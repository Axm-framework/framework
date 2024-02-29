<?php

namespace Auth;

use App;
use Exception;

/**
 * Class Application
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Auth
 */

class Auth
{
	protected $userClass;
	protected $usernameField;
	protected $passwordField;
	protected $session;
	protected $maxFailedAttempts = 5;
	protected $failedAttempts    = 0;
	protected $primaryKey;
	protected $userId;
	protected $user;
	protected $userModel;
	protected $app;

	const EVENT_BEFORE_AUTH = 'beforeAuth';
	const EVENT_AFTER_AUTH  = 'afterAuth';


	/**
	 * __construct
	 *
	 * @param  Axm\App $app
	 * @param  string $usernameField
	 * @param  string $passwordField
	 * @return void
	 */
	public function __construct(App $app, string $usernameField = 'email', string $passwordField = 'password')
	{
		$this->app = $app;

		$this->session = $app->session;
		$this->userClass = config('app.userClass');
		$this->usernameField = $usernameField;
		$this->passwordField = $passwordField;
	}

	/**
	 * Attempts to log in a user based on provided data.
	 *
	 * @param array[] ...$keyValuePairs An array or nested arrays containing the fields and values to use for the database query.
	 * @return bool Returns true if the login is successful, false otherwise.
	 * @throws \Exception Throws an exception in case of an error during the login process.
	 */
	public function resolverLogin(array ...$keyValuePairs): bool
	{
		$count = count($keyValuePairs);
		return match ($count) {
			1 => $this->loginWithSingleKey($keyValuePairs[0]),
			2 => $this->loginWithMultipleKeys($keyValuePairs[0], $keyValuePairs[1]),
			default => false,
		};
	}

	/**
	 * Attempts to log in a user based on a single key-value pair.
	 *
	 * @param array $data An associative array containing user login data.
	 * @return bool Returns true if the login is successful, false otherwise.
	 * @throws \Exception Throws an exception in case of an error during the login process.
	 */
	private function loginWithSingleKey(array $data): bool
	{
		[$field, $value] = $data;
		$result = $this->getUserFromDatabase($this->userClass, $field, $value);
		if ($result) {
			$keyName = (string)(new $this->userClass)->getKeyName();
			return $this->setUserSession($keyName, $result);
		}

		return false;
	}

	/**
	 * Attempts to log in a user based on multiple key-value pairs.
	 *
	 * @param array $keys An array containing the fields to use for the database query.
	 * @param array $values An array containing the corresponding values to match in the database query.
	 * @return bool Returns true if the login is successful, false otherwise.
	 * @throws \Exception Throws an exception in case of an error during the login process.
	 */
	private function loginWithMultipleKeys(array $keys, array $values): bool
	{
		if (count($keys) !== count($values))
			throw new \InvalidArgumentException('Number of keys and values must match.');

		$userData = array_combine($keys, $values);
		foreach ($userData as $field => $value) {
			if ($result = $this->getUserFromDatabase($this->userClass, $field, $value)) {
				$keyName = (new $this->userClass)->getKeyName();
				return $this->setUserSession($keyName, $result);
			}
		}

		return false;
	}

	/**
	 * Sets the user session based on the provided user ID and data.
	 *
	 * @param string $userId The key to use for storing user data in the session.
	 * @param mixed $result The user data to store in the session.
	 * @return bool
	 */
	private function setUserSession($userId, $result): bool
	{
		return $this->app->session->write($userId, serialize($result));
	}

	/**
	 * Retrieves a user from the database based on provided field and value.
	 *
	 * @param string $userClass The class representing the user model.
	 * @param string $field The field to use for the database query.
	 * @param mixed $value The value to match in the database query.
	 * @return mixed|null Returns the user object if found, or null if no user is found.
	 */
	private function getUserFromDatabase(string $userClass, string $field, $value)
	{
		// Use prepared statements or an ORM to prevent SQL injection
		return $userClass::where($field, $value)->first();
	}

	/**
	 * Performs a login attempt with the provided credentials 
	 * Returns true if the login was successful, false otherwise.
	 *
	 * @param  mixed $username
	 * @param  mixed $password
	 * @return void
	 */
	public function attempt($username, $password)
	{
		if ($this->failedAttempts >= $this->maxFailedAttempts) {
			throw new \Exception('You have reached the maximum number of failed attempts.');
		}

		$this->userModel = $this->getUserFromDatabase($this->userClass, $this->usernameField, $username);

		if (!$this->userModel || !password_verify($password, $this->userModel->{$this->passwordField})) {
			++$this->failedAttempts;
			return false;
		}

		$this->failedAttempts = 0;
		$this->session->write($this->userId, $this->userModel->{$this->userModel->primaryKey});
		return true;
	}

	/**
	 * Checks if there is a currently authenticated user 
	 * Returns true if there is an authenticated user, false otherwise.
	 * @return bool
	 */
	public function check(): bool
	{
		return $this->session->has($this->userId);
	}

	/**
	 * Returns the currently authenticated user or null if there 
	 * is no authenticated user.
	 * @return void
	 */
	public function user()
	{
		if (!$this->check()) return null;

		return $this->userModel;
	}

	/**
	 * Logs out the current user.
	 * @return void
	 */
	public function logout(string $path = null): void
	{
		$this->session->remove($this->userId);
		if (!is_null($path)) {
			app()->response->redirect($path);
		}
	}

	/**
	 * Returns the maximum number of failed attempts allowed.
	 */
	public function getMaxFailedAttempts()
	{
		return (int) $this->maxFailedAttempts;
	}

	/**
	 * Sets the maximum number of failed attempts allowed.
	 */
	public function setMaxFailedAttempts($maxFailedAttempts)
	{
		$this->maxFailedAttempts = $maxFailedAttempts;
	}

	/**
	 * Returns the number of unsuccessful attempts made at current login.
	 */
	public function getFailedAttempts()
	{
		return $this->failedAttempts ?? null;
	}
}
