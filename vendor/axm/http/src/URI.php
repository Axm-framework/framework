<?php

namespace Axm\Http;

use Axm;
use Axm\Exception\AxmException;

/**
 * Class Request
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\HTTP
 */
abstract class URI
{
    /**
     * Current URI string
     * $uriString es el retorno filtrado de la uri sin quitar la roodir
     * @var string
     */
    protected $cleanedUri;

    /**
     * List of URI segments.
     *
     * Starts at 1 instead of 0
     *
     * @var array
     */
    protected $segments = [];

    /**
     * The URI Scheme.
     *
     * @var string
     */
    protected $scheme = 'http';

    /**
     * URI User Info
     *
     * @var string
     */
    protected $user;

    /**
     * URI User Password
     *
     * @var string
     */
    protected $password;

    /**
     * URI Host
     *
     * @var string
     */
    protected $host;

    /**
     * URI Port
     *
     * @var int
     */
    protected $port;

    /**
     * URI path.
     *
     * @var string
     */
    protected $path;

    /**
     * The name of any fragment.
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * The query string.
     *
     * @var array
     */
    protected $query = [];

    /**
     * Default schemes/ports.
     *
     * @var array
     */
    protected $defaultPorts = [
        'http'  => 80,
        'https' => 443,
        'ftp'   => 21,
        'sftp'  => 22,
    ];

    /**
     * Whether passwords should be shown in userInfo/authority calls.
     * Default to false because URIs often show up in logs
     *
     * @var bool
     */
    protected $showPassword = false;

    /**
     * If true, will continue instead of throwing exceptions.
     *
     * @var bool
     */
    protected $silent = false;

    /**
     * If true, will use raw query string.
     *
     * @var bool
     */
    protected $rawQueryString = false;

    /**
     * If true, will use raw query string.
     *
     * @var bool
     */
    protected $uri;

    /**
     * The encryption key resolver callable.
     *
     * @var callable
     */
    protected $key = null;

    /**
     * 
     */
    private const HASH_ALGORITHM = 'sha512';

    /**
     * Returns the cleaned and formatted URI for the current request.
     * 
     * @return string The cleaned and formatted URI for the current request.
     */
    public function getUri(): ?string
    {
        $uri = str_replace(PATH_CLEAR_URI, '', rawurldecode($_SERVER['REQUEST_URI']));

        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }


