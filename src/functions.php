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
     * Returns the Axm instance or a value from the instance by alias.
     */
    function app(string $alias = null, mixed $value = null): object
    {
        $axmInstance = Axm::getApp();

        if (null === $alias) {
            return $axmInstance;
        }

        if (null !== $value) {
            $axmInstance->$alias = $value;
        }

        return $axmInstance->$alias;
    }
}

if (!function_exists('env')) {

    function env(string $params, string|bool $default = null): bool|null|string
    {
        return Env::get($params, $default);
    }
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
            ->withData($params)
            ->withLayout($withLayout)
            ->render($view, $ext)
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

if (!function_exists('tap')) {
    /**
     * Do some operation after value get.
     */
    function tap(mixed $value, callable $callable): mixed
    {
        $callable($value);

        return $value;
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
     */
    function baseUrl(string $path = '/'): string
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        $path = trim($basePath . '/' . $path, '/');

        return "{$scheme}://{$host}/{$path}";
    }
}

if (!function_exists('asset')) {

    /**
     * Generate the URL for an asset.
     */
    function asset(string $path, string $basePath = 'app/resources/assets/'): string
    {
        $basePath = rtrim($basePath, '/');
        $path = ltrim($path, '/');

        return baseUrl("{$basePath}/{$path}");
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
    function redirect(string $url)
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
     * Returns the previously submitted value of a form field with the given name, if it exists.
     */
    function old(string $fieldName): string
    {
        $submittedValues = app()->request->post();
        return $submittedValues[$fieldName] ?? '';
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
     * Returns a specific info of the user who has successfully logged in.
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
     */
    function str(?string $string = null): Stringable
    {
        return is_null($string) ? new class {
            public function __call(string $method, array $arguments)
            {
                return Str::$method(...$arguments);
            }
        } : Str::of($string);
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
    function helpers($helpers, ?string $customPath = null, string $separator = '_'): bool
    {
        $helpers = is_string($helpers) ? preg_split('/[\s,\.]+/', $helpers) : $helpers;
        $customPath = $customPath ? rtrim($customPath, DIRECTORY_SEPARATOR) : null;

        $config = config('paths');
        $appHelpersPath = $config['helpersPath'];
        $axmHelpersPath = $config['helpersAxmPath'];

        foreach ($helpers as $helper) {
            $helperFile = "$helper$separator" . 'helper.php';

            if ($customPath && is_file($customPath . DIRECTORY_SEPARATOR . $helperFile)) {
                require_once "$customPath/$helperFile";
                continue;
            }

            $paths = [$appHelpersPath, $axmHelpersPath];
            foreach ($paths as $path) {
                if (is_file("$path/$helperFile")) {
                    require_once "$path/$helperFile";
                    continue 2;
                }
            }

            throw new Exception("The helper '$helperFile' does not exist in any of the specified paths.");
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
        return (string) is_object($class)
            ? basename(str_replace('\\', '/', get_class($class)))
            : basename(str_replace('\\', '/', $class));
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

if (!function_exists('csrf')) {
    /**
     * Generates a hidden input field with the CSRF token.
     */
    function csrf(): string
    {
        return '<input type="hidden" name="csrfToken" value="' . app()->getCsrfToken() . '">';
    }

}