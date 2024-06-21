<?php

namespace Http;

trait CacheTrait
{

    /**
     * Enables response caching and sets cache options.
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
     */
    public function disableResponseCache()
    {
        $this->setOption(CURLOPT_HEADER, false);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_HTTPHEADER, []);

        return $this;
    }
}
