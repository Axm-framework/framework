<?php

declare(strict_types=1);

use Axm\Application;

/**
 * Axm Framework PHP.
 *
 * The Axm class serves as the entry point for the AXM Framework. It provides methods for
 * initializing the application, handling exceptions and errors, and managing services.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class Axm
{
	public  static $framework = 'Axm Framework';
	private static $version;
	public  static $_environment;
	private static $_app;

	/**
	 * Initializes the application.
	 *
	 * This method performs the initial setup for the application. It includes
	 * bootstrapping, initializing system handlers, and detecting the environment.
	 * @return void
	 */
	private static function initialize()
	{
		// Add framework constants
		self::boot();

		// Initialize system handlers
		self::initSystemHandlers();

		// Detect environment
		self::initializeEnvironment();
	}

	/**
	 * Bootstraps the application.
	 *
	 * This method is responsible for including the specified bootstrap file,
	 * typically used for initializing configurations, autoloading, and other
	 * necessary setup for the application.
	 * @param string $bootstrapFileName The name of the bootstrap file to include.
	 *                                  Defaults to 'bootstrap.php'.
	 * @return void
	 */
	public static function boot(string $bootstrapFileName = 'bootstrap.php'): void
	{
		$bootstrapPath = __DIR__ . DIRECTORY_SEPARATOR . $bootstrapFileName;
		require_once $bootstrapPath;
	}

	/**
	 * Initializes system-specific error handlers.
	 *
	 * This method is responsible for setting up system-specific error handlers,
	 * such as those defined in the `Axm\HandlerErrors` class. It ensures that the
	 * necessary error handling configurations are applied.
	 * @return void
	 */
	protected static function initSystemHandlers()
	{
		Axm\HandlerErrors::get()->run();
	}

	/**
	 * Checks if the script is running in a command-line environment.
	 *
	 * This method determines whether the script is being executed in a command-line
	 * interface (CLI) environment based on the PHP_SAPI value or the absence of
	 * specific server variables commonly present in web requests.
	 * @return bool True if the script is running in a CLI environment, false otherwise.
	 */
	public static function is_cli(): bool
	{
		if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) return true;

		return !isset($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Initializes the application environment.
	 *
	 * This method sets up the application environment based on the value of
	 * APP_ENVIRONMENT. It configures error reporting and display settings
	 * according to the specified environment.
	 * @return void
	 */
	private static function initializeEnvironment(string $appEnvironment = null)
	{
		// Obtain the value of APP_ENVIRONMENT or use a default value
		$environment = static::$_environment = $appEnvironment ?? env('APP_ENVIRONMENT', 'production');

		// Set error reporting based on the environment
		($environment === 'debug')
			? self::configureForDebug()
			: self::configureForProduction();
	}

	/**
	 * Configure error reporting and display settings for debugging.
	 * @return void
	 */
	private static function configureForDebug()
	{
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}

	/**
	 * Configure error reporting and display settings for production.
	 * @return void
	 */
	private static function configureForProduction()
	{
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED
			& ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
		ini_set('display_errors', 0);
	}

	/**
	 * Start the application using the specified configuration.
	 *
	 * @param array|null $config An optional configuration array.
	 * @return Application The initialized application instance.
	 */
	public static function startApplication(array|null $config = null)
	{
		return self::buildApplication('Axm\\initApplication', $config);
	}

	/**
	 * Build and initialize an application instance of the specified class with optional configuration.
	 *
	 * @param string      $class  The fully qualified class name of the application.
	 * @param array|null  $config An optional configuration array.
	 * @return Application The initialized application instance.
	 */
	private static function buildApplication(string $class, array $config = null)
	{
		self::initialize();
		return new $class($config);
	}

	/**
	 * Create and initialize a console application instance with optional configuration.
	 *
	 * @param array|null $config An optional configuration array.
	 * @return ConsoleApplication The initialized console application instance.
	 */
	public static function createConsoleApplication(array|null $config = null)
	{
		return self::buildApplication('Axm\\Console\\ConsoleApplication', $config);
	}

	/**
	 * Get or register a service in the application.
	 *
	 * @param string|object|null $alias   The alias or object representing the service.
	 * @param Closure|null       $callback A callback to create the service if not registered.
	 * @param bool               $shared   Indicates if the service should be shared.
	 * @return mixed The registered service or the result of the callback.
	 * @throws RuntimeException If the service is not found.
	 */
	public static function app(string|object|null $alias = null, Closure|null $callback = null, bool $shared = false)
	{
		if ($alias === null) {
			return self::getSingleton();
		}

		$alias = is_object($alias) ? get_class($alias) : $alias;

		if (!self::$_app->has($alias)) {
			return self::$_app->make($alias);
		}

		return self::$_app->get($alias)
			?? throw new RuntimeException(sprintf('Service %s not found.', $alias));
	}

	/**
	 * Get the singleton instance of the application.
	 * @return Application|null The singleton instance of the application, or null if not set.
	 */
	private static function getSingleton(): ?Application
	{
		return self::$_app;
	}

	/**
	 * Get the current environment of the application.
	 * @return string The current environment, such as 'production', 'debug', etc.
	 */
	public static function getEnvironment()
	{
		return static::$_environment;
	}

	/**
	 * Check if the application is in production environment.
	 * @return bool True if the environment is 'production', false otherwise.
	 */
	public static function isProduction(): bool
	{
		return static::$_environment === 'production';
	}

	/**
	 * Get performance statistics of the application.
	 *
	 * @return array An associative array containing performance statistics.
	 *               - 'startTime': The start time of the application.
	 *               - 'totalTime': The total execution time of the application.
	 */
	public static function getPerformanceStats(): array
	{
		return [
			'startTime' => AXM_BEGIN_TIME,
			'totalTime' => (microtime(true) - AXM_BEGIN_TIME),
		];
	}

	/**
	 * Set the singleton instance of the application.
	 *
	 * @param Application $app The application instance to set.
	 * @throws Exception If the application instance has already been set.
	 */
	public static function setApplication(Application $app): void
	{
		if (self::$_app !== null) {
			throw new Exception('Axm application can only be created once.');
		}

		self::$_app = $app;
	}

	/**
	 * Get the installed version of the Axm Framework from composer.json.
	 * @return string|null The version of the Axm Framework or null if not found.
	 */
	public static function version(): ?string
	{
		if (isset($version)) return self::$version;

		self::$version = \Composer\InstalledVersions::getVersion('axm/framework');
		return self::$version;
	}
}
