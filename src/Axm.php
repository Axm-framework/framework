<?php

declare(strict_types=1);

use Axm\Application;
use Axm\Exception\AxmException;
use Axm\Exception\AxmCLIException;

/**
 * AXM Framework PHP.
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
	public static $framework = 'Axm Framework';

	private static $version;

	/**
	 * @var array Class map used by the AXM autoloading mechanism.
	 * The array keys are the class names, and the values are the corresponding class file paths.
	 * @since 1.1.5
	 */
	public static $classMap = [];

	/**
	 * Request path to use.
	 *
	 * @var string
	 */
	protected $path;

	public static $_environment;
	private static $_app;
	private static $initialized = false;


	private static function ensureInitialized()
	{
		if (!self::$initialized) {
			self::$initialized = true;
			self::initialize();
		}
	}

	/**
	 * Initializes the AXM Framework.
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
	 * Starts the application by loading the specified bootstrap file.
	 *
	 * @param string $bootstrapFileName The name of the bootstrap file.
	 * @throws Exception If the bootstrap file cannot be loaded or is unreadable.
	 */
	public static function boot(string $bootstrapFileName = 'bootstrap.php'): void
	{
		$bootstrapPath = __DIR__ . DIRECTORY_SEPARATOR . $bootstrapFileName;
		require_once $bootstrapPath;
	}

	/**
	 * Initializes the error handlers.
	 */
	protected static function initSystemHandlers()
	{
		if (self::is_cli()) {
			return set_exception_handler(function (\Throwable $e) {
				AxmCLIException::handleCLIException($e);
			});
		}

		\Axm\HandlerErrors::make(new \Whoops\Handler\PrettyPageHandler, new \Whoops\Run);
		// if (env('AXM_ENABLE_EXCEPTION_HANDLER', true)) {
		// 	set_exception_handler(fn ($e) => self::handleException($e));
		// }

		// if (env('AXM_ENABLE_ERROR_HANDLER', true)) {
		// 	set_error_handler([self::class, 'handleError'], error_reporting());
		// }
	}

	/**
	 * Manages and displays application features.
	 */
	private static function handleException(\Throwable $e)
	{
		// Disable error capturing to avoid recursive errors
		restore_error_handler();
		restore_exception_handler();

		if (self::is_cli()) {
			return AxmCLIException::handleCLIException($e);
		}

		return AxmException::handleException($e);
	}

	/**
	 * Handles and displays the captured PHP error.
	 *
	 * This method displays the error in HTML when there is
	 * no active error handler.
	 * @param int    $code   Error code.
	 * @param string $msg    Error message (optional).
	 * @param string $file   Error file.
	 * @param int    $line   Error line.
	 * @return \AxmException Custom error exception object.
	 */
	public static function handleError($code, $message, $file, $line)
	{
		if (self::is_cli()) {
			// Create a custom error exception object.
			$e = new \ErrorException($message, $code, 0, $file, $line);
			return AxmCLIException::handleCLIException($e);
		}

		// Create a custom error exception object.
		throw new \ErrorException($message, $code, 0, $file, $line);
	}


	/**
	 * Checks if the application is running in a CLI environment.
	 */
	public static function is_cli(): bool
	{
		if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
			return true;
		}

		return !isset($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Starts the environment and configures error handling.
	 */
	private static function initializeEnvironment()
	{
		// Obtain the value of AXM_ENVIRONMENT or use a default value
		static::$_environment = $environment = env('AXM_ENVIRONMENT', 'production');

		// Configuring environment-based error handling.
		if ($environment === 'debug') {
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
		} else {
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
			ini_set('display_errors', '0');
		}
	}

	/**
	 * Creates an application instance of the specified class.
	 *
	 * @return mixed The application instance.
	 */
	public static function startApplication()
	{
		return self::createApplication('Axm\\initApplication');
	}

	/**
	 * Initializes the application.
	 *
	 * @param string $class   The application class name.
	 * @param mixed  $config  Application configuration.
	 */
	private static function createApplication(string $class, $config = null)
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
		return self::createApplication('Axm\\Console\\ConsoleApplication', $config);
	}

	/**
	 * Gets the application singleton or a registered service instance.
	 *
	 * @param string|null $alias    The service alias to get or register.
	 * @param Closure|null $callback The callback function to create the service.
	 * @param bool $shared           Whether the service instance should be shared across requests.
	 * @return mixed|null The application singleton, the requested service instance, or null if not found.
	 * @throws InvalidArgumentException if attempting to register a service with an existing alias.
	 * @throws RuntimeException if attempting to get a non-existent service.
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
	 * Returns the app instance.
	 */
	private static function getSingleton(): ?Application
	{
		return self::$_app;
	}

	/**
	 * Returns the environment variable.
	 */
	public static function getEnvironment()
	{
		return static::$_environment;
	}

	/**
	 * Checks if the application is running in production mode.
	 */
	public static function isProduction(): bool
	{
		return static::$_environment === ENV_PRODUCTION;
	}

	/**
	 * Returns an array with basic performance statistics.
	 */
	public static function getPerformanceStats(): array
	{
		return [
			'startTime' => AXM_BEGIN_TIME,
			'totalTime' => (microtime(true) - AXM_BEGIN_TIME),
		];
	}

	/**
	 * Stores the application instance in the class static member.
	 * 
	 * This method helps implement a singleton pattern for Application.
	 * Repeated invocation of this method or the Application constructor
	 * will cause an exception to be thrown.
	 * To retrieve the application instance, use app().
	 * @param Application $app The application instance.
	 * @throws AxmException if multiple application instances are registered.
	 */
	public static function setApplication(Application $app): void
	{
		if (self::$_app !== null) {
			throw new AxmException(self::t('axm', 'Axm application can only be created once.'));
		}

		self::$_app = $app;
	}

	/**
	 * Translation method that allows translating messages in the system.
	 *
	 * @param string $category The translation category.
	 * @param string $message  The original message.
	 * @param array $params    Parameters to replace in the original message.
	 * @param bool $isHtml     Whether the message should be treated as HTML.
	 * @return string The translated message.
	 */
	public static function t($category, $message, $params = [], $language = null, bool $isHtml = true)
	{
		$source = ($category === 'axm') ? 'coreMessages' : 'messages';

		$message = vsprintf($message, array_map(function ($value) use ($isHtml) {
			return !$isHtml ? $value : '<b>' . $value . '</b>';
		}, $params));

		return $message;
	}

	/**
	 * Get the installed version of the Axm Framework from composer.json.
	 *
	 * @return string|null The version of the Axm Framework or null if not found.
	 */
	public static function getVersion()
	{
		if (isset($version)) {
			return self::$version;
		}

		self::$version = getLatestPackageVersion('axm/framework');
		return self::$version;
	}
}
