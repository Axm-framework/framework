<?php

namespace Http;

use RuntimeException;

/**
 * Class Request
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\HTTP
 */
abstract class URI
{
    /**
     * List of URI segments.
     * Starts at 1 instead of 0
     * @var array
     */
    protected $segments = [];

    /**
     * The URI Scheme.
     * @var string
     */
    protected $scheme = 'http';

    /**
     * URI User Info
     * @var string
     */
    protected $user;

    /**
     * URI User Password
     * @var string
     */
    protected $password;

    /**
     * URI Host
     * @var string
     */
    protected $host;

    /**
     * URI Port
     * @var int
     */
    protected $port;

    /**
     * URI path.
     * @var string
     */
    protected $path;

    /**
     * The name of any fragment.
     * @var string
     */
    protected $fragment = '';

    /**
     * The query string.
     * @var array
     */
    protected $query = [];

    /**
     * Default schemes/ports.
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
     * @var bool
     */
    protected $showPassword = false;

    /**
     * If true, will continue instead of throwing exceptions.
     * @var bool
     */
    protected $silent = false;

    /**
     * If true, will use raw query string.
     * @var bool
     */
    protected $rawQueryString = false;

    /**
     * If true, will use raw query string.
     * @var bool
     */
    protected $uri;

    /**
     * The encryption key resolver callable.
     * @var string
     */
    protected $key = null;

    /**
     * 
     */
    private const HASH_ALGORITHM = 'sha512';

    /**
     * Returns the cleaned and formatted URI for the current request.
     * @return string The cleaned and formatted URI for the current request.
     */
    // public function getUri(): ?string
    // {
    //     $uri = rawurldecode($_SERVER['REQUEST_URI']);

    //     if ($pos = strpos($uri, '?')) {
    //         $uri = substr($uri, 0, $pos);
    //     }

    //     // Delete the base path
    //     if (CLEAN_URI_PATH !== '/') {
    //         $uri = str_replace(CLEAN_URI_PATH, '', $uri);
    //     }

    //     return '/' . trim($uri, '/');
    // }

