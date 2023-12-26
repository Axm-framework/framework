<?php

declare(strict_types=1);

use Axm\Lang\Lang;
use Axm\Views\View;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

if (!function_exists('extend')) {

	/**
	 * Extend the current View template with a layout.
	 *
	 * This function is used to extend the current View template with a layout template.
	 * It calls the 'extend' method of the 'View' class to specify the layout template to use.
	 * @param string $layout The name of the layout template to extend with.
	 * @return void
	 */
	function extend(string $layout)
	{
		// Call the 'extend' method of the 'View' class to specify the layout template.
		return View::extend($layout);
	}
}

if (!function_exists('memoize')) {

	/**
	 * Memoize the result of a callable function.
	 *
	 * This function is used to cache and reuse the results of a callable function for the same 
	 * set of arguments. It returns a new callable function that stores and retrieves results from 
	 * a cache based on argument values.
	 * @param callable $fn The callable function to memoize.
	 * @return callable A memoized version of the original callable function.
	 */
	function memoize(callable $fn): callable
	{
		$cache = [];

		// Return a new callable function that handles memoization.
		return function (...$args) use ($fn, &$cache) {
			// Generate a unique key based on the serialized arguments.
			$key = sha1(serialize($args));

			// Check if the result is already cached; if not, compute and cache it.
			return $cache[$key] ?? ($cache[$key] = $fn(...$args));
		};
	}
}

if (!function_exists('raxm')) {

	/**
	 * Initialize and use a Raxm component.
	 *
	 * This function is used to initialize and use a Raxm component within the application.
	 * @param string $component The name of the Raxm component to initialize and use.
	 * @return mixed The result of initializing and using the specified Raxm component.
	 */
	function raxm(string $component)
	{
		// Get the Raxm instance from the application.
		$raxm = app('raxm');

		// Initialize and use the specified Raxm component.
		return $raxm::initializeComponent($component);
	}
}

if (!function_exists('raxmScripts')) {

	/**
	 * Enable the use of Raxm scripts and assets in the View.
	 *
	 * This function is used to enable the inclusion of Raxm scripts and assets in a View template.
	 * It sets a flag in the View class to indicate that Raxm assets should be included.
	 * @return bool True to enable Raxm scripts and assets in the View; false otherwise.
	 */
	function raxmScripts()
	{
		// Set a flag in the View class to enable Raxm scripts and assets.
		return View::$raxmAssets = true;
	}
}

if (!function_exists('view')) {

	/**
	 * Render and display a View template.
	 *
	 * This function is used to render and display a View template within the application.
	 * @param string $view    The name of the View template to render.
	 * @param mixed  $params  Optional data to pass to the View template (default is null).
	 * @param bool   $buffer  If true, the output is buffered; if false, 
	 * it's immediately displayed (default is true).
	 * @param string $ext     The file extension of the View template (default is '.php').
	 * @return void
	 */
	function view(string $view, array $params = [], bool $buffer = true, string $ext = '.php')
	{
		// Render the View template using the provided parameters.
		$renderedView = Axm::app()->controller->renderView($view, $params, $buffer, $ext);

		// Display the rendered View template using the 'show' function.
		return show($renderedView);
	}
}

if (!function_exists('section')) {

	/**
	 * Begin a new named section in a View template.
	 *
	 * This function is used to start a new named section within a View template.
	 * It calls the 'section' method of the 'View' class, allowing you to define content
	 * that can be yielded or included in other parts of the template.
	 * @param string $name The name of the section being started.
	 * @return void
	 */
	function section(string $name)
	{
		// Call the 'section' method of the 'View' class to begin a new named section.
		return View::section($name);
	}
}

if (!function_exists('endSection')) {

	/**
	 * End the current section in a View.
	 *
	 * This function is used to mark the end of a section within a View template.
	 * It calls the 'endSection' method of the 'View' class.
	 * @return void
	 */
	function endSection()
	{
		// Call the 'endSection' method of the 'View' class to mark the end of the section.
		return View::endSection();
	}
}

