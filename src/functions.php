<?php

declare(strict_types=1);

use Lang\Lang;
use Views\View;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


if (!function_exists('config')) {
    /**
     * Get the configuration value for a given key.
     */
    function config(string $key = null, mixed $value = null)
    {
        if (null === $key) {
            return new Config;
        }

        if (null !== $value) {
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

        if (null === $alias) {
            return $instance;
        }

        if (null !== $value) {
            return $instance->$alias = $value;
        }

        return $instance->$alias;
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
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true))
            return true;

        return !isset($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['REQUEST_METHOD']);
    }
}

if (!function_exists('extend')) {

    /**
     * Extend the current View template with a layout.
     * @return void
     */
    function extend(string $layout)
    {
        return Views\View::extend($layout);
    }
}

if (!function_exists('view')) {

    /**
     * Render and display a View template.
     */
    function view(string $view, string|array $params = null, bool $show = false, bool $withLayout = false, string $ext = '.php'): ?string
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
     */
    function section(string $name): void
    {
        Views\View::section($name);
    }
}

if (!function_exists('endSection')) {

    /**
     * End the current section in a View.
     */
    function endSection(): void
    {
        Views\View::endSection();
    }
}

if (!function_exists('partials')) {

    function partials(string $partial_name, array $data = [])
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
     */
    function cleanInput(mixed $data): mixed
    {
        return match (true) {
            is_array($data) => array_map('cleanInput', $data),
            is_object($data) => cleanInput((array) $data),
            is_email($data) => filter_var($data, FILTER_SANITIZE_EMAIL),
            is_url($data) => filter_var($data, FILTER_SANITIZE_URL),
            is_ip($data) => filter_var($data, FILTER_VALIDATE_IP),
            is_string($data) => preg_replace('/[\x00-\x1F\x7F]/u', '', filter_var(trim($data), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES)),
            is_int($data) => filter_var($data, FILTER_SANITIZE_NUMBER_INT),
            is_float($data) => filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            is_bool($data) => filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            is_null($data) => settype($data, 'NULL'),

            default => filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS),
        };
    }
}

if (!function_exists('is_email')) {

    /**
     * Check if a string is a valid email address.
     */
    function is_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_url')) {

    /**
     * Check if a string is a valid URL.
     */
    function is_url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('is_ip')) {

    /**
     * Check if a string is a valid IP address.
     */
    function is_ip(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

if (!function_exists('show')) {

    /**
     * Display or return data.
     */
    function show(mixed $data = null, bool $return = false): string
    {
        $output = $data ?? '';
        if ($return)
            return $output;

        echo $output . PHP_EOL;
        return '';
    }
}

if (!function_exists('cVar')) {

    /**
     * Copies the value of an original variable, removes the original variable, 
     * and returns the copied value.
     */
    function cVar(mixed $var): mixed
    {
        $result = $var;

        unset($var);
        return $result;
    }
}

if (!function_exists('randomId')) {

    /**
     * Checks if the 'randomId' function exists and defines it if not.
     */
    function randomId(int $size = 50): int
    {
        $randomBytesHex = bin2hex(random_bytes($size));
        return crc32($randomBytesHex);
    }
}

if (!function_exists('lang')) {

    /**
     * Checks if the 'lang' function exists and defines it if not.
     */
    function lang(string $key, array $args = []): string
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
     * This function is used to set flash messages in an application.
     */
    function setFlash(string $type, string $message): void
    {
        app()
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
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException(sprintf('Invalid URL: %s', $url));
        }

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
     */
    function asset(string $path, ?string $basePath = null): string
    {
        $basePath = $basePath ?? 'app/resources/assets/';
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
        if (!($post = app()->request->post()))
            return false;

        if ($key !== null) {
            return htmlspecialchars($post[$key], ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars($post, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isLogged')) {

    /**
     * Check if a user is logged in.
     */
    function isLogged(): bool
    {
        return app()->isLogged();
    }
}

if (!function_exists('old')) {

    /**
     *  Used to show again if the data sent in 
     *  html elements (input, select, textarea, etc) sent by the POST method exist. 
     * e.g.: old('name); **/
    function old(string $value): string
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

if (!function_exists('str')) {

    /**
     * Create a new string helper instance or operate on a string.
     * @return Stringable|object Returns a Stringable instance if a string argument is provided.
     */
    function str(?string $string = null)
    {
        if (is_null($string)) {
            // Return a new class instance for chaining string methods
            return new class {
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
     * @return Axm\Fluent\Fluent An instance of the Fluent class for method chaining.
     */
    function __($obj)
    {
        return new Axm\Fluent\Fluent($obj);
    }
}

if (!function_exists('to_object')) {

    /**
     * Converts the element into an object
     */
    function to_object(array &$array): stdClass
    {
        return json_decode(json_encode($array));
    }
}

if (!function_exists('helpers')) {

    /**
     * Load one or multiple helpers.
     */
    function helpers(string|array $helpers, ?string $customPath = null, string $separator = '_'): bool
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
                    require_once ($customHelperFile);
                    continue;
                }
            }

            // Try to load the helper from the default application path
            $appHelperFile = $appPath . DIRECTORY_SEPARATOR . $helper;
            if (is_file($appHelperFile)) {
                require_once ($appHelperFile);
                continue;
            }

            // Try to load the helper from the Axm system path
            $axmHelperFile = $axmHelpersPath . DIRECTORY_SEPARATOR . $helper;
            if (is_file($axmHelperFile)) {
                require_once ($axmHelperFile);
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
     * This function retrieves the route parameters from the current HTTP request. 
     */
    function getRouteParams(): array
    {
        return app()
            ->request
            ->getRouteParams();
    }
}

if (!function_exists('getUri')) {

    /**
     * Get the URI (Uniform Resource Identifier) of the current request.
     */
    function getUri(): string
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
     */
    function class_basename($class): string
    {
        return is_object($class)
            ? basename(str_replace('\\', '/', get_class($class)))
            : (string) basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('camelCase')) {

    /**
     * Converts a string in "date_format" style to camelCase format.
     */
    function camelCase(string $str): string
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
     */
    function esc(string $text): string
    {
        $encodedText = htmlspecialchars($text, ENT_QUOTES, config('app.charset') ?? 'UTF-8');
        $brText = nl2br($encodedText);
        return $brText;
    }
}
