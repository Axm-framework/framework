<?php

namespace Http;

trait CacheTrait
{

    /**
     * Enables response caching and sets cache options.
     *
     * @param bool   $enableCache  Whether to enable response caching.
     * @param int    $cacheTime    The time in seconds to consider the response cache as valid.
     * @param string $etag         The ETag value for cache validation.
     * @param int    $expires      The expiration time in seconds for the cached response.
     * @return $this
     */
    public function enableResponseCache(bool $enableCache = true, int $cacheTime = 60, string $etag = null, int $expires = null)
    {
        if ($enableCache) {
            $this->setOption(CURLOPT_HEADER, true);
            $this->setOption(CURLOPT_RETURNTRANSFER, true);

            if ($etag !== null) {
                $this->setOption(CURLOPT_HTTPHEADER, ['If-None-Match: ' . $etag]);
            }

            if ($expires !== null) {
                $this->setOption(CURLOPT_HTTPHEADER, ['Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT']);
            }

            $this->setOption(CURLOPT_FOLLOWLOCATION, true); // Required for proper cache handling with redirects
        }

        return $this;
    }


    /**
     * Disables response caching.
     *
     * @return $this
     */
    public function disableResponseCache()
    {
        $this->setOption(CURLOPT_HEADER, false);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_HTTPHEADER, []);

        return $this;
    }
}
