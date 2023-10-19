<?php

namespace Axm\Http;

use Axm;
use Axm\Http\URI;
use Axm\Exception\AxmException;

/**
 * Class Request
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\HTTP
 */

class Request extends URI
{

    private array $routeParams = [];
    protected $headers = [];
    private $_inputFormat;
    private $files;

    /**
     * Tipos de contenido permitidos para la solicitud HTTP.
     * Cada tipo especifica una función de análisis correspondiente.
     */
    protected static $allowedContentTypes = [
        'text/xml'         => 'parseXML',
        'text/csv'         => 'parseCSV',
        'application/json' => 'parseJSON',
        'application/xml'  => 'parseXML',
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

    protected $body;


    public function __construct()
    {
        $this->init();
    }

    public function createUrl(string $uri = '')
    {
        return $this->createNewUrl($uri);
    }

    /**
     * Obtiene el método de la petición HTTP actual.
     *
     * @return string El método de la petición HTTP.
     */
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Obtiene el array $_FILES de archivos subidos.
     *
     * @return array|null El array $_FILES o null si no se proporcionaron archivos.
     */
    public function files(string $name = null): ?array
    {
        $this->files = isset($_FILES[$name]) ? $_FILES[$name] : $_FILES;
        return $this->files ?: null;
    }

    /**
     * Obtiene un archivo subido por su nombre.
     *
     * @param string $name El nombre del archivo a obtener.
     * @return array|null El array del archivo específico o null si no se encuentra.
     */
    public function file(string $options): ?array
    {
        $file = $this->files[$options];
        return isset($file[$options]) ? $file[$options] : null;
    }

    /**
     * Verifica si se ha subido un archivo con un nombre específico.
     *
     * @param string $name Nombre del archivo a verificar.
     * @return bool Indica si el archivo ha sido subido.
     */
    public function hasFile(string $name): bool
    {
        $files = $this->files;
        return isset($files[$name]) && $files[$name]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Mueve un archivo subido a una ubicación destino.
     *
     * @param string $destination Ruta destino donde se moverá el archivo.
     * @return bool Indica si la operación de mover el archivo fue exitosa.
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
     * Obtiene los archivos subido.
     *
     */
    public function getClientOriginalName(): ?string
    {
        return $_FILES['name'] ?? null;
    }

    /**
     * Obtiene los archivos subido.
     *
     */
    public function getClientOriginalExtension()
    {
        $file = data_get($this->files(), 'file');
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return $extension;
    }

    public function get_file_extension($file)
    {
        $filename = $file['name'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * Devuelve true si el método es get
     * 
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Devuelve true si el método es HEAD
     * 
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Devuelve true si el método es post
     * 
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Devuelve true si el método es PUT
     * 
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Devuelve true si el método es PATCH
     * 
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Devuelve true si el método es DELETE
     * 
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Devuelve true si el método es OPTIONS
     * 
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
        return $this->_inputFormat === 'application/x-www-form-urlencoded';
    }

    /**
     * Determine if the request is JSON.
     */
    public function isJson(): bool
    {
        return $this->_inputFormat === 'application/json';
    }

    /**
     * Determine if the request is text/plain.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this->_inputFormat === 'text/plain';
    }

    /**
     * Determine if the request is multipart.
     *
     * @return bool
     */
    public function isMultipart(): bool
    {
        return $this->_inputFormat === 'multipart/form-data';
    }

    /**
     * Determine if the request is xml.
     */
    public function isXml(): bool
    {
        return $this->_inputFormat === 'application/xml';
    }

    /**
     * Verifica si son veridicos los datos con el campo csrf y
     * Devuelve los datos enviados por el método post
     */
    private function hasPost(): bool
    {
        if ($this->is_csrf_valid())
            return ($this->isPost()) ? true : false;
        else
            return throw new AxmException("El token CSRF no es válido.");
    }

    /**
     * Parse JSON
     * Convierte formato JSON en array asociativo.
     *
     * @param string $input
     *
     * @return array|string
     */
    public function parseJSON($input)
    {
        return json_decode($input, true);
    }

    /**
     * Parse XML.
     *
     * Convierte formato XML en un objeto, esto será necesario volverlo estandar
     * si se devuelven objetos o arrays asociativos
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
     * Realiza la conversión de formato de Formulario a array.
     *
     * @param string $input
     *
     * @return array
     */
    public function parseForm($input)
    {
        if ($this->hasPost())
            parse_str($input, $vars);

        return $vars;
    }

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * @param  array|mixed  $keys
     * @return array
     */
    protected function arrayToObject(array $keys): object
    {
        return (object) $keys;
    }

    /**
     * devuelve los datos de entrada en un Objeto
     */
    public function input(string $key = null)
    {
        $data = (object) $this->arrayToObject($this->getBody());
        return (empty($key)) ? $data : ($data->{$key} ?? throw new AxmException(Axm::t('axm', 'La propiedad "%s" no existe en los datos de entrada.', [$data->$key ?? $key])));
    }


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
     *
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
        if ($domain === null) {
            $domain = $_SERVER['HTTP_HOST'];
        }

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
                'samesite' => 'None', // Set SameSite to None
            ]
        );
    }