    /**
     * Builds a representation of the string from the component parts.     *
     */
    protected function createURIString(?string $scheme = null, ?string $host = null, ?string $path = null, string|object|array $query = null, ?string $fragment = null): string
    {
        $route = $scheme . '://' . $host . $path;

        if (!empty($query)) {
            $route .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        if (!empty($fragment)) {
            $route .= '#' . rawurlencode($fragment);
        }

        return $route;
    }


    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * @return string The URI scheme.
     */
    public function getScheme(): string
    {
        // Check for forced HTTPS
        if (Axm::app()->config()->get('forceGlobalSecureRequests')) {
            $this->scheme = 'https';
        }

        return $this->scheme ?? $_SERVER['REQUEST_SCHEME'];
    }


    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     */
    protected function getAuthority(): string
    {
        $authority = $this->getHost();
        if (!empty($userInfo = $this->getUserInfo())) {
            $authority = $userInfo . '@' . $authority;
        }

        $this->showPassword = false;
        return $authority;
    }


    /**
     * Retrieve the user information component of the URI.
     *
     */
    protected function getUserInfo()
    {
        $userInfo = $this->user;
        if ($this->showPassword === true && !empty($this->password)) {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    /**
     * Temporarily sets the URI to show a password in userInfo. Will
     * reset itself after the first call to authority().
     *
     * @return URI
     */
    protected function showPassword(bool $val = true)
    {
        $this->showPassword = $val;

        return $this;
    }


    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * @return string The URI host.
     */
    protected function getHost(): string
    {
        return $this->host ?? $_SERVER['HTTP_HOST'] ?? 'http://localhost';
    }


    /**
     * Retrieve the port component of the URI.
     */
    protected function getPort()
    {
        return $this->port ?? $_SERVER['SERVER_PORT'];
    }


    /**
     * Retrieve the path component of the URI.
     *
     * @return string The URI path.
     */
    protected function getPath(): string
    {
        return $this->path ?? '';
    }


    /**
     * Esta función toma un array de opciones y una variable interna llamada $query, y devuelve una cadena de 
     * consulta HTTP construida a partir de $query.
     * Dependiendo de las claves especificadas en el array de opciones, la función puede incluir solo ciertas 
     * claves de $query o excluir ciertas claves de $query antes de construir la cadena de consulta HTTP.
     */
    protected function getQueryString(array $options = []): ?array
    {
        $query = $this->query;

        if (is_array($options)) {
            if (array_key_exists('except', $options)) {
                $query = array_diff_key($query, array_flip($options['except']));
            } elseif (array_key_exists('only', $options)) {
                $query = array_intersect_key($query, array_flip($options['only']));
            }
        }

        return ($query);
    }


    /**
     * Retrieve the query string
     */
    public function getQuery(array $options = []): string
    {
        $vars = $this->query;

        if (array_key_exists('except', $options)) {
            if (!is_array($options['except'])) {
                $options['except'] = [$options['except']];
            }

            foreach ($options['except'] as $var) {
                unset($vars[$var]);
            }
        } elseif (array_key_exists('only', $options)) {
            $temp = [];

            if (!is_array($options['only'])) {
                $options['only'] = [$options['only']];
            }

            foreach ($options['only'] as $var) {
                if (array_key_exists($var, $vars)) {
                    $temp[$var] = $vars[$var];
                }
            }

            $vars = $temp;
        }

        return empty($vars) ? '' : http_build_query($vars);
    }


    /**
     * Retrieve a URI fragment
     */
    protected function getFragment(): string
    {
        return $this->fragment ?? '';
    }


    /**
     * Returns the segments of the path as an array.
     */
    protected function getSegments(): array
    {
        return $this->segments;
    }


    /**
     * Returns the value of a specific segment of the URI path.
     *
     */
    protected function getSegment(int $number, string $default = ''): string
    {
        // The segment should treat the array as 1-based for the user
        // but we still have to deal with a zero-based array.
        $number--;

        if ($number > count($this->segments) && !$this->silent)
            throw new AxmException("Segmento fuera de rango $number");

        return $this->segments[$number] ?? $default;
    }


    /**
     * Returns the total number of segments.
     */
    protected function getTotalSegments(): int
    {
        return count($this->segments);
    }


    public function createNewUrl(string $uri, array $query = []): string
    {
        $scheme     = $this->getScheme();
        $authority  = $this->getAuthority();
        $uri        = PATH_CLEAR_URI . trim($uri, '/');
        $query      = $this->getQuery($query);
        $fragment   = $this->getFragment();

        return static::createURIString($scheme, $authority, $uri, $query, $fragment);
    }


    public function getUrl(string $url = null): string
    {
        $scheme     = $this->getScheme();
        $authority  = $this->getAuthority();
        $cleanedUri = $url ?? PATH_CLEAR_URI ?? '';
        $query      = $this->getQuery();
        $fragment   = $this->getFragment();

        return static::createURIString($scheme, $authority, $cleanedUri, $query, $fragment);
    }

    /**
     * 
     */
    public function getCurrentUrl(): string
    {
        $url = trim($this->getUrl() . trim($this->getUri(), '/'));
        return $url;
    }

    /**
     * 
     */
    public function signed(string $url, $expire = null)
    {
        $now = time();
        $expiration = $expire ?: $now + 3600; // 1 hour by default
        $signature  = $this->generateSignature($url);
        $queryParameters = [
            'signature'  => $signature,
            'expiration' => $expiration
        ];

        $signedUrl = $this->createNewUrl($url . '?' . http_build_query($queryParameters));
        return $signedUrl;
    }

    /**
     * 
     */
    public function hasValidSignature(): bool
    {
        $urlParts = parse_url($_SERVER['REQUEST_URI']);

        if (!isset($urlParts['query'])) return false;
        parse_str($urlParts['query'], $queryParameters);

        if (
            !isset($queryParameters['expiration']) ||
            !isset($queryParameters['signature'])
        ) {
            return false;
        }

        $expirationTimestamp = $queryParameters['expiration'];
        if ($expirationTimestamp < time()) {
            return false;
        }

        $signature = $queryParameters['signature'];
        $generatedSignature = $this->generateSignature($this->getUri());

        return hash_equals($generatedSignature, $signature);
    }

    /**
     * 
     */
    private function generateSignature($url)
    {
        $this->key  = env('AXM_APP_KEY');
        $dataToSign = $url;
        $signature  = hash_hmac(self::HASH_ALGORITHM, $dataToSign, $this->key);

        return $signature;
    }

    /**
     * Removes a specified number of path fragments from a given path.
     *
     * This function divides the input path into fragments using the directory separator (e.g., '/') as a separator.
     * It then calculates the total number of fragments in the path and removes fragments either from the left (positive count)
     * or the right (negative count) side of the path. If the count is 0, no fragments are removed.
     * @param string $path The input path to remove fragments from.
     * @param int $count The number of fragments to remove. Use a positive value to remove fragments from the left,
     *                   a negative value to remove fragments from the right, or 0 to keep the path unchanged.
     * @return string The modified path with the specified number of fragments removed.
     * @example
     * $originalPath = "C:/xampp/htdocs/appApp/vendor/axm/raxm/src";
     * $modifiedPath = removePathFragments($originalPath, -3);
     * return "C:/xampp/htdocs/appApp"
     */
    public function removePathFragments($path, $count)
    {
        // Split the path into fragments using the directory separator.
        $fragments = explode(DIRECTORY_SEPARATOR, $path);

        // Calculate the total number of fragments in the path.
        $totalFragments = count($fragments);

        // Determine whether to remove fragments from the left or right.
        if ($count > 0) {
            // Remove fragments from the left.
            $resultFragments = array_slice($fragments, $count);
        } elseif ($count < 0) {
            // Calculate the number of fragments to remove from the right.
            $removeCount = abs($count);

            // Remove fragments from the right.
            $resultFragments = array_slice($fragments, 0, $totalFragments - $removeCount);
        } else {
            // If $count is 0, no fragments are removed.
            $resultFragments = $fragments;
        }

        // Reconstruct the path from the remaining fragments.
        $resultPath = implode(DIRECTORY_SEPARATOR, $resultFragments);

        return $resultPath;
    }
}
