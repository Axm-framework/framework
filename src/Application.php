<?php

declare(strict_types=1);

namespace Axm;

use Axm;
use Locale;
use Axm\Container;
use Axm\Exception\AxmException;

/**
 * Class Application
 *
 * The Application class represents the core of the framework.
 * It is responsible for managing the application's lifecycle and components.
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Framework
 */
abstract class Application
{
	const EVENT_BEFORE_REQUEST = 'beforeRequest';
	const EVENT_AFTER_REQUEST  = 'afterRequest';

	/**
	 * @var Container|null The container for managing components.
	 */
	private ?Container $container;

	/**
	 * @var string The socket token used for specific operations.
	 */
	private string $socketToken;

	/**
	 * Constructor for the Application class.
	 * Initializes the application.
	 *
	 * @param array $config An optional configuration array.
	 */
	public function __construct($config = [])
	{
		Axm::setApplication($this);
		$this->init();
	}

	/**
	 * Get the container instance.
	 * @return Container The container instance.
	 */
	public function getContainer(): Container
	{
		return $this->container ??= Container::getInstance();
	}

	/**
	 * Initialize the application by loading services, including internal functions,
	 * configuration files,routes, and generating security tokens.
	 */
	private function init(): void
	{
		$this->getContainer()
			->loadFromDirectory(APP_PATH . DIRECTORY_SEPARATOR . 'Providers');

		$this->openDefaultSystemConfigurationFiles();
		$this->openRoutesUser();
		$this->generateTokens();
	}

	/**
	 * Open the default system configuration files.
	 */
	private function openDefaultSystemConfigurationFiles(): void
	{
		$path = APP_PATH . DIRECTORY_SEPARATOR . 'Config';
		$this->config->load($path . DIRECTORY_SEPARATOR . 'App.php');
		$this->config->load($path . DIRECTORY_SEPARATOR . 'Paths.php');
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
		$config = Axm\BaseConfig::make();
		if (is_null($key))
			return $config;

		if (str_contains($key, '/')) {
			$path = is_null($rootBaseConfig) ? $config::ROOT_PATH_CONFIG .
				$key : $rootBaseConfig;
			return $config->load($path);
		}

		return $config->get($key);
	}

	/**
	 * Open user routes configuration files.
	 */
	public function openRoutesUser(): void
	{
		$ext = '.php';
		$files = glob(ROOT_PATH . DIRECTORY_SEPARATOR .
			'routes' . DIRECTORY_SEPARATOR . "*$ext");

		foreach ($files as $file) {
			include_once($file);
		}
	}

	/**
	 * Load a configuration file.
	 *
	 * @param string $path The path to the configuration file.
	 * @param string $root An optional root directory for the path.
	 *
	 * @return mixed The result of the configuration file load operation.
	 */
	public function load(string $path, string $root = APP_PATH)
	{
		$filePath = $root . DIRECTORY_SEPARATOR . str_replace(
			'.',
			DIRECTORY_SEPARATOR,
			pathinfo($path, PATHINFO_FILENAME)
		) . '.' . pathinfo($path, PATHINFO_EXTENSION);

		return $this->container->load($filePath);
	}

	/**
	 * Generate security tokens, including CSRF tokens.
	 */
	private function generateTokens(): void
	{
		$this->generateCsrfToken();
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
	 * Log out the user.
	 * @param string $path The optional path to redirect after logout.
	 */
	public function logout(string $path = '/'): void
	{
		$this->session->flush();
		$this->response->redirect($path);
	}

	/**
	 * Get the event handler intent.
	 * @return mixed The event handler intent.
	 */
	public function event()
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
	 * @throws AxmException If the "intl" extension is not enabled on the server.
	 */
	public function getLocale()
	{
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return '';
		}

		if (!extension_loaded('intl')) {
			throw new AxmException('The "intl" extension is not enabled on this server');
		}

		return Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	}