if (!function_exists('env')) {

	/**
	 * Allows user to retrieve values from the environment
	 * 
	 * variables that have been set. Especially useful for
	 * retrieving values set from the .env file for
	 * use in config files.
	 * @param string|null $default
	 * @return mixed
	 */
	function env(string $key, $default = null)
	{
		static $cache = [];

		// Check if the key is already in the cache
		if (isset($cache[$key])) return $cache[$key];

		// Try to get the value from different sources
		$value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key) ?? null;

		// Not found? Return the default value
		if ($value === null) return $default;

		// Store the value in the cache and return it
		return $cache[$key] = $value;
	}
}

if (!function_exists('cleanInput')) {

	/**
	 * Sanitizes and cleans input data to prevent XSS attacks.
	 *
	 * @param mixed $data The data to be cleaned.
	 * @return mixed The cleaned data.
	 */
	function cleanInput($data) {
		return match (true) {
			is_array($data)  => array_map('cleanInput', $data),
			is_object($data) => cleanInput((array)$data),
			is_string($data) => filter_var(trim($data), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES),
			is_int($data)    => filter_var($data, FILTER_SANITIZE_NUMBER_INT),
			is_float($data)  => filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			is_bool($data)   => filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
			
			default => $data,
		};
	}
}

if (!function_exists('show')) {

	/**
	 * Display or return data.
	 *
	 * This function is used to either display data or return it as a string
	 * based on the provided parameters.
	 * @param mixed  $data   The data to be displayed or returned (default is null).
	 * @param bool   $return If true, the data is returned as a string; if false, it's echoed (default is false).
	 * @return mixed If $return is true, the data is returned as a string; otherwise, it's echoed.
	 */
	function show($data = null, bool $return = false): string 
	{
	  $output = $data ?? '';
	  if($return) return $output; 
	
	  echo $output . PHP_EOL;
	  return '';
	}
}

if (!function_exists('cVar')) {

	/**
	 * Copies the value of an original variable, removes the original variable, 
	 * and returns the copied value.
	 *
	 * This function is primarily used for duplicating and removing variables 
	 * of types like $_COOKIE or $_SESSION.
	 * @param mixed $var The variable whose value you want to copy and remove.
	 * @return mixed The copied value of the original variable.
	 */
	function cVar($var)
	{
		$result = $var;

		// Unset the original variable to remove it.
		unset($var);
		return $result;
	}
}

if (!function_exists('randomId')) {

	/**
	 * Checks if the 'randomId' function exists and defines it if not.
	 *
	 * This function generates a random identifier of a specified size.
	 * @param int $size The size of the random identifier (default is 50).
	 * @return int A randomly generated identifier based on CRC32 hashing.
	 */
	function randomId($size = 50)
	{
		// Generate a random binary string of the specified size and convert it to hexadecimal.
		$randomBytesHex = bin2hex(random_bytes($size));

		// Calculate a CRC32 hash of the hexadecimal string to create a random identifier.
		return crc32($randomBytesHex);
	}
}

if (!function_exists('lang')) {

	/**
	 * Checks if the 'lang' function exists and defines it if not.
	 *
	 * This function is used for language localization and translation of messages.
	 * @param string $key The key representing the message to be translated.
	 * @return string The translated message, with optional placeholders replaced.
	 */
	function lang(string $key, array $args = [])
	{
		// Get an instance of Lang
		$lang = Lang::make();

		if (empty($args)) {
			return $lang->trans($key);
		}

		// Translate a key
		return $lang->trans($key, $args);
	}
}

if (!function_exists('setFlash')) {

	/**
	 * Checks if the 'setFlash' function exists and defines it if not.
	 *
	 * This function is used to set flash messages in an application.
	 * @param string $type    The type of flash message (e.g., 'success', 'error', 'info', etc.).
	 * @param string $message The message to be displayed as a flash message.
	 * @return void
	 */
	function setFlash(string $type, string $message)
	{
		// Calls the 'setFlash' method from the 'session' component of the Axm application.
		return Axm::app()
			->session
			->setFlash($type, $message);
	}
}

