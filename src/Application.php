<?php

declare(strict_types=1);

namespace Axm;

use Axm;
use Locale;
use Axm\Container;
use Axm\Auth\Auth;
use Exception;

/**
 * Class Application
 *
 * The Application class represents the core of the framework.
 * It is responsible for managing the application's lifecycle and components.
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Framework
 */
class Application extends Container
{
	const EVENT_BEFORE_REQUEST = 'beforeRequest';
	const EVENT_AFTER_REQUEST  = 'afterRequest';

	protected $class = [
		'config'     => Axm\Config::class,
		'event'      => Axm\EventManager::class,
		'session'    => Axm\Session\Session::class,
		'request'    => Axm\Http\Request::class,
		'response'   => Axm\Http\Response::class,
		'router'     => Axm\Http\Router::class,
		'view'       => Axm\Views\View::class,
		'controller' => \App\Controllers\BaseController::class,
		'database'   => Axm\Database::class,
		// 'cache'      => Axm\Cache\Cache::class,
	];

	/**
	 * Constructor for the Application class.
	 * Initializes the application.
	 *
	 * @param array $config An optional configuration array.
	 */
	public function __construct($config = [])
	{
		Axm::setApplication($this);
		$this->preInit();
		$this->init();
	}

	/**
	 * Pre-Initialize the application by loading services,
	 * configuration files,routes, and generating security tokens.
	 */
	private function preInit(): void
	{
		$this->registerProviders();
		$this->initializeServices();
	}

	/**
	 * Initialize the application by loading services, including internal functions,
	 * configuration files,routes, and generating security tokens.
	 */
	private function init(): void
	{
		$this->openRoutesUser();
	}

	/**
	 * Register service providers.
	 *
	 * This method reads the service provider configurations from the `providers.php` file
	 * and registers them with the application container.
	 */
	public function registerProviders()
	{
		$pathConfig = config('paths.providersPath') . DIRECTORY_SEPARATOR;
		$provider   = include $pathConfig . 'providers.php';
		$providers  = array_merge_recursive($this->class, $provider);

		$this->set($providers);
	}

	/**
	 * Get the application configuration.
	 *
	 * @param string $key An optional configuration key.
	 * @return mixed The configuration value for the specified key, or the entire 
	 * configuration if no key is provided.
	 */
	public function config(string $key = null, $rootBaseConfig = null)
	{
		return config($key, $rootBaseConfig);
	}

	/**
	 * Open user routes configuration files.
	 */
	public function openRoutesUser(): void
	{
		$ext = '.php';
		$pathConfig = config('paths.routesPath') . DIRECTORY_SEPARATOR;
		$files = glob($pathConfig . "*$ext");
		foreach ($files as $file) {
			require_once($file);
		}
	}

	/**
	 * Check if the application is in production mode.
	 * @return bool True if the application is in production mode, false otherwise.
	 */
	public function isProduction(): bool
	{
		return Axm::isProduction();
	}

	/**
	 * Check if the user is logged in.
	 * @return bool True if the user is logged in, false otherwise.
	 */
	public function isLogged(): bool
	{
		return !empty($this->user);
	}

	/**
	 * Set the user from the session variable.
	 */
	private function getUser(): void
	{
		$this->user = function () {
			return $this->session->get('user', true);
		};
	}

	/**
	 * Set the user from the session variable.
	 */
	private function setUser(): void
	{
		$this->user = function () {
			return $this->session->set('user', true);
		};
	}

	/**
	 * Attempts to log in a user based on provided data.
	 *
	 * @param array|array[] $fields An array or nested arrays containing the fields to use for the database query.
	 * @param array|array[] $values An array or nested arrays containing the corresponding values to match in the database query.
	 * @param callable|null $callback A callback function to execute upon successful login.
	 * @return bool Returns true if the login is successful, false otherwise.
	 * @throws \Exception Throws an exception in case of an error during the login process.
	 */
	public function login(): bool
	{
		$instance = new Auth($this);
		return $instance->resolverLogin(...func_get_args());
	}

	/**
	 * Log out the user.
	 * @param string $path The optional path to redirect after logout.
	 */
	public function logout(string $path = '/'): void
	{
		$this->session->flush();
		$this->response->redirect($path);
	}

	/**
	 * Get the events handler intent.
	 * @return mixed The event handler intent.
	 */
	public function events()
	{
		return $this->event;
	}

	/**
	 * Get event details by name.
	 *
	 * @param string $eventName The name of the event.
	 * @return array Event details if the event exists, an empty array otherwise.
	 */
	public function getEvent(string $eventName): array
	{
		return $this->event->getEvent($eventName);
	}

