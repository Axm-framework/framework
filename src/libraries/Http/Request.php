<?php

namespace Http;

// use Axm;
use Http\URI;
use RuntimeException;

/**
 * Class Request
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\HTTP
 */

class Request extends URI
{
    /**
     * routeParams
     * @var array
     */
    private array $routeParams = [];

    /**
     * headers
     * @var array
     */
    protected $headers = [];

    /**
     * contentType
     * @var mixed
     */
    private $contentType;

    /**
     * files
     * @var mixed
     */
    private $files;

    /**
     * Associative array of supported content types and 
     * their corresponding parsing methods.
     *
     * @var array
     */
    protected $supportedContentTypes = [
        'text/xml'                          => 'parseXML',
        'text/csv'                          => 'parseCSV',
        'application/json'                  => 'parseJSON',
        'application/xml'                   => 'parseXML',
        'application/x-www-form-urlencoded' => 'parseForm',
    ];

    /**
     * Holds a map of lower-case header names
     * and their normal-case key as it is in $headers.
     * Used for case-insensitive header access.
     *
     * @var array
     */
    protected $headerMap = [];

    /**
     * body
     * @var mixed
     */
    protected $body;


    public function __construct()
    {
        $this->init();
    }

    /**
     * Create a URL by combining a base URL and a relative URI.
     *
     * @param string $uri
     * @param string|null $baseUrl
     * @return string
     */
    public function createUrl(string $uri = '', array $params = null): string
    {
        // // If a base URL is provided, combine it with the URI
        // if (!is_null($baseUrl)) {
        //     // Ensure that the base URL ends with a trailing slash
        //     $baseUrl = rtrim($baseUrl, '/') . '/';

        //     // Combine the base URL and the URI
        //     return $baseUrl . ltrim($uri, '/');
        // }

        // If no base URL is provided, return the URI as is
        return Router::url($uri, $params);
    }

    /**
     * Gets the method of the current HTTP request 
     * @return string The method of the HTTP request.
     */
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Gets the $_FILES array of uploaded files 
     *  @return array|null The $_FILES array or null if no files were provided.
     */
    public function files(string $name = null): ?array
    {
        $this->files = isset($_FILES[$name]) ? $_FILES[$name] : $_FILES;
        return $this->files ?: null;
    }

    /**
     * Get an uploaded file by its name 
     * 
     * @param string $name The name of the file to get 
     * @return array|null The array of the specific file or null if not found.
     */
    public function file(string $options): ?array
    {
        $file = $this->files[$options];
        return isset($file[$options]) ? $file[$options] : null;
    }

