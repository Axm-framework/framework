<?php

declare(strict_types=1);

namespace Http;

use Http\OAuthTrait;
use Http\CacheTrait;
use Http\ProxyTrait;
use CurlHandle;

/**
 * Interface CurlHandlerInterface
 */
interface CurlHandlerInterface
{
    public function get(string $url, array $headers = []): array;
    public function post(string $url, array $data = [], array $headers = []): array;
    public function put(string $url, array $data = [], array $headers = []): array;
    public function delete(string $url, array $data = [], array $headers = []): array;
    public function head(string $url, array $headers = []): array;
}

/**
 * CurlHandler - A simple PHP class for handling cURL requests.
 *
 * This class provides an easy-to-use interface for making HTTP requests using cURL.
 * It supports common HTTP methods like GET, POST, PUT, DELETE, and HEAD.
 * @author Juan Cristobal
 */
class Curl implements CurlHandlerInterface
{
    use OAuthTrait;
    use CacheTrait;
    use ProxyTrait;

    private ?CurlHandle $ch;
    private $retryAttempts;
    private $transientErrorCodes;

    /**
     * Constructor - Initializes the cURL handle.
     */
    public function __construct()
    {
        $this->ch = curl_init();
    }

    /**
     * Sends a GET request.
     */
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, [], $headers);
    }

    /**
     * Sends a POST request.
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * Sends a PUT request.
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * Sends a DELETE request.
     */
    public function delete(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('DELETE', $url, $data, $headers);
    }

    /**
     * Sends a HEAD request.
     */
    public function head(string $url, array $headers = []): array
    {
        return $this->request('HEAD', $url, [], $headers);
    }

    /**
     * Performs the cURL request.
     */
    private function request(string $method, string $url, array $data = [], array $headers = [])
    {
        $this->setCommonOptions($url, $method);

        if ($method !== 'GET') {
            $this->setRequestBody($data);
        }

        if (!empty($headers)) {
            $this->setRequestHeaders($headers);
        }

        $response = curl_exec($this->ch);

        if ($response === false) {
            $error = curl_error($this->ch);
            $errno = curl_errno($this->ch);
            throw new \Exception("Error in the cURL request (Error Code: $errno): $error");
        }

        $info = curl_getinfo($this->ch);
        $this->reset();

        return $this->buildResponse($response, $info);
    }

    /**
     * Builds the response array.
     */
    private function buildResponse(string $response, array $info): array
    {
        return [
            'response' => $response,
            'info' => $info,
        ];
    }

    /**
     * Sets common cURL options.
     */
    private function setCommonOptions(string $url, string $method): bool
    {
        return curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
    }

    /**
     * Sets a cURL option.
     */
    public function setCurlOption(int $option, $value)
    {
        curl_setopt($this->ch, $option, $value);
        return $this;
    }

    /**
     * Sets the request body for non-GET requests.
     */
    private function setRequestBody(array $data, string $contentType = 'array')
    {
        if (!empty($data)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->preparePostData($data, $contentType));
        }
    }

    /**
     * Prepares POST data for the request.
     */
    private function preparePostData(array $data, string $contentType = 'json'): mixed
    {
        return match ($contentType) {
            'json'  => json_encode($data),
            'form'  => http_build_query($data),
            default => $data
        };
    }

    /**
     * Sets the request headers.
     */
    private function setRequestHeaders(array $headers)
    {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Sets a cURL option.
     */
    public function setOption(int $option, $value)
    {
        curl_setopt($this->ch, $option, $value);
        return $this;
    }

    /**
     * Sets multiple cURL options at once.
     */
    public function setOptions(array $options)
    {
        curl_setopt_array($this->ch, $options);
        return $this;
    }

    /**
     * Sets the cURL timeout option.
     */
    public function setTimeout(int $timeout)
    {
        return $this->setOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Sets the cURL connect timeout option.
     */
    public function setConnectTimeout(int $timeout)
    {
        return $this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
    }

    /**
     * Sets the cURL cookie file option.
     * @return $this
     */
    public function useCookieFile(string $cookieFile)
    {
        return $this->setOption(CURLOPT_COOKIEFILE, $cookieFile);
    }

    /**
     * Sets the cURL cookie jar option.
     * @return $this
     */
    public function saveCookieFile(string $cookieFile)
    {
        return $this->setOption(CURLOPT_COOKIEJAR, $cookieFile);
    }

    /**
     * Enables or disables cURL sessions.
     * @return $this
     */
    public function enableSessions($enable = true, $sessionFile = null)
    {
        if ($enable) {
            if ($sessionFile) {
                $this->setOption(CURLOPT_COOKIEFILE, $sessionFile);
                $this->setOption(CURLOPT_COOKIEJAR, $sessionFile);
            } else {
                $this->setOption(CURLOPT_COOKIESESSION, true);
            }
        } else {
            $this->setOption(CURLOPT_COOKIESESSION, false);
        }

        return $this;
    }

    /**
     * Executes multiple cURL handles in parallel.
     * @return $this
     */
    public function multiExec(array $handles)
    {
        $multiHandle = curl_multi_init();

        foreach ($handles as $handle) {
            curl_multi_add_handle($multiHandle, $handle);
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
        } while ($running > 0);

        foreach ($handles as $handle) {
            curl_multi_remove_handle($multiHandle, $handle);
        }

        curl_multi_close($multiHandle);

        return $this;
    }

    /**
     * Sets the number of retry attempts in case of failures.
     */
    public function setRetryAttempts(int $attempts)
    {
        $this->retryAttempts = max(0, $attempts);
        return $this;
    }

    /**
     * Sets the HTTP status codes considered as transient errors for retry attempts.
     */
    public function setTransientErrorCodes(array $codes)
    {
        $this->transientErrorCodes = $codes;
        return $this;
    }

    /**
     * Resets the cURL handle.
     */
    public function reset()
    {
        curl_reset($this->ch);
        return $this;
    }

    /**
     * Closes the cURL handle.
     */
    public function close()
    {
        curl_close($this->ch);
    }
}
