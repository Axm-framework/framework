<?php

namespace Http;

use Http\URI;
use Validation\Validator;
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
     */
    private array $routeParams = [];

    /**
     * headers
     */
    protected array $headers = [];

    /**
     * Content type
     */
    private ?string $contentType;

    /**
     * Associative array of supported content types and 
     * their corresponding parsing methods.
     */
    protected array $supportedContentTypes = [
        'text/xml' => 'parseXML',
        'text/csv' => 'parseCSV',
        'application/json' => 'parseJSON',
        'application/xml' => 'parseXML',
        'application/x-www-form-urlencoded' => 'parseForm',
        'multipart/form-data' => 'parseMultipartForm'
    ];

    /**
     * Holds a map of lower-case header names
     * and their normal-case key as it is in $headers.
     * Used for case-insensitive header access.
     */
    protected array $headerMap = [];

    protected $body;

    protected ?string $key;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Parse multipart/form-data request
     */
    public function parseMultipartForm(): array
    {
        $formData = compact($this->post(), $this->files()) ?? [];

        return $formData;
    }

    /**
     * Create a URL by combining a base URL and a relative URI.
     */
    public function createUrl(string $uri = '', array $params = null): string
    {
        return app()->router->url($uri, $params);
    }

    /**
     * Gets the method of the current HTTP request
     */
    public function getMethod(): ?string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
    }

    /**
     * Gets the $_FILES array of uploaded files or a specific file.
     */
    public function files(string $name = null): ?array
    {
        $files = isset($_FILES) ? $this->getFilteredValue($name, $_FILES) : null;

        return $name !== null ? $files[$name] ?? null : $files;
    }

    /**
     * Get an uploaded file by its name.
     */
    public function file(string $name): ?array
    {
        return $this->files($name);
    }

    /**
     * Checks if a file with a specific name has been uploaded.
     */
    public function hasFile(string $name): bool
    {
        $file = $this->file($name);
        return isset($file) && $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Moves an uploaded file to a destination location.
     */
    public function move(string $name, string $destination = ''): bool
    {
        if (!$file = $this->file($name))
            return false;

        $uploadPath = config('paths.uploadPath');
        $destination = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($destination, DIRECTORY_SEPARATOR);

        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
        }

        $uniqueFilename = bin2hex(random_bytes(20)) . time() . '.' . $this->getFileExtension($file);
        $destinationFile = $destination . DIRECTORY_SEPARATOR . $uniqueFilename;

        if (move_uploaded_file($file['tmp_name'], $destinationFile)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the original name of the uploaded file.
     */
    public function getClientOriginalName(string $name): ?string
    {
        return $this->file($name)['name'] ?? null;
    }

    /**
     * Gets the original extension of the uploaded file.
     */
    public function getClientOriginalExtension(string $name): ?string
    {
        $file = $this->file($name);
        if ($file) {
            return pathinfo($file['name'], PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * Gets the extension of a given file array.
     */
    public function getFileExtension(array $file): string
    {
        return pathinfo($file['name'], PATHINFO_EXTENSION);
    }

    /**
     * Returns true if the method is get
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Returns true if method is HEAD
     */
    public function isHead(): bool
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Returns true if the method is post
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Returns true if the method is PUT
     */
    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Returns true if the method is PATCH
     */
    public function isPatch(): bool
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Returns true if the method is DELETE
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Returns true if method is OPTIONS
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
     */
    public function isText(): bool
    {
        return $this->contentType === 'text/plain';
    }

    /**
     * Determine if the request is multipart.
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
            return $this->isPost();
        else
            return throw new RuntimeException("CSRF token is invalid.");
    }

    /**
     * Converts JSON format to associative array.
     */
    public function parseJSON(string $input): mixed
    {
        return json_decode($input, true);
    }

    /**
     * Converts XML string into a SimpleXMLElement object.
     */
    public function parseXML(string $input): \SimpleXMLElement
    {
        try {
            $xml = new \SimpleXMLElement($input);
        } catch (\Exception $e) {
            throw new \Exception("Invalid XML: " . $e->getMessage());
        }

        return $xml;
    }

    /**
     * Performs Form to Array format conversion.
     */
    public function parseForm(string $input): array
    {
        if ($this->hasPost())
            parse_str($input, $vars);

        return $vars;
    }

    /**
     * Convert an array to an object, containing only the provided keys with values.
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
        return empty($key) ? $data : ($data->{$key} ?? throw new RuntimeException(sprintf('The property %s does not exist in the input data.', $key)));
    }

    /**
     * Fetch an item from the COOKIE array.
     */
    public function getCookie(?string $key = null)
    {
        return $_COOKIE[$key] ?? [];
    }

    /**
     * Stores a value in a cookie.
     */
    public function setCookie(string $name, string $value, int $expiration = 2592000, string $path = '/', ?string $domain = null, bool $secure = false, bool $httpOnly = true): bool
    {
        $domain = $domain ?? config('session.domain');
        $expirationTime = time() + $expiration;

        return setcookie(
            $name,
            $value,
            [
                'expires' => $expirationTime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => config('session.samesite') ?? 'Lax',
            ]
        );
    }

    /**
     * Expire a cookie by name.
     */
    public function deleteCookie(string $name, string $path = '/', ?string $domain = null): bool
    {
        $domain = $domain ?? config('session.domain');
        return setcookie($name, '', time() - 3600, $path, $domain);
    }

    /**
     * Indicates if the request is AJAX
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Indicates whether the request is CLI
     */
    public function isCLI(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Detect if the User Agent is a mobile phone
     */
    public function isMobile(): bool
    {
        return strpos(mb_strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') ? true : false;
    }

    /**
     * The operating system name.
     */
    public function getOperatingSystem(): string
    {
        return PHP_OS_FAMILY;
    }

    /**
     * Detects if https is secure
     */
    public function isSecure(): bool
    {
        return app()
            ->config()
            ->get('app.forceGlobalSecureRequests');
    }

    /**
     * Get User Agent (User Agent)
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Allows the client's IP to be obtained, even when using a proxy.
     */
    public function getIPAddress(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Returns the version of the HTTP protocol used by client.
     */
    public function getHttpVersion(): float
    {
        return (isset($_SERVER['SERVER_PROTOCOL'])
            && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0')
            ? 1.0
            : 1.1;
    }

    /**
     * Modify the parameter passed by Url
     */
    public function setRouteParams($params): self
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * Returns all the parameters passed by Url
     */
    public function getRouteParams()
    {
        return $this->routeParams ?? null;
    }

    /**
     * Returns a specific parameter from those passed by url
     */
    public function getRouteParam(string $param, $default = [])
    {
        return $this->routeParams[$param] ?? $default;
    }

    /**
     * Validates whether the csrf is valid.
     */
    private function is_csrf_valid(): bool
    {
        $requestToken = $this->toJson()->csrfToken ?? $_POST['csrfToken'] ?? '';
        $cookieToken = $this->getCookie('csrfToken') ?? '';

        return hash_equals($requestToken, $cookieToken);
    }

    /**
     * Validate data against a set of rules.
     */
    public function validate(array $rules, array $data): Validator
    {
        $validator = Validator::make($rules, $data);
        $validator->validate();

        return $validator;
    }

    /**
     * A convenience method that grabs the raw input stream and decodes the JSON into an array.
     */
    public function toJson(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        return json_decode($this->body ?? '', $assoc, $depth, $options);
    }

    /**
     * Validate an IP address
     */
    public function isValidIP(?string $ip = null): bool
    {
        $Ip = new \Validation\Rules\Ip();
        return ($Ip->validate($ip));
    }
    /**
     * Gets the content type of an HTTP request
     */
    public function getContentType(): ?string
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null;

        if (!$contentType) {
            return null;
        }

        $parts = explode(';', $contentType);
        return trim($parts[0]);
    }

    /**
     * Check the method of the request
     */
    public function isRequestMethod(string|array $methods): bool
    {
        if (is_string($methods)) {
            return strtoupper($methods) === $_SERVER['REQUEST_METHOD'];
        }

        $uppercasedMethods = array_map('strtoupper', $methods);
        return in_array(strtoupper($_SERVER['REQUEST_METHOD']), $uppercasedMethods);
    }

    /**
     * Gets a value from the $_GET array, applies the default FILTER_SANITIZE_STRING filter
     */
    public function get(string $key = '')
    {
        return isset($_GET) ? $this->getFilteredValue($key, $_GET) : null;
    }

    /**
     * Gets a value from the $_POST array, applies the default FILTER_SANITIZE_STRING filter
     */
    public function post(string $key = '')
    {
        return isset($_POST) ? $this->getFilteredValue($key, $_POST) : null;
    }

    /**
     * Gets a value $value from the array $_REQUEST(Contains $_GET,$_POST,$_COOKIE)
     */
    public function request(string $key = '')
    {
        return $this->getFilteredValue($key, $_REQUEST);
    }

    /**
     * Gets a value $value from the array $_SERVER
     */
    protected function server(string $key = '')
    {
        return $this->getFilteredValue($key, $_SERVER);
    }

    /**
     * Filters/sanitizes a value from an array using a specified filter.
     */
    protected function getFilteredValue(string $key, array $array, $default = null): mixed
    {
        if (isset($array[$key]) && $array[$key] !== null) {
            return $this->h($array[$key]);
        }

        return array_map([$this, 'h'], $array) ?? $default;
    }

    /**
     * Shortcut for htmlspecialchars, defaults to the application's charset.
     */
    protected function h(string|array $data, string $charset = null): ?string
    {
        if (is_string($data)) {
            $data = htmlspecialchars($data, ENT_QUOTES, $charset ?? config('app.charset'));
        } elseif (is_array($data)) {
            $data = array_map('htmlspecialchars', $data);
        }

        return $data;
    }

    /**
     * Parse the request body based on the content type
     */
    public function getBody(): mixed
    {
        $contentType = $this->contentType;
        $method = $this->supportedContentTypes[$contentType] ?? null;

        if ($method) {
            $input = file_get_contents('php://input');
            return $this->{$method}($input);
        }

        return throw new RuntimeException("Content type {$contentType} not supported");
    }

    /**
     * Determines if the current request is an Axm request.
     */
    public function isAxmRequest(): bool
    {
        $requestCsrfHeader = $this->getRequestCsrfHeader();
        $axmCsrfToken = app()->getCsrfToken();

        return $this->compareCsrfTokens($requestCsrfHeader, $axmCsrfToken);
    }

    /**
     * Get the CSRF token from the request header.
     */
    private function getRequestCsrfHeader(): ?string
    {
        $csrfHeader = $this->getHeader('X-CSRF-TOKEN');
        return $csrfHeader !== null ? trim($csrfHeader, "'") : null;
    }

    /**
     * Compare two CSRF tokens for equality.
     */
    private function compareCsrfTokens(?string $tokenFromRequest, ?string $axmCsrfToken): bool
    {
        return $tokenFromRequest === $axmCsrfToken;
    }

    /**
     * Returns the browser's accepted media types, or null if not present.
     */
    public function getAcceptTypes(): ?string
    {
        return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
    }

    /**
     * Returns the URL referrer, null if not present
     */
    public function getUrlReferrer(): ?string
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * Ininitialize
     */
    private function init()
    {
        $this->contentType = $this->getContentType() ?? 'application/json';
    }

    /**
     * Check security tokens
     */
    private function hasSecurityTokens(): bool
    {
        return app()->hasCsrfToken($_SERVER['X-CSRF-TOKEN']);
    }

    /**
     * Gets all the headers from the request and returns it as an associative array.
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
    public function getHeader(string $key): array|string|null
    {
        return $this->getHeaders()[$key] ?? null;
    }

    /**
     * Get a header line from the request headers.
     */
    public function getHeaderLine(string $name, string $defaultValue = ''): string
    {
        $headerValue = $this->getHeader($name);
        return $headerValue ? implode(',', $headerValue) : $defaultValue;
    }

    /**
     * Determine if the request has a given header.
     */
    public function hasHeader(string $key, $value = null): bool
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
     */
    public function hasHeaders(array|string $headers): bool
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
     * Modify a user-specified header
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
        if ($contentType = $this->contentType) {
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

    /**
     * Magic getter to retrieve input values.
     */
    public function __get(string $key): mixed
    {
        return $this->input($key) ?? null;
    }
}