    /**
     * Checks if a file with a specific name has been uploaded 
     * @param string $name Name of the file to check 
     * @return bool Indicates if the file has been uploaded.
     */
    public function hasFile(string $name): bool
    {
        $files = $this->files;
        return isset($files[$name]) && $files[$name]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Moves an uploaded file to a destination location 
     * 
     * @param string $destination Destination path where the file will be moved 
     * @return bool Indicates whether the file move operation was successful.
     */
    public function move(string $destination): bool
    {
        $sourcePath = $this->files('tmp_name')[0];
        if (is_uploaded_file($sourcePath) && move_uploaded_file($sourcePath, $destination)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the uploaded files.
     */
    public function getClientOriginalName(): ?string
    {
        return $_FILES['name'] ?? null;
    }

    /**
     * Gets the uploaded files.
     */
    public function getClientOriginalExtension()
    {
        $file = data_get($this->files(), 'file');
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * @param mixed $file
     * @return string
     */
    public function get_file_extension($file)
    {
        $filename = $file['name'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * Returns true if the method is get
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Returns true if method is HEAD
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Returns true if the method is post
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Returns true if the method is PUT
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Returns true if the method is PATCH
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Returns true if the method is DELETE
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Returns true if method is OPTIONS
     * @return bool
     */
    public function isOtions(): bool
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * Determine if the request is simple form data.
     */
    public function isForm(): bool
    {
        return $this->contentType === 'application/x-www-form-urlencoded';
    }

    /**
     * Determine if the request is JSON.
     */
    public function isJson(): bool
    {
        return $this->contentType === 'application/json';
    }

    /**
     * Determine if the request is text/plain.
     * @return bool
     */
    public function isText(): bool
    {
        return $this->contentType === 'text/plain';
    }

    /**
     * Determine if the request is multipart.
     * @return bool
     */
    public function isMultipart(): bool
    {
        return $this->contentType === 'multipart/form-data';
    }

    /**
     * Determine if the request is xml.
     */
    public function isXml(): bool
    {
        return $this->contentType === 'application/xml';
    }

    /**
     * Checks if the data is true with the csrf field and 
     * Returns the data sent by the post method.
     */
    private function hasPost(): bool
    {
        if ($this->is_csrf_valid())
            return ($this->isPost()) ? true : false;
        else
            return throw new RuntimeException("CSRF token is invalid.");
    }

    /**
     * Parse JSON
     * Converts JSON format to associative array.
     * @param string $input
     * @return array|string
     */
    public function parseJSON($input)
    {
        return json_decode($input, true);
    }

    /**
     * Parse XML 
     * Convert XML format into an object, this will need to be made standard 
     * if objects or associative arrays are returned 
     * @param string $input
     * @return \SimpleXMLElement|null
     */
    public function parseXML($input)
    {
        try {
            return new \SimpleXMLElement($input);
        } catch (\Exception $e) {
            // Do nothing
        }
    }

    /**
     * Performs Form to Array format conversion.
     * @param string $input
     * @return array
     */
    public function parseForm($input)
    {
        if ($this->hasPost())
            parse_str($input, $vars);

        return $vars;
    }

    /**
     * Get a subset containing the provided keys with values
     * from the input data.
     * @param  array|mixed $keys
     * @return array
     */
    protected function arrayToObject(array $keys): object
    {
        return (object) $keys;
    }

    /**
     * Returns the input data in an Object
     */
    public function input(string $key = null)
    {
        $data = (object) $this->arrayToObject($this->getBody());
        return (empty($key)) ?
            $data : ($data->{$key} ??
                throw new RuntimeException(
                    printf('The property %s does not exist in the input data.', [$data->$key ?? $key])
                ));
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->input($key);
    }

    /**
     * Fetch an item from the COOKIE array.
     *
     * @param array|string|null $index  Index for item to be fetched from $_COOKIE
     * @param int|null          $filter A filter name to be applied
     * @param mixed             $flags
     * @return mixed
     */
    public function getCookie(string $key = null)
    {
        return $_COOKIE[$key] ?? [];
    }

    /**
     * Stores a value in a cookie.
     *
     * @param string $name The name of the cookie.
     * @param mixed $value The value to store in the cookie.
     * @param int $expiration The time in seconds before the cookie expires (default is 30 days).
     * @param string $path The path where the cookie will be available (default is "/" for the entire site).
     * @param string $domain The domain where the cookie will be available (default is the current domain).
     * @param bool $secure Indicates whether the cookie should be sent only over a secure connection (default is false).
     * @param bool $httpOnly Indicates whether the cookie should be accessible only via HTTP (default is true).
     */
    public function setCookie($name, $value, $expiration = 2592000, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        // Validate and clean input data
        $name  = cleanInput($name);
        $value = cleanInput($value);

        // If the domain is not specified, use the current domain
        if ($domain === null) $domain = $_SERVER['HTTP_HOST'];

        // Calculate the expiration date
        $expirationTime = time() + $expiration;

        // Configure the cookie
        setcookie(
            $name,
            $value,
            [
                'expires' => $expirationTime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => config('session.samesite') ?? null, // Set SameSite to None
            ]
        );
    }

    /**
     * Expire a cookie by name.
     *
     * This function expires a cookie by setting its expiration date in the past,
     * causing the browser to remove it.
     * @param string $name   The name of the cookie to expire.
     * @param string $value   The path on the server where the cookie is available.
     *                       Defaults to '/'.
     * @param string|null $domain The domain for which the cookie is available.
     *                            If not specified, the current domain is used.
     */
    function deleteCookie($name, $value = '/', $domain = null)
    {
        // If the domain is not specified, use the current domain
        if ($domain === null) {
            $domain = $_SERVER['HTTP_HOST'];
        }

        // Set the expiration date in the past to delete the cookie
        setcookie($name, '', time() - 3600, $value, $domain);
    }

    /**
     * Indicates if the request is AJAX
     * @return boolean
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Indicates whether the request is CLI
     * @return boolean
     */
    public function isCLI(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Detect if the User Agent is a mobile phone
     * @return boolean
     */
    public function isMobile(): bool
    {
        return strpos(mb_strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') ? TRUE : FALSE;
    }

    /**
     * @return string
     */
    public function getOperatingSystem(): string
    {
        return php_uname('s');
    }

    /**
     * Detects if https is secure
     * @return boolean
     */
    public function isSecure(): bool
    {
        return app()
            ->config()
            ->get('app.forceGlobalSecureRequests');
    }

    /**
     * Get User Agent (User Agent)
     * @return String
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Allows the client's IP to be obtained, even when using a proxy.
     * @return String
     */
    public function getIPAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Returns the version of the HTTP protocol used by client.
     * @return string the version of the HTTP protocol.
     */
    public function getHttpVersion()
    {
        return (isset($_SERVER['SERVER_PROTOCOL'])
            && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0')
            ? 1.0
            : 1.1;
    }

    /**
     * Modify the parameter passed by Url
     * 
     * @param $params
     * @return $this
     */
    public function setRouteParams($params)
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * Returns all the parameters passed by Url
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams ?? null;
    }

    /**
     * Returns a specific parameter from those passed by url
     * @param string $param array
     */
    public function getRouteParam($param, $default = null)
    {
        return $this->routeParams[$param] ?? [];
    }

    /**
     * Validates whether the csrf is valid.
     */
    private function is_csrf_valid(): bool
    {
        $requestToken = $this->toJson()->csrfToken ?? $_POST['csrfToken'] ?? null;
        if (!$requestToken) return false;

        return $_COOKIE['csrfToken'] === $requestToken;
    }

    /**
     * A convenience method that grabs the raw input stream and decodes
     * the JSON into an array.
     * @return mixed
     */
    public function toJson(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        return json_decode($this->body ?? '', $assoc, $depth, $options);
    }

    /**
     * Validate an IP address
     *
     * @param string $ip    IP Address
     * @param string $which IP protocol: 'ipv4' or 'ipv6'
     * @deprecated Use Validation instead
     */
    public function isValidIP(?string $ip = null, ?string $type = null): bool
    {
        $Ip = new \Validation\Rules\Ip();
        return ($Ip->validate($ip));
    }

    /**
     * Gets the content type of an HTTP request
     * @return string|null
     */
    public function getContentType(): ?string
    {
        $contentType = null;

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'];
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
        }

        return $contentType;
    }

    /**
     * Check the method of the request
     *
     * @param string $method Http method
     * @return mixed
     */
    public function isRequestMethod(string|array $methods): bool
    {
        if (is_string($methods)) {
            return strtoupper($methods) === $_SERVER['REQUEST_METHOD'];
        }

        $uppercasedMethods = array_map('strtoupper', $methods);

        // Return true if at least one of the methods is matched.
        return in_array(strtoupper($_SERVER['REQUEST_METHOD']), $uppercasedMethods);
    }

    /**
     * Gets a value from the $_POST array
     *
     * @param string $var
     * @return mixed
     */
    public function post(string $key = '')
    {
        return isset($_POST) ? $this->getFilteredValue($key, $_POST) : null;
    }

    /**
     * Gets a value from the $_GET array, applies the default 
     * FILTER_SANITIZE_STRING filter
     * @param string $var
     * @return mixed
     */
    public function get(string $key = '')
    {
        return isset($_GET) ? $this->getFilteredValue($key, $_GET) : null;
    }

    /**
     * Gets a value $value from the array $_REQUEST(Contains $_GET,$_POST,$_COOKIE)
     *
     * @param string $var
     * @return mixed
     */
    public function request(string $key = '')
    {
        return $this->getFilteredValue($key, $_REQUEST);
    }

    /**
     * Gets a value $value from the array $_SERVER
     *
     * @param string $var
     * @return mixed
     */
    protected function server(string $key = '')
    {
        return $this->getFilteredValue($key, $_SERVER);
    }

    /**
     * Filters/sanitizes a value from an array using a specified filter.
     *
     * @param string $key The key of the value to filter.
     * @param mixed $value The value to filter.
     * @param int $filter (optional) The filter to apply. Defaults to FILTER_SANITIZE_STRING.
     * @param array $array (optional) The array containing the value. Defaults to an empty array.
     * @return mixed The filtered value or null if the value is empty.
     * @throws RuntimeException If the key does not exist in the array.
     */
    protected function getFilteredValue(string $key, array $array, $default = null): mixed
    {
        // Verify if the key exists and is not null
        if (isset($array[$key]) && $array[$key] !== null) {
            // Filter the value of the key using the function h
            $filteredValue = $this->h($array[$key]);
        } else {
            // The key does not exist or its value is null, apply the function h to the whole array.
            $filteredValue = array_map([$this, 'h'], $array);
        }

        // Return filtered value or default value if key not found
        return $filteredValue ?? $default;
    }

    /**
     * Shortcut for htmlspecialchars, defaults to the application's charset.
     * 
     * @param string|array $data
     * @param string $charset
     * @return string
     */
    protected function h($data, string $charset = null)
    {
        if (is_string($data)) {
            $data = htmlspecialchars($data, ENT_QUOTES, $charset ?? APP_CHARSET);
        } elseif (is_array($data)) {
            $data = array_map('htmlspecialchars', $data);
        }

        return $data;
    }

    /**
     * Parse the request body based on the content type
     * @return mixed
     */
    public function getBody(): mixed
    {
        $supportedContentTypes = $this->supportedContentTypes;
        if (!in_array($this->contentType, array_keys($supportedContentTypes))) {
            throw new RuntimeException("Content type {$this->contentType} not supported");
        }

        try {
            $requestBody = file_get_contents('php://input');
        } catch (\Exception $e) {
            throw new RuntimeException('Error retrieving request body: ' . $e->getMessage());
        }

        $parser = $supportedContentTypes[$this->contentType];
        $parsedBody = $this->$parser($requestBody);

        if (!$parsedBody) {
            throw new RuntimeException('Input data cannot be processed');
        }

        return $parsedBody;
    }

    /**
     * Determines if the current request is an Axm request.
     * @return bool True if it's an Axm request, false otherwise.
     */
    public function isAxmRequest(): bool
    {
        $requestCsrfHeader = $this->getRequestCsrfHeader();
        $axmCsrfToken = app()->getCsrfToken();

        return $this->compareCsrfTokens($requestCsrfHeader, $axmCsrfToken);
    }

    /**
     * Get the CSRF token from the request header.
     * @return string|null The CSRF token or null if not present.
     */
    private function getRequestCsrfHeader(): ?string
    {
        $csrfHeader = $this->getHeader('X-CSRF-TOKEN');
        return $csrfHeader !== null ? trim($csrfHeader, "'") : null;
    }

    /**
     * Compare two CSRF tokens for equality.
     *
     * @param string|null $tokenFromRequest The CSRF token from the request.
     * @param string|null $axmCsrfToken     The CSRF token from the Axm application.
     */
    private function compareCsrfTokens(?string $tokenFromRequest, ?string $axmCsrfToken): bool
    {
        // Use strict comparison to handle null values
        return $tokenFromRequest === $axmCsrfToken;
    }

    /**
     * Returns user browser accept types, null if not present.
     * @return string user browser accept types, null if not present
     */
    public function getAcceptTypes()
    {
        return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
    }

    /**
     * Returns the URL referrer, null if not present
     * @return string URL referrer, null if not present
     */
    public function getUrlReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * Ininitialize
     */
    private function init()
    {
        $this->contentType  = $this->getContentType() ?? 'application/json';
    }

    /**
     * @return bool
     */
    private function hasSecurityTokens(): bool
    {
        return app()->hasCsrfToken($_SERVER['X-CSRF-TOKEN']);
    }

    /**
     * Gets all the headers from the request and returns it as an associative array.
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('HTTP_', '', $key);
                $headerName = strtoupper(str_replace('_', '-', $headerName));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Returns the value of the header by entering the key
     */
    public function getHeader(string $key)
    {
        return $this->getHeaders()[$key] ?? null;
    }

    /**
     * Get a header line from the request headers.
     *
     * @param string $name The name of the header.
     * @param string $defaultValue The default value to return if the header is not present.
     * @return string The value of the header or the default value.
     */
    public function getHeaderLine(string $name, string $defaultValue = ''): string
    {
        $headerValue = $this->getHeader($name);
        if (empty($headerValue)) {
            return $defaultValue;
        }

        return implode(',', $headerValue);
    }

    /**
     * Determine if the request has a given header.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function hasHeader($key, $value = null): bool
    {
        $headers = $this->getHeaders();
        if (!array_key_exists($key, $headers))
            return false;

        if (is_null($value))
            return true;

        return in_array($value, (array) $headers[$key]);
    }

    /**
     * Determine if the request has the given headers.
     *
     * @param  array|string  $headers
     * @return bool
     */
    public function hasHeaders($headers): bool
    {
        if (is_string($headers))
            $headers = [$headers => null];

        foreach ($headers as $key => $value) {
            if (!$this->hasHeader($key, $value))
                return false;
        }

        return true;
    }

    /**
     * Modifica um Header específicado por user 
     * 
     * @param string $name
     * @param string $value
     */
    public function setHeader(string $name, string $value): self
    {
        if (empty($name) || empty($value)) {
            throw new \InvalidArgumentException('Name and value must be non-empty strings');
        }

        $safeValue = str_replace(["\r", "\n"], '', $value);
        header("{$name}: {$safeValue}", true);

        return $this;
    }

    /**
     * Populates the $headers array with any headers the getServer knows about.
     */
    public function populategetHeaders(): void
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $this->setHeader('Content-Type:', $contentType);
        }

        foreach ($_SERVER as $key => $value) {
            if (sscanf($key, 'HTTP_%s', $header) === 1) {
                $header = str_replace('_', '-', ucwords(strtolower($header)));
                $this->setHeader($header, $value);
                $this->headerMap[strtolower($header)] = $header;
            }
        }
    }
}