    /**
     * Expire a cookie by name.
     *
     * This function expires a cookie by setting its expiration date in the past,
     * causing the browser to remove it.
     * @param string $name   The name of the cookie to expire.
     * @param string $path   The path on the server where the cookie is available.
     *                       Defaults to '/'.
     * @param string|null $domain The domain for which the cookie is available.
     *                            If not specified, the current domain is used.
     */
    function deleteCookie($name, $path = '/', $domain = null)
    {
        // If the domain is not specified, use the current domain
        if ($domain === null) {
            $domain = $_SERVER['HTTP_HOST'];
        }

        // Set the expiration date in the past to delete the cookie
        setcookie(
            $name,
            '',
            time() - 3600, // Set the time in the past (one hour earlier)
            $path,
            $domain
        );
    }

    /**
     * Indica si el request es AJAX
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Indica si el request es CLI
     *
     * @return boolean
     */
    public function isCLI(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Detecta si el Agente de Usuario (User Agent) es un móvil
     *
     * @return boolean
     */
    public function isMobile(): bool
    {
        return strpos(mb_strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') ? TRUE : FALSE;
    }

    /**
     * 
     */
    public function getOperatingSystem(): string
    {
        return php_uname('s');
    }

    /**
     * Detecta si es seguro https
     *
     * @return boolean
     */
    public function isSecure(): bool
    {
        return Axm::app()->config()->get('app.forceGlobalSecureRequests');
    }

    /**
     * Permite Obtener el Agente de Usuario (User Agent)
     * @return String
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Permite obtene la IP del cliente, aún cuando usa proxy
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
     * Modifica el parámetro pasado por url
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
     * Devuelve todos los parámetro pasados por url
     * 
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams ?? null;
    }

    /**
     * Devuelve un parametro específico
     * 
     * de los que han sido pasados por url
     * @param string $param array
     */
    public function getRouteParam($param, $default = null)
    {
        return $this->routeParams[$param] ?? [];
    }

    /**
     * Valida si el csrf es válido.
     * devuelve si el token de sesión y el token de solicitud son iguales. 
     */
    private function is_csrf_valid(): bool
    {
        $requestToken = $this->getJson()->_csrf_token_ ?? $_POST['_csrf_token_'] ?? null;
        if (!$requestToken) return false;

        return $_SESSION['_csrf_token_'] === $requestToken;
    }

    /**
     * A convenience method that grabs the raw input stream and decodes
     * the JSON into an array.
     *
     * If $assoc == true, then all objects in the response will be converted
     * to associative arrays.
     * @param bool $assoc   Whether to return objects as associative arrays
     * @param int  $depth   How many levels deep to decode
     * @param int  $options Bitmask of options
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @return mixed
     */
    public function getJson(bool $assoc = false, int $depth = 512, int $options = 0)
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
        $Ip = new \Axm\Validation\Rules\Ip();
        return ($Ip->validate($ip));
    }

    /**
     * Obtiene el tipo de contenido de una solicitud HTTP
     *
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
     * Verifica el método de la petición
     *
     * @param string $method Http method
     * @return mixed
     */
    public function isRequestMethod(string $method): bool
    {
        return strtoupper($method) === $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Obtiene un value del arreglo $_POST
     *
     * @param string $var
     * @return mixed
     */
    public function post($key = '')
    {
        return $this->getFilteredValue($key, $_POST);
    }

    /**
     * Obtiene un value del arreglo $_GET, aplica el filtro FILTER_SANITIZE_STRING
     * por defecto
     *
     * @param string $var
     * @return mixed
     */
    public function get($key = '')
    {
        return $this->getFilteredValue($key, $_GET);
    }

    /**
     * Obtiene un value $value del arreglo $_REQUEST(Contiene $_GET,$_POST,$_COOKIE)
     *
     * @param string $var
     * @return mixed
     */
    public function request($key = '')
    {
        return $this->getFilteredValue($key, $_REQUEST);
    }

    /**
     * Obtiene un value$value del arreglo $_SERVER
     *
     * @param string $var
     * @return mixed
     */
    protected function server($key = '')
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
     * @throws AxmException If the key does not exist in the array.
     */
    protected function getFilteredValue(string $key, array $array, $default = null): mixed
    {
        $value  = isset($array[$key]) ? [$array[$key]] : $array;
        $filter = array_map([$this, 'h'], $value);

        return isset($array[$key]) ? ($filter[$key] ?? $default) : $filter;
    }

    /**
     * Atajo para htmlspecialchars, por defecto toma el charset de la
     * aplicacion.
     *
     * @param string|array $data
     * @param string $charset
     *
     * @return string
     */
    protected function h($data, string $charset = APP_CHARSET)
    {
        if (is_string($data)) {
            $data = htmlspecialchars($data, ENT_QUOTES, $charset);
        } elseif (is_array($data)) {
            $data = array_map('htmlspecialchars', $data);
        }

        return $data;
    }

    /**
     * Obtiene el cuerpo de la solicitud HTTP, procesándolo según el tipo de contenido.
     *
     * @return mixed El cuerpo de la solicitud, procesado según el tipo de contenido.
     * @throws AxmException si el tipo de contenido no está permitido o el cuerpo no se puede procesar.
     */
    public function getBody()
    {
        if (!in_array($this->_inputFormat, array_keys(static::$allowedContentTypes))) {
            throw new AxmException('Content type not allowed');
        }

        try {
            $input = file_get_contents('php://input');
        } catch (AxmException $e) {
            throw new AxmException('Error retrieving input data: ' . $e->getMessage());
        }

        $parser = [self::class, static::$allowedContentTypes[$this->_inputFormat]];
        $result = call_user_func($parser, $input) ?? false;

        if (!$result) {
            throw new AxmException('Input data cannot be processed');
        }

        return $result;
    }

    /**
     * Determina si la solicitud actual es una solicitud de Axm
     */
    public function isAxmRequest(): bool
    {
        return $this->getHeader('X-Csrf-Token') === Axm::app()->generateCsrfToken();
    }

    /**
     * Elimina del la entrada el elemento token.
     * 
     * @param array $data
     * @return array
     */
    protected function returnDataWithoutToken($data)
    {
        if (isset($data['_csrf_token_']))
            unset($data['_csrf_token_']);

        return $data;
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
     * ininitialize
     */
    private function init()
    {
        $this->_inputFormat  = $this->getContentType() ?? 'application/json';
    }

    /**
     * 
     */
    private function hasSecurityTokens(): bool
    {
        return Axm::app()->hasCsrfToken($_SERVER['X-CSRF-TOKEN']);
    }

    /**
     * Obtiene todas las cabeceras  desde la solicitud y lo devuelve como un array asociativo
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('HTTP_', '', $key);
                $headerName = strtolower(str_replace('_', '-', $headerName));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Devuelve el valor de la cabecera entrando la llave
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
        if (is_string($headers)) {
            $headers = [$headers => null];
        }

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
     * 
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
