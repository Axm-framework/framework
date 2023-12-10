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
	private static $initialized = false;


	/**
	 * Ensures that the class is initialized.
	 *
	 * This method checks if the class has already been initialized. If not,
	 * it sets the initialization flag to true and calls the initialize method.
	 * This helps to ensure that the necessary setup or initialization is performed
	 * only once.
	 * @return void
	 */
	private static function ensureInitialized()
	{
		if (!self::$initialized) {
			self::$initialized = true;
			self::initialize();
		}
	}

	/**
	 * Initializes the application.
	 *
	 * This method performs the initial setup for the application. It includes
	 * bootstrapping, initializing system handlers, and detecting the environment.
	 * It is typically called after ensuring that the class is initialized.
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
	 *
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
		if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
			return true;
		}

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
	private static function initializeEnvironment()
	{
		// Obtain the value of APP_ENVIRONMENT or use a default value
		static::$_environment = $env = env('APP_ENVIRONMENT', 'production');

		if ($env === 'debug') {
			ini_set('display_errors', 1);
			error_reporting(E_ALL);
		} else {
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED
				& ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
			ini_set('display_errors', 0);
		}
	}

	/**
	 * Starts the application.
	 *
	 * This method initiates the application by calling the specified initialization
	 * function and passing the provided configuration. It returns the initialized
	 * application instance.
	 * @param array|null $config An optional array of configuration parameters.
	 * @return object The initialized application instance.
	 */
	public static function startApplication(array $config = null)
	{
		return self::buildApplication('Axm\\initApplication', $config);
	}

	/**
	 * Initializes the application.
	 *
	 * @param string $class   The application class name.
	 * @param mixed  $config  Application configuration.
	 */
	private static function buildApplication(string $class, array $config = null)
	{
		self::ensureInitialized();
		return new $class($config);
	}

	/**
	 * Creates a console application instance.
	 *
	 * @param mixed $config application configuration.
	 * If a string, it is treated as the path of the file that contains the configuration;
	 * If an array, it is the actual configuration information.
	 * @return Console
	 */
	public static function createConsoleApplication($config = null)
	{
		return self::buildApplication('Axm\\Console\\ConsoleApplication', $config);
	}

	/**
	 * Gets or registers a service in the application container.
	 *
	 * This method allows getting or registering a service in the application container.
	 * If no arguments are provided, it returns the singleton instance of the application.
	 * If an alias is provided, it checks if the service is registered and returns it.
	 * If a callback is provided, it registers the service with the specified alias and callback.
	 * @param string|object|null $alias    The alias or object of the service.
	 * @param Closure|null       $callback The callback function to create the service.
	 * @param bool               $shared   Whether the service should be shared (singleton) or not.
	 * @return object The registered or retrieved service instance.
	 * @throws InvalidArgumentException If the provided alias or callback is not valid.
	 * @throws RuntimeException         If the requested service is not found.
	 */
	public static function app($alias = null, Closure $callback = null, bool $shared = false)
	{
		if ($alias === null && $callback === null) {
			return self::getSingleton();
		}

		if ($alias !== null && !is_string($alias)  && !is_object($alias)) {
			throw new InvalidArgumentException('Alias must be a string, an object, or null.');
		}

		if ($callback !== null && !($callback instanceof Closure)) {
			throw new InvalidArgumentException('Callback must be a Closure or null.');
		}

		$class = null;
		if (is_object($alias)) {
			$class = $alias;
			$alias = get_class($alias);
		}

		$serviceRegistred = self::$_app->hasService($alias);
		if ($alias !== null && $callback === null) {

			if (is_object($alias) && !$serviceRegistred) {
				return self::$_app->addService($alias, $class, $shared);
			}

			if (class_exists($alias) && !$serviceRegistred) {
				return self::$_app->addService($alias, fn () => new $alias, $shared);
			}

			$service = self::$_app->getService($alias);
			if ($service === null) {
				throw new RuntimeException("Service '{$alias}' not found.");
			}

			if (class_exists($alias) && $serviceRegistred) {
				return $service;
			}

			return $service;
		}

		if (!$serviceRegistred) {
			return self::$_app->addService($alias, $callback, $shared);
		}
	}

	/**
	 * Gets the singleton instance of the application.
	 *
	 * This method returns the singleton instance of the application container.
	 * @return Application|null The singleton instance of the application, or null if not set.
	 */
	private static function getSingleton(): ?Application
	{
		return self::$_app;
	}

	/**
	 * Gets the current application environment.
	 *
	 * This method returns the current application environment, which is typically
	 * determined based on the value of the `APP_ENVIRONMENT` variable or a default value.
	 * @return string The current application environment.
	 */
	public static function getEnvironment()
	{
		return static::$_environment;
	}

	/**
	 * Checks if the application is in production environment.
	 *
	 * This method returns true if the current application environment is set to
	 * production, as determined by the value of the `APP_ENVIRONMENT` variable.
	 * @return bool True if the application is in production environment, false otherwise.
	 */
	public static function isProduction(): bool
	{
		return static::$_environment === ENV_PRODUCTION;
	}

	/**
	 * Gets performance statistics for the script execution.
	 *
	 * This method returns an array containing performance statistics for the script execution.
	 * It includes the start time and the total time elapsed since the script began executing.
	 * @return array An associative array containing performance statistics.
	 *               - 'startTime': The timestamp when the script started executing.
	 *               - 'totalTime': The total time elapsed since the script began executing.
	 */
	public static function getPerformanceStats(): array
	{
		return [
			'startTime' => AXM_BEGIN_TIME,
			'totalTime' => (microtime(true) - AXM_BEGIN_TIME),
		];
	}

	/**
	 * Sets the application instance.
	 *
	 * This method sets the application instance, ensuring that it can only be set once.
	 * If the application instance is already set, an exception is thrown.
	 * @param Application $app The instance of the application to set.
	 * @return void
	 * @throws Exception If the application instance is already set.
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
	public static function version()
	{
		if (isset($version)) return self::$version;

		self::$version = \Composer\InstalledVersions::getVersion('axm/framework');
		return self::$version;
	}
}
