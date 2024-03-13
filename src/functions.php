<?php

declare(strict_types=1);

use Lang\Lang;
use Views\View;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


if (!function_exists('config')) {
    /**
     * Get the configuration value for a given key.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config(string $key = null, string $value = null)
    {
        if (is_null($key)) {
            return new Config;
        }

        if (!is_null($key) && !is_null($value)) {
            Config::set($key, $value);
            return;
        }

        $config = Config::get($key);
        return $config;
    }
}

if (!function_exists('app')) {
    /**
     * Return the Axm instance
     */
    function app(?string $alias = null, $value = null): object
    {
        static $instance;
        $instance ??= new App;

        if ($alias === null) {
            return $instance;
        }

        if ($alias !== null && $value === null) {
            return $instance->$alias;
        }

        if ($alias !== null && $value !== null) {
            return $instance->$alias = $value;
        }

        return $instance;
    }
}

function env(string $params, string|bool $default = null)
{
    $env = Env::get($params, $default);
    return $env ?? $default;
}

if (!function_exists('is_cli')) {

    function is_cli(): bool
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) return true;

        return !isset($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['REQUEST_METHOD']);
    }
}

if (!function_exists('extend')) {

    /**
     * Extend the current View template with a layout.
     *
     * @param string $layout The name of the layout template to extend with.
     * @return void
     */
    function extend(string $layout)
    {
        return Views\View::extend($layout);
    }
}

if (!function_exists('memoize')) {

    /**
     * Memoize the result of a callable function.
     *
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

if (!function_exists('view')) {

    /**
     * Render and display a View template.
     *
     * @param string $view    The name of the View template to render.
     * @param mixed  $params  Optional data to pass to the View template (default is null).
     * @param bool   $buffer  If true, the output is buffered; if false, it's immediately displayed (default is true).
     * @param string $ext     The file extension of the View template (default is '.php').
     * @return void
     */
    function view(string $view, string|array $params = null, bool $show = false, bool $withLayout = false, string $ext = '.php')
    {
        return app('view', new View)
            ->render($view, $ext)
            ->withData($params)
            ->withLayout($withLayout)
            ->get();
    }
}

if (!function_exists('section')) {

    /**
     * Begin a new named section in a View template.
     *
     * @param string $name The name of the section being started.
     * @return void
     */
    function section(string $name)
    {
        return Views\View::section($name);
    }
}

if (!function_exists('endSection')) {

    /**
     * End the current section in a View.
     * @return void
     */
    function endSection()
    {
        return Views\View::endSection();
    }
}

if (!function_exists('partials')) {

    function partials(string $partial_name,  array $data = [])
    {
        $partialsPath = config('paths.partialsPath'); // Make sure we have our paths set up!

        $partial_file = $partialsPath . DIRECTORY_SEPARATOR . $partial_name . '.php';
        $partials = app()->view->file($partial_file, $data);
        return $partials;
    }
}

if (!function_exists('cleanInput')) {

    /**
     * Sanitizes and cleans input data to prevent XSS attacks.
     *
     * @param mixed $data The data to be cleaned.
     * @return mixed The cleaned data.
     */
    function cleanInput($data)
    {
        return match (true) {
            is_array($data)  => array_map('cleanInput', $data),
            is_object($data) => cleanInput((array) $data),
            is_email($data)  => filter_var($data, FILTER_SANITIZE_EMAIL),
            is_url($data)    => filter_var($data, FILTER_SANITIZE_URL),
            is_ip($data)     => filter_var($data, FILTER_VALIDATE_IP),
            is_string($data) => preg_replace('/[\x00-\x1F\x7F]/u', '', filter_var(trim($data), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES)),
            is_int($data)    => filter_var($data, FILTER_SANITIZE_NUMBER_INT),
            is_float($data)  => filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            is_bool($data)   => filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            is_null($data)   => settype($data, 'NULL'),

            default => filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS),
        };
    }
}