if (!function_exists('generateUrl')) {

	/**
	 * This code checks if a function called "urlSite" exists. 
	 * 
	 * If it does not exist, the code creates a function called "urlSite" that takes in one parameter, 
	 * a string called $dir. The function then sets the scheme and host variables to the request scheme 
	 * and http host from the server respectively. It then sets the path variable to the value of $dir 
	 * after trimming off any slashes at the end. It then creates an url variable by concatenating the 
	 * scheme, host and path variables. If this url is not valid, it throws an exception. Otherwise,
	 * it returns the url.
	 **/
	function generateUrl(string $dir = ''): string
	{
		$url = Axm::app()
			->request
			->createNewUrl($dir);

		// If the URL is not valid, throw an exception
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			throw new RuntimeException(sprintf('Invalid URL: %s', $url));
		}

		// Return the generated URL
		return $url;
	}
}

if (!function_exists('baseUrl')) {

	/**
	 *  Returns the full site root, if the $dir parameter is not passed it will show only the root 
	 *  if the path to a file is passed it will show it, this function is used to show paths primarily. 
	 * e.g.: baseUrl('assets/css/bootstrap.min.css');
	 **/
	function baseUrl(string $dir = ''): string
	{
		// If $dir is not empty, remove any forward-slashes or back-slashes from the beginning 
		// or end of the string, add a forward-slash to the end and assign it to $dir
		$dir = (!empty($dir)) ? rtrim($dir, '\/') . '/' : '';

		// Concatenate PUBLIC_PATH and $dir to form the full URL of the current site 
		// with the directory appended
		$url = generateUrl(trim("$dir/"));

		return $url;
	}
}