	/**
	 * Generate a CSRF token.
	 *
	 * This method generates a CSRF token and stores it in a cookie. 
	 * If a token already exists in the cookie, it is reused.
	 * @return string The generated CSRF token.
	 */
	public function generateCsrfToken(): string
	{
		if (empty($_COOKIE['csrfToken'])) {
			return $_COOKIE['csrfToken'] = bin2hex(random_bytes(50) . time());
		}

		return $_COOKIE['csrfToken'];
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
	 * Get the CSRF token.
	 *
	 * This method retrieves the CSRF token from the session, or generates a new one if not available.
	 * @return string|null The CSRF token or null if not found.
	 */
	public function getCsrfToken(): ?string
	{
		return $_COOKIE['csrfToken'] ?? $this->generateCsrfToken();
	}

	/**
	 * Check if the provided token matches the socket token.
	 *
	 * This method compares the provided token with the socket token to determine if they match.
	 * @param string $token The token to check against the socket token.
	 * @return bool True if the provided token matches the socket token, false otherwise.
	 */
	public function checkSocketToken(string $token): bool
	{
		return $this->socketToken === $token;
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
		if (is_null($value))
			return $this->container->get('user');

		return $this->container
			->get('user')
			->{$value} ?? null;
	}

	/**
	 * Create and return a singleton instance of a service.
	 *
	 * This method creates and returns a singleton instance of a service identified by its alias.
	 * @param string $alias The alias of the service.
	 * @return object The singleton instance of the service.
	 */
	public function singleton($alias)
	{
		return $this->container->singleton($alias);
	}

	/**
	 * Add a service to the container and return it.
	 *
	 * This method adds a service identified by its alias to the container and returns 
	 * the service instance.
	 * @param string $alias The alias of the service.
	 * @param mixed $args The arguments or configuration for creating the service.
	 * @return object The added service instance.
	 */
	public function addService(string $alias, $args)
	{
		$this->container->set($alias, $args);
		return $this->container->get($alias);
	}

	/**
	 * Get a service instance by its alias.
	 *
	 * This method retrieves a service instance from the container based on its alias.
	 * @param string $alias The alias of the service.
	 * @return object|null The service instance or null if not found.
	 */
	public function getService(string $alias)
	{
		return $this->container->get($alias);
	}

	/**
	 * Get all registered services.
	 *
	 * This method returns an array containing all registered services in the container.
	 * @return array An array of registered services.
	 */
	public function getServices()
	{
		return $this->container->getServices();
	}

	/**
	 * Check if a service with the given alias exists in the container.
	 *
	 * This method checks whether a service with the specified alias exists in the container.
	 * @param string $alias The alias of the service to check.
	 * @return bool True if the service exists, false otherwise.
	 */
	public function hasService(string $alias): bool
	{
		return $this->container->has($alias);
	}

	/**
	 * Remove a service from the container.
	 *
	 * This method removes a service identified by its alias from the container.
	 * @param string $alias The alias of the service to remove.
	 */
	public function removeService(string $alias)
	{
		return $this->container->remove($alias);
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
		if ($name === 'user') {
			$this->setUser();
		}

		return $this->container->get($name);
	}

	/**
	 * Magic method to dynamically set properties.
	 *
	 * This magic method allows you to set properties of the container dynamically.
	 * @param string $name The name of the property to set.
	 * @param mixed $value The value to set for the property.
	 */
	public function __set($name, $value)
	{
		$this->container->set($name, $value);
	}

	/**
	 * Magic method to check if a property exists.
	 *
	 * This magic method checks if a property with the given name exists in the container.
	 * @param string $name The name of the property to check.
	 * @return bool True if the property exists, false otherwise.
	 */
	public function __isset($name)
	{
		return $this->container->has($name);
	}

	/**
	 * Magic method to unset properties.
	 *
	 * This magic method allows you to unset properties of the container dynamically.
	 * @param string $name The name of the property to unset.
	 */
	public function __unset($name)
	{
		$this->container->remove($name);
	}

	/**
	 * Magic method to call container methods dynamically.
	 *
	 * This magic method allows you to call methods of the container dynamically.
	 * @param string $name The name of the method to call.
	 * @param array $arguments The arguments to pass to the method.
	 */
	public function __call($name, $arguments)
	{
		if (!method_exists($this, $name)) {
			$this->container->$name(...$arguments);
		}
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