if (!function_exists('is_email')) {

    /**
     * Check if a string is a valid email address.
     *
     * @param string $email The email address to be checked.
     * @return bool True if it's a valid email address, false otherwise.
     */
    function is_email($email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_url')) {

    /**
     * Check if a string is a valid URL.
     *
     * @param string $url The URL to be checked.
     * @return bool True if it's a valid URL, false otherwise.
     */
    function is_url($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('is_ip')) {

    /**
     * Check if a string is a valid IP address.
     *
     * @param string $ip The IP address to be checked.
     * @return bool True if it's a valid IP address, false otherwise.
     */
    function is_ip($ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

if (!function_exists('show')) {

    /**
     * Display or return data.
     *
     * @param mixed  $data   The data to be displayed or returned (default is null).
     * @param bool   $return If true, the data is returned as a string; if false, it's echoed (default is false).
     * @return mixed If $return is true, the data is returned as a string; otherwise, it's echoed.
     */
    function show($data = null, bool $return = false): string
    {
        $output = $data ?? '';
        if ($return) return $output;

        echo $output . PHP_EOL;
        return '';
    }
}

if (!function_exists('cVar')) {

    /**
     * Copies the value of an original variable, removes the original variable, 
     * and returns the copied value.
     *
     * @param mixed $var The variable whose value you want to copy and remove.
     * @return mixed The copied value of the original variable.
     */
    function cVar($var)
    {
        $result = $var;

        unset($var);
        return $result;
    }
}

if (!function_exists('randomId')) {

    /**
     * Checks if the 'randomId' function exists and defines it if not.
     *
     * @param int $size The size of the random identifier (default is 50).
     * @return int A randomly generated identifier based on CRC32 hashing.
     */
    function randomId($size = 50)
    {
        $randomBytesHex = bin2hex(random_bytes($size));
        return crc32($randomBytesHex);
    }
}

if (!function_exists('lang')) {

    /**
     * Checks if the 'lang' function exists and defines it if not.
     *
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
        return app()
            ->session
            ->setFlash($type, $message);
    }
}

if (!function_exists('generateUrl')) {

    /**
     * This code checks if a function called "urlSite" exists. 
     **/
    function generateUrl(string $dir = ''): string
    {
        $url = baseUrl($dir);
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
     * Returns the full site root. 
     **/
    function baseUrl(string $path = '/'): string
    {
        return app()->router->url($path);
    }
}

if (!function_exists('asset')) {

    /**
     * Generate the URL for an asset.
     *
     * @param string $path The relative path to the asset.
     * @param string|null $basePath The base URL of the application (optional). If not provided, it uses '/resources/assets/' as the default.
     * @return string The full URL to the asset.
     */
    function asset(string $path, ?string $basePath = null): string
    {
        $basePath  = $basePath ?? 'app/resources/assets/';
        $base = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        return baseUrl($base);
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
        return app()
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
        return app()
            ->router
            ->getUri();
    }
}

if (!function_exists('post')) {

    /**
     * Returns all data sent by the POST method. 
     * e.g.: post(); || post('name'); 
     **/
    function post($key = null)
    {
        if (!($post = app()->request->post())) return false;

        if ($key !== null) {
            return htmlspecialchars($post[$key], ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars($post, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isLogged')) {

    /**
     * Check if a user is logged in.
     * @return bool True if a user is logged in; false otherwise.
     */
    function isLogged()
    {
        return app()->isLogged();
    }
}

if (!function_exists('old')) {

    /**
     *  Used to show again if the data sent in 
     *  html elements (input, select, textarea, etc) sent by the POST method exist. 
     * e.g.: old('name); **/
    function old(string $value)
    {
        $input = app()->request->post();
        return (isset($input[$value]) && !empty($input[$value])) ? $input[$value] : '';
    }
}

if (!function_exists('checkSession')) {

    /**
     * Check whether a session is defined or not
     */
    function checkSession(string $key): bool
    {
        return app()->session->get($key);
    }
}

if (!function_exists('getInfoUser')) {

    /**
     * Returns any user specific info, the name of the class from the ConfigApp 
     * 
     * @param string $user
     * @param string $value
     */
    function getInfoUser(string $user, string $value)
    {
        $userClass = config('app.userClass');
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
        return app()->user->{$value};
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
     * @param string|null $string (Optional) The input string to operate on.
     * @return Stringable|object Returns a Stringable instance if a string argument is provided.
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
     * @return Fluent\Fluent An instance of the Fluent class for method chaining.
     */
    function __($obj)
    {
        return new Fluent\Fluent($obj);
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
     * @param string|array $helpers Names of the helpers to load, separated by spaces, commas, dots or an array.
     * @param string|null $customPath The path to custom helper files. If not provided, custom helpers are not loaded.
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

        $config = config('paths');

        // Define paths for helper files
        $appPath = $config['helpersPath'];            // Default application path
        $axmHelpersPath = $config['helpersAxmPath']; // Axm system path

        // Load custom helpers from the provided path
        if ($customPath) {
            $customPath = rtrim($customPath, '/'); // Ensure the path does not end with a slash
        }

        foreach ($helpers as $helper) {
            $helper = trim($helper) . $separator . 'helper.php';

            // Try to load the helper from the custom path first
            if ($customPath) {
                $customHelperFile = $customPath . DIRECTORY_SEPARATOR . $helper;
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
            throw new Exception("The helper '$axmHelperFile' does not exist in any of the specified paths.");
        }

        return true;
    }
}

if (!function_exists('getRouteParams')) {

    /**
     * Get the route parameters from the current request.
     *
     * This function retrieves the route parameters from the current HTTP request. 
     * @return array An associative array containing the route parameters.
     */
    function getRouteParams()
    {
        return app()
            ->request
            ->getRouteParams();
    }
}

if (!function_exists('getUri')) {

    /**
     * Get the URI (Uniform Resource Identifier) of the current request.
     * @return string The URI of the current request as a string.
     */
    function getUri()
    {
        return app()
            ->request
            ->getUri();
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