if (!function_exists('asset')) {

	/**
	 * Generate the URL of a resource using the path relative to the resource directory 
	 * 
	 * @param string $dirFile
	 * @return string
	 * @throws FileNotFoundException
	 */
	function asset(string $dirFile): string
	{
		$pathAssets = config('paths.assetsPath');
		$fullPath = rtrim($pathAssets, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($dirFile, DIRECTORY_SEPARATOR);

		if (!is_file($fullPath)) {
			throw new RuntimeException("File not found: $fullPath");
		}

		return baseUrl($fullPath);
	}

}

if (!function_exists('go')) {

	/**
	 * Used to go to a specific page, 
	 * e.g.: go('login'); 
	 **/
	function go(string $page = ''): string
	{
		$url = generateUrl($page);
		return $url;
	}
}

if (!function_exists('back')) {

	/**
	 *  Is used to go backwards, 
	 *  if $_SERVER['HTTP_REFERER'] is defined it goes backwards, 
	 *  otherwise it does a refresh 
	 *  e.g.: back('login'); **/
	function back()
	{
		$referer = $_SERVER['HTTP_REFERER'] ?? null;

		if (null !== $referer)
			return redirect($referer);

		return refresh();
	}
}

if (!function_exists('redirect')) {

	/**
	 * Redirect one page to another, 
	 * e.g.: redirect('login'); 
	 **/
	function redirect($url)
	{
		return Axm::app()
			->response
			->redirect($url);
	}
}

if (!function_exists('refresh')) {

	/**
	 * Redirects the request to the current URL
	 **/
	function refresh()
	{
		return Axm::app()
			->request
			->getUri();
	}
}

if (!function_exists('post')) {

	/**
	 * Returns all data sent by the POST method. 
	 * if no parameter is passed it shows all the element, if parameters are passed it 
	 * shows the specific element 
	 * e.g.: post(); || post('name'); 
	 **/
	function post($key = null)
	{
		if (!($post = Axm::app()->request->post())) return false;

		if ($key !== null) {
			return htmlspecialchars($post[$key], ENT_QUOTES, 'UTF-8');
		}

		return htmlspecialchars($post, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('isLogged')) {

	/**
	 * Check if a user is logged in.
	 *
	 * This function is used to determine whether a user is currently logged in within the application.
	 * It calls the 'isLogged' method of the 'Axm' application instance to perform the check.
	 * @return bool True if a user is logged in; false otherwise.
	 */
	function isLogged()
	{
		// Call the 'isLogged' method of the 'Axm' application instance to check if a user is logged in.
		return Axm::app()->isLogged();
	}
}

if (!function_exists('old')) {

	/**
	 *  Used to show again if the data sent in 
	 *  html elements (input, select, textarea, etc) sent by the POST method exist. 
	 * e.g.: old('name); **/
	function old(string $value)
	{
		$input = Axm::app()->request->post();
		return (isset($input[$value]) && !empty($input[$value])) ? $input[$value] : '';
	}
}

if (!function_exists('checkSession')) {

	/**
	 * Check whether a session is defined or not
	 */
	function checkSession(string $key): bool
	{
		return Axm::app()->session->get($key);
	}
}

if (!function_exists('getInfoUser')) {

	/**
	 * Returns any user specific info, the name of the class from the ConfigApp 
	 * 
	 * var public userClass   
	 * @param string $user
	 * @param string $value
	 */
	function getInfoUser(string $user, string $value)
	{
		$userClass = Axm::app()->config()->userClass;
		return $userClass::getInfoUser($user, $value);
	}
}

if (!function_exists('getUser')) {

	/**
	 * Returns a specific info of the user who 
	 * has successfully logged in.
	 */
	function getUser(string $value = null)
	{
		return Axm::app()->user->{$value};
	}
}

if (!function_exists('app')) {

	/**
	 * Resolve or retrieve an instance of the Axm class.
	 *
	 * @param string|null $alias  (Optional) An alias for the instance to be retrieved.
	 * @param Closure|null $callback  (Optional) A closure (anonymous function) to construct
	 * @param bool $shared  (Optional) Indicates whether the instance should be shared (singleton) or not.
	 * @return mixed If an alias is provided, it returns the instance associated with that alias.
	 *               If no alias is provided, it returns an instance of the Axm class.
	 * @throws Exception If attempting to resolve an instance that is not registered and no callback is provided.
	 */
	function app($alias = null, Closure $callback = null, bool $shared = false)
	{
		return Axm::app($alias, $callback, $shared);
	}
}

if (!function_exists('now')) {

	/**
	 * Get the current date and time using the Carbon library.
	 * @return \Carbon\Carbon A Carbon instance representing the current date and time.
	 */
	function now()
	{
		return Carbon::now();
	}
}

if (!function_exists('once')) {

	/**
	 * Call a function only once.
	 *
	 * @param callable $fn The function to be executed.
	 * @return callable A closure that wraps the provided function and ensures it is executed only once.
	 */
	function once($fn)
	{
		$hasRun = false;

		return function (...$params) use ($fn, &$hasRun) {
			if ($hasRun) return;

			$hasRun = true;

			return $fn(...$params);
		};
	}
}

if (!function_exists('str')) {

	/**
	 * Create a new string helper instance or operate on a string.
	 *
	 * If no argument is provided, returns a new instance of a string helper class
	 * that allows chaining string manipulation methods.
	 * If a string argument is provided, creates a Stringable instance for the given string.
	 * @param string|null $string (Optional) The input string to operate on.
	 * @return Stringable|object Returns a Stringable instance if a string argument is provided.
	 *    If no argument is provided, returns an instance of a helper class
	 *    for chaining string manipulation methods.
	 */
	function str($string = null)
	{
		if (is_null($string)) {
			// Return a new class instance for chaining string methods
			return new class
			{
				public function __call($method, $params)
				{
					// Delegate method calls to the Str class
					return Str::$method(...$params);
				}
			};
		}
		// Return a Stringable instance for the provided string
		return Str::of($string);
	}
}

if (!function_exists('__')) {

	/**
	 * Create a Fluent instance for method chaining.
	 *
	 * This function is used to create a Fluent instance, allowing for method chaining
	 * on the provided object. It enhances the readability and expressiveness of code by enabling
	 * a sequence of method calls on the object.
	 * @param object $obj The object on which method chaining is desired.
	 * @return Axm\Fluent\Fluent An instance of the Fluent class for method chaining.
	 */
	function __($obj)
	{
		// Return a new instance of the FluentInterface class for method chaining
		return new Axm\Fluent\Fluent($obj);
	}
}

if (!function_exists('reflect')) {

	/**
	 * Access and manipulate non-public properties and methods of an object using reflection.
	 *
	 * @param object $obj The object to be reflect.
	 * @return object An object with enhanced access to non-public members of $obj.
	 * @throws InvalidArgumentException If $obj is not a valid object.
	 */
	function reflect($obj)
	{
		return new class($obj)
		{
			private $obj;
			private $reflected;

			public function __construct($obj)
			{
				$this->obj = $obj;
				$this->reflected = new ReflectionClass($obj);
			}

			public function &__get($name)
			{
				$getProperty = function & () use ($name) {
					return $this->{$name};
				};

				$getProperty = $getProperty->bindTo($this->obj, get_class($this->obj));

				return $getProperty();
			}

			public function __set($name, $value)
			{
				$setProperty = function () use ($name, &$value) {
					$this->{$name} = $value;
				};

				$setProperty = $setProperty->bindTo($this->obj, get_class($this->obj));

				$setProperty();
			}

			public function __call($name, $params)
			{
				if (!$this->reflected->hasMethod($name)) {
					throw new RuntimeException("Method '{$name}' not found.");
				}

				$method = $this->reflected->getMethod($name);
				$method->setAccessible(true);

				return $method->invoke($this->obj, ...$params);
			}
		};
	}
}

if (!function_exists('class_uses_recursive')) {

	/**
	 * Returns all traits used by a class, its parent classes and trait of their traits.
	 *
	 * @param  object|string  $class
	 * @return array
	 */
	function class_uses_recursive($class)
	{
		if (is_object($class)) {
			$class = get_class($class);
		}

		$results = [];

		foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
			$results += trait_uses_recursive($class);
		}

		return array_unique($results);
	}
}

if (!function_exists('trait_uses_recursive')) {

	/**
	 * Returns all traits used by a trait and its traits.
	 *
	 * @param  string  $trait
	 * @return array
	 */
	function trait_uses_recursive($trait)
	{
		$traits = class_uses($trait) ?: [];

		foreach ($traits as $trait) {
			$traits += trait_uses_recursive($trait);
		}

		return $traits;
	}
}

if (!function_exists('config')) {
	/**
	 * Get the configuration value for a given key.
	 *
	 * @param  string|null  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function config(string $key = null)
	{
		$config = Axm\BaseConfig::make();

		if (is_null($key)) return $config;

		return $config->get($key);
	}
}

if (!function_exists('timeExec')) {

	/**
	 * Measure the execution time of a callback function in milliseconds.
	 *
	 * This function allows you to measure the execution time of a provided callback function
	 * in milliseconds. It can be useful for performance profiling and optimization.
	 * @param callable $callback The callback function to be executed and measured.
	 * @param int $precision (Optional) The number of decimal places in the result. Default is 5.
	 * @return float The execution time of the callback function in milliseconds.
	 * @throws InvalidArgumentException If an invalid precision value (less than zero) is provided.
	 * @throws RuntimeException If an exception is thrown during the execution of the callback.
	 *
	 * @example
	 * // Measure the execution time of a callback function
	 * $executionTime = timeExec(
	 *     fn () => app()->request->getCurrentUrl()
	 * );
	 */
	function timeExec(callable $callback, int $precision = 5): float
	{
		if ($precision < 0) {
			throw new InvalidArgumentException('Precision must be greater than or equal to zero');
		}

		$startTime = microtime(true);
		$exception = null;

		try {
			$callback();
		} catch (RuntimeException $e) {
			$exception = $e;
		}

		$endTime = microtime(true);
		$executionTimeMs = round(($endTime - $startTime) * 1000, $precision);

		if ($exception) {
			throw new RuntimeException("An exception occurred: {$exception->getMessage()}");
		}

		return (float) $executionTimeMs;
	}
}

if (!function_exists('to_object')) {

	/**
	 * Converts the element into an object
	 *
	 * @param [type] $array
	 * @return void
	 */
	function to_object($array)
	{
		return json_decode(json_encode($array));
	}
}

if (!function_exists('helpers')) {

	/**
	 * Load one or multiple helpers.
	 *
	 * @param string|array $helpers Names of the helpers to load, separated by spaces, 
	 * commas, dots or an array.
	 * @param string|null $customPath The path to custom helper files. If not provided, 
	 * custom helpers are not loaded.
	 * @return bool True if all helpers were loaded successfully, false otherwise.
	 * @throws HelperNotFoundException If a specified helper file does not exist in the given paths.
	 */
	function helpers($helpers, string $customPath = null, string $separator = '_')
	{
		// Convert $helpers to an array if it's a string and split by spaces, commas, or dots
		if (is_string($helpers)) {
			$helpers = preg_split('/[\s,\.]+/', $helpers);
		} elseif (!is_array($helpers)) {
			throw new InvalidArgumentException('The $helpers variable must be an array.');
		}

		// Define paths for helper files
		$appPath = config('paths.helpersPath'); // Default application path
		$axmHelpersPath = config('paths.helpersAxmPath'); // Axm system path

		// Load custom helpers from the provided path
		if ($customPath) {
			$customPath = rtrim($customPath, '/'); // Ensure the path does not end with a slash
		}

		foreach ($helpers as $helper) {
			$helper = trim($helper) . $separator . 'helper.php';

			// Try to load the helper from the custom path first
			if ($customPath) {
				$customHelperFile = "$customPath/$helper";
				if (is_file($customHelperFile)) {
					require_once($customHelperFile);
					continue;
				}
			}

			// Try to load the helper from the default application path
			$appHelperFile = $appPath . DIRECTORY_SEPARATOR . $helper;
			if (is_file($appHelperFile)) {
				require_once($appHelperFile);
				continue;
			}

			// Try to load the helper from the Axm system path
			$axmHelperFile = $axmHelpersPath . DIRECTORY_SEPARATOR . $helper;
			if (is_file($axmHelperFile)) {
				require_once($axmHelperFile);
				continue;
			}

			// If the helper is not found in any of the specified locations, throw an exception
			throw new Exception("The helper '$helper' does not exist in any of the specified paths.");
		}

		return true;
	}
}

if (!function_exists('getRouteParams')) {

	/**
	 * Get the route parameters from the current request.
	 *
	 * This function retrieves the route parameters from the current HTTP request. 
	 * Route parameters are typically used to
	 * capture values from the URL in a structured way and are commonly used in routing 
	 * systems to determine the action to
	 * be taken based on the requested URL.
	 * @return array An associative array containing the route parameters.
	 */
	function getRouteParams()
	{
		return Axm::app()
			->request
			->getRouteParams();
	}
}

if (!function_exists('getUri')) {

	/**
	 * Get the URI (Uniform Resource Identifier) of the current request.
	 *
	 * This function retrieves the URI of the current HTTP request. 
	 * The URI represents the unique identifier for the requested
	 * resource and typically includes the scheme, host, path, query parameters,
	 * and fragment identifier.
	 * @return string The URI of the current request as a string.
	 */
	function getUri()
	{
		return Axm::app()
			->request
			->getUri();
	}
}

if (!function_exists('logger')) {

	/**
	 * Log a message with a specified log level and optionally output it.
	 *
	 * This function is used to log messages with various log levels, 
	 * such as 'debug', 'info', 'warning', 'error', etc.
	 * It can also optionally output the log message. 
	 * The log messages are formatted with a timestamp and written to a log file.
	 * @param string $message The message to be logged.
	 * @param string $level (Optional) The log level (e.g., 'debug', 'info', 'warning').
	 * @param bool $output (Optional) Whether to output the log message to the console.
	 * @return bool True if the message was logged successfully, false otherwise.
	 */
	function logger(string $message, string $level = 'debug', bool $output = false)
	{
		$levels           = ['debug', 'import', 'info', 'success', 'warning', 'error'];
		$dateTime         = date('d-m-Y H:i:s');
		$level            = in_array($level, $levels) ? $level : 'debug';
		$formattedMessage = '[' . strtoupper($level) . ']' . $dateTime - $message;
		$logFilePath      = config('paths.logsPath') . DIRECTORY_SEPARATOR . 'axm_log.log';

		if (!file_exists($logFilePath)) {
			setFlash('error', sprintf('The log file does not exist at %s', $logFilePath));
			return false;
		}

		if (!$fileHandle = fopen($logFilePath, 'a')) {
			setFlash('error', sprintf('Cannot open file at %s', $logFilePath));
			return false;
		}

		fwrite($fileHandle, "$formattedMessage\n");
		fclose($fileHandle);

		if ($output) {
			print "$formattedMessage\n";
		}

		return true;
	}
}

if (!function_exists('class_basename')) {

	/**
	 * Class Basename
	 *
	 * Returns the base name of a class, effectively stripping the namespace.
	 * @param  mixed $class Either an object or a string with the class name.
	 * @return string
	 */
	function class_basename($class)
	{
		return is_object($class)
			? basename(str_replace('\\', '/', get_class($class)))
			: (string) basename(str_replace('\\', '/', $class));
	}
}

if (!function_exists('asset')) {

	/**
	 * Generate the URL for an asset.
	 *
	 * This function takes a relative path to an asset and combines it with the base URL of the application,
	 * producing the full URL to the asset. It ensures proper handling of directory separators.
	 *
	 * @param string $path The relative path to the asset.
	 * @param string|null $basePath The base URL of the application (optional). If not provided, it uses an empty string.
	 * @return string The full URL to the asset.
	 */
	function asset(string $path, ?string $basePath = null): string
	{
		// Get the URL base of your application from the configuration or as you prefer.
		$baseUrl = $basePath ?? '';

		// Combines the base URL with the relative path of the resource.
		$url = rtrim($baseUrl, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);

		$fullUrl = baseUrl($url);
		return $fullUrl;
	}
}

if (!function_exists('camelCase')) {

	/**
	 * Converts a string in "date_format" style to camelCase format.
	 *
	 * @param string $str The input string in "date_format" style.
	 * @return string The input string converted to camelCase format.
	 */
	function camelCase($str)
	{
		$words = explode('_', $str);

		// Capitalize the first letter of each word (except the first one)
		for ($i = 1; $i < count($words); $i++) {
			$words[$i] = ucfirst($words[$i]);
		}
		// Join the words back together without spaces and in camelCase format
		$camelCaseStr = implode('', $words);

		return $camelCaseStr;
	}
}

if (!function_exists('esc')) {
	/**
	 * Escapes and formats a text string for safe display in HTML.
	 *
	 * This function combines HTML encoding and newline-to-break conversion.
	 * @param string $text The input text to be escaped and formatted.
	 * @return string The escaped and formatted text.
	 */
	function esc(string $text): string
	{
		$encodedText = htmlspecialchars($text, ENT_QUOTES, config('app.charset') ?? 'UTF-8');
		$brText = nl2br($encodedText);
		return $brText;
	}
}

if (!function_exists('getVersion')) {

	/**
	 * Get the latest version of a Composer package from Packagist.org.
	 *
	 * @param string $packageName The name of the Composer package.
	 * @return string|null The latest version of the package, or null if not found.
	 */
	function getVersion($packageName)
	{
		$url = "https://packagist.org/packages/{$packageName}.json";

		$response = file_get_contents($url);

		if ($response === false) {
			return null;
		}

		$data = json_decode($response, true);
		$versions = data_get($data, 'package.versions');

		if ($versions) {
			return max(@array_keys($versions));
		}

		return null;
	}
}
