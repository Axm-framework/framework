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
	 */
	private function setUserSession($userId, $result): bool
	{
		return $this->app->session->write($userId, serialize($result));
	}

	/**
	 * Retrieves a user from the database based on provided field and value.
	 */
	private function getUserFromDatabase(string $userClass, string $field, $value)
	{
		// Use prepared statements or an ORM to prevent SQL injection
		return $userClass::where($field, $value)->first();
	}

	/**
	 * Attempts a login with the given credentials.
	 */
	public function attemptLogin(string $username, string $password): bool
	{
		if ($this->failedAttempts >= $this->maxFailedAttempts) {
			throw new Exception('Maximum number of failed login attempts reached.');
		}

		$user = $this->getUserFromDatabase($this->userClass, $this->usernameField, $username);

		if (!$user || !password_verify($password, $user->{$this->passwordField})) {
			$this->failedAttempts++;
			return false;
		}

		$this->failedAttempts = 0;
		$this->session->write($this->userId, $user->{$this->userModel->getKeyName()});
		return true;
	}

	/**
	 * Checks if there is a currently authenticated user 
	 */
	public function check(): bool
	{
		return $this->session->has($this->userId);
	}

	/**
	 * Returns the currently authenticated user or null if there 
	 * is no authenticated user.
	 */
	public function user()
	{
		if (!$this->check()) return null;

		return $this->userModel;
	}

	/**
	 * Logs out the current user.
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