	/**
	 * Register events and their callbacks.
	 *
	 * @param string   $eventName The name of the event.
	 * @param callable $callback  The callback function to be executed for the event.
	 */
	public function registerEvent(string $eventName, callable $callback)
	{
		if (!$this->event->hasEvent($eventName)) {
			// Check if the event exists before registering it.
			$this->event->onEvent($eventName, $callback);
		}
	}

	/**
	 * Returns the time zone used by this application.
	 * 
	 * This is a simple wrapper of PHP function date_default_timezone_get().
	 * @return string the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-get.php
	 */
	public function getTimeZone(): string
	{
		return date_default_timezone_get();
	}

	/**
	 * Sets the time zone used by this application.
	 * 
	 * This is a simple wrapper of PHP function date_default_timezone_set().
	 * @param string $value the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-set.php
	 */
	public function setTimeZone(string $timezone): void
	{
		date_default_timezone_set($timezone);
	}

	/**
	 * Get the user's preferred locale based on the HTTP Accept-Language header.
	 *
	 * This method retrieves the user's preferred locale from the HTTP Accept-Language header.
	 * @return string The user's preferred locale or an empty string if not available.
	 * @throws Exception If the "intl" extension is not enabled on the server.
	 */
	public function getLocale(): string|false
	{
		if (!extension_loaded('intl'))
			throw new Exception('The "intl" extension is not enabled on this server');

		$http = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
		$locale = !empty($http) ? $http : 'en_US';

		return $locale;
	}

	/**
	 * Generate security tokens, including CSRF tokens.
	 */
	private function generateTokens(): string
	{
		return bin2hex(random_bytes(64) . time());
	}

	/**
	 * Modify cookies for the CSRF token
	 */
	public function setCsrfCookie(string $csrfToken): void
	{
		$expiration = config('session.expiration');
		setcookie('csrfToken', $csrfToken, time() + 60 * $expiration);
	}

	/**
	 * Get the CSRF token. If the token is not present in the cookie, generate and set a new one.
	 * @return string The generated or existing CSRF token.
	 */
	public function getCsrfToken(): string
	{
		return isset($_COOKIE['csrfToken']) ? $_COOKIE['csrfToken'] : $this->generateAndSetCsrfToken();
	}

	/**
	 * Generate a CSRF token and set it in the cookie.
	 * @return string The newly generated CSRF token.
	 */
	private function generateAndSetCsrfToken(): string
	{
		$csrfToken = $this->generateTokens();
		$this->setCsrfCookie($csrfToken);

		return $csrfToken;
	}

	/**
	 * Check if the provided CSRF token matches the one in the session.
	 *
	 * This method compares the provided CSRF token with the one stored in the session to verify its authenticity.
	 * @param string $token The CSRF token to check.
	 * @return bool True if the provided CSRF token matches the one in the session, false otherwise.
	 */
	public function hasCsrfToken(string $token): bool
	{
		return $_COOKIE['csrfToken'] === $token;
	}

	/**
	 * Get all added helpers.
	 *
	 * This method returns an array containing all the added helpers.
	 * @return array An array of added helpers.
	 */
	public function getHelpers(): array
	{
		return $this->helpers;
	}

	/**
	 * Get the user or a specific user property.
	 *
	 * This method allows you to retrieve the user or a specific property of the user.
	 * @param string|null $value The name of the user property to retrieve. If null, the entire user is returned.
	 * @return mixed|null The user or the specified user property, or null if not found.
	 */
	public function user(string $value = null)
	{
		if (is_null($value)) return $this->container->make('user');

		return $this->container
			->make('user')
			->{$value} ?? null;
	}

	/**
	 * Remove a service from the container.
	 *
	 * This method removes a service identified by its alias from the container.
	 * @param string $alias The alias of the service to remove.
	 */
	public function removeService(string $alias)
	{
		return $this->container->unbind($alias);
	}

	/**
	 * Magic method to dynamically retrieve properties.
	 *
	 * This magic method allows you to access properties of the container and retrieve them.
	 * @param string $name The name of the property to retrieve.
	 * @return mixed The value of the retrieved property.
	 */
	public function __get($name)
	{
		return match (true) {
			($name == 'config') => config(),
			($name == 'user')   => $this->setUser(),
			default => $this->get($name),
		};
	}

	/**
	 * Run the application.
	 *
	 * This method triggers events before and after handling the request 
	 * and dispatches the router.
	 */
	public function run(): void
	{
		$this->event->triggerEvent(self::EVENT_BEFORE_REQUEST);
		$this->router->dispatch();
		$this->event->triggerEvent(self::EVENT_AFTER_REQUEST);
	}
}