    /**
     * Builds a representation of the string from the component parts.    
     */
    protected function createURIString(
        ?string $scheme = null,
        ?string $host = null,
        ?string $path = null,
        string|object|array $query = null,
        ?string $fragment = null
    ): string {
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
     * @return string The URI scheme.
     */
    public function getScheme(): string
    {
        // Check for forced HTTPS
        if (config('app.forceGlobalSecureRequests')) {
            $this->scheme = 'https';
        }

        return $this->scheme ?? $_SERVER['REQUEST_SCHEME'];
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
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
     * @return string The URI path.
     */
    protected function getPath(): string
    {
        return $this->path ?? '';
    }

    /**
     * Retrieve a filtered query string based on the given options.
     *
     * @param array $options An array of options to filter the query string.
     *                       Possible options:
     *                       - 'except': An array of keys to exclude from the query string.
     *                       - 'only': An array of keys to include in the query string.
     *
     * @return array|null The filtered query string based on the provided options.
     *                    If the options are not provided or not in the expected format, 
     * the original query is returned.
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

        return $query;
    }

    /**
     * Retrieve the query string
     *
     * @param array $options
     * @return string
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
     */
    protected function getSegment(int $number, string $default = ''): string
    {
        // The segment should treat the array as 1-based for the user
        // but we still have to deal with a zero-based array.
        $number--;

        if ($number > count($this->segments) && !$this->silent)
            throw new RuntimeException("Segment out of range $number");

        return $this->segments[$number] ?? $default;
    }

    /**
     * Returns the total number of segments.
     */
    protected function getTotalSegments(): int
    {
        return count($this->segments);
    }

    /**
     * Create a new URL by assembling its components.
     *
     * @param string $uri The path component of the URL.
     * @param array $query An associative array representing the query parameters.
     * @return string The newly constructed URL.
     */
    public function createNewUrl(string $uri, array $query = []): string
    {
        // Get the scheme, authority, and fragment components from the current URL
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $fragment = $this->getFragment();

        // Ensure the URI starts with CLEAN_URI_PATH and remove trailing slashes
        $uri = CLEAN_URI_PATH . trim($uri, '/');

        // Get the query component by processing the provided query parameters
        $query = $this->getQuery($query);

        // Create the full URL using the static method createURIString
        return static::createURIString($scheme, $authority, $uri, $query, $fragment);
    }

    /**
     * Get a complete URL by combining its components.
     *
     * @param string|null $url An optional parameter representing the path component of the URL.
     *                         If not provided, the default CLEAN_URI_PATH is used.
     * @return string The fully constructed URL based on the provided or default path, and other components.
     */
    public function getUrl(string $url = null): string
    {
        // Get the scheme, authority, and fragment components from the current URL
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $fragment = $this->getFragment();

        // Use the provided $url or default CLEAN_URI_PATH for the path component
        $cleanedUri = $url ?? CLEAN_URI_PATH ?? '';

        // Get the query component from the current URL
        $query = $this->getQuery();

        // Create the full URL using the static method createURIString
        return static::createURIString($scheme, $authority, $cleanedUri, $query, $fragment);
    }

    /**
     * Get the current URL by combining the base URL and the current URI.
     * @return string The fully constructed current URL, obtained by appending 
     * the current URI to the base URL.
     */
    public function getCurrentUrl(): string
    {
        $baseURL = trim($this->getUrl(), '/');
        $currentURI = trim($this->getUri(), '/');
        $url = $baseURL . '/' . $currentURI;

        return $url;
    }

    /**
     * Generate a signed URL by adding a signature and optional expiration time.
     *
     * @param string $url The URL to be signed.
     * @param int|null $expire An optional expiration timestamp for the signed URL.
     *                         If not provided, a default expiration of 1 hour from the current time is used.
     * @return string The signed URL with added signature and expiration parameters.
     */
    public function signed(string $url, $expire = null)
    {
        // Get the current timestamp
        $now = time();

        // Calculate the expiration timestamp (default: 1 hour from now)
        $expiration = $expire ?: $now + 3600;

        // Generate a signature for the URL
        $signature = $this->generateSignature($url);

        // Prepare query parameters with signature and expiration
        $queryParameters = [
            'signature'  => $signature,
            'expiration' => $expiration
        ];

        // Create a new URL by appending the signature and expiration as query parameters
        $signedUrl = $this->createNewUrl($url . '?' . http_build_query($queryParameters));

        // Return the signed URL
        return $signedUrl;
    }

    /**
     * Check if the current request has a valid signature in its query parameters.
     * @return bool Returns true if the request has a valid signature and has not expired; 
     * otherwise, returns false.
     */
    public function hasValidSignature(): bool
    {
        // Parse the URL components from the current request URI
        $urlParts = parse_url($_SERVER['REQUEST_URI']);

        // Check if the query string is present in the URL
        if (!isset($urlParts['query'])) {
            return false;
        }

        // Parse the query parameters from the query string
        parse_str($urlParts['query'], $queryParameters);

        // Check if 'expiration' and 'signature' parameters are present
        if (
            !isset($queryParameters['expiration']) ||
            !isset($queryParameters['signature'])
        ) {
            return false;
        }

        // Get the expiration timestamp from the query parameters
        $expirationTimestamp = $queryParameters['expiration'];

        // Check if the signature has expired
        if ($expirationTimestamp < time()) {
            return false;
        }

        // Get the signature from the query parameters
        $signature = $queryParameters['signature'];

        // Generate a signature for the current URI
        $generatedSignature = $this->generateSignature($this->getUri());

        // Compare the generated signature with the provided signature
        return hash_equals($generatedSignature, $signature);
    }

    /**
     * Generate a signature for a given URL using HMAC (Hash-based Message Authentication Code).
     *
     * @param string $url The URL for which the signature is to be generated.
     * @return string The generated signature for the given URL.
     */
    private function generateSignature($url)
    {
        // Get the secret key from the environment variables
        $this->key = env('APP_KEY');

        // Prepare the data to be signed, which is the provided URL
        $dataToSign = $url;

        // Generate the signature using HMAC with the specified hash algorithm and the secret key
        $signature = hash_hmac(self::HASH_ALGORITHM, $dataToSign, $this->key);

        // Return the generated signature
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
