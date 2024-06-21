<?php

namespace Cache\Drivers;

use Cache\Cache;
use RuntimeException;

/**
 * FileCache is a caching driver that stores cache data in files.
 *
 * @package Cache\Drivers
 */
class FileCache extends Cache
{
    public const DEFAULT_CACHE_PATH_PERMISSION = 0777;
    public const DEFAULT_CACHE_FILE_SUFFIX = '.bin';
    public const DEFAULT_CACHE_FILE_PERMISSION = 0666;
    public const DEFAULT_EXPIRE = 31536000; // 1 year

    /**
     * The path to the cache directory.
     */
    public ?string $cachePath;

    /**
     * The permission for creating the cache directory.
     */
    public int $cachePathPermission = self::DEFAULT_CACHE_PATH_PERMISSION;

    /**
     * The suffix for cache file names.
     */
    public string $cacheFileSuffix = self::DEFAULT_CACHE_FILE_SUFFIX;

    /**
     * The permission for creating cache files.
     */
    public int $cacheFilePermission = self::DEFAULT_CACHE_FILE_PERMISSION;

    /**
     * The number of directory levels to create within the cache directory.
     */
    public int $directoryLevels = 0;

    /**
     * Whether to embed expiry information in the cache file.
     */
    public bool $embedExpiry = false;

    /**
     * The default expiration time for cache items (1 year by default).
     */
    public int $expire = self::DEFAULT_EXPIRE;

    /**
     * The garbage collection probability (default is 100).
     */
    private int $_gcProbability = 100;

    /**
     * Internal flag to track whether garbage collection has been performed.
     */
    private bool $_gced = false;

    /**
     * Initializes the cache component.
     *
     * If the cache path is not set, it defaults to the 'cacheViewPath' configuration.
     * If the cache directory doesn't exist, it will be created with the specified permission.
     */
    public function init(): void
    {
        if ($this->cachePath === null) {
            $this->cachePath = config('paths.cacheViewPath');

            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, $this->cachePathPermission, true);
            } elseif (!is_writable($this->cachePath)) {
                throw new RuntimeException('Cache path is not writable.');
            }
        }
    }

    /**
     * Clears all cached data.
     */
    public function flush(): bool
    {
        $this->gc(false);
        return true;
    }

    /**
     * Retrieves a cached value based on a given key.
     */
    public function get(string $key): false|string|int|array|object|null
    {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile) && !$this->isExpired($cacheFile)) {
            return $this->embedExpiry
                ? substr(file_get_contents($cacheFile), 10)
                : file_get_contents($cacheFile);
        }

        $this->delete($key);
        return false;
    }

    /**
     * Stores a value in the cache with a specified key and optional expiration time.
     */
    public function set(string $key, $value, int $expire = 0): bool
    {
        $cacheFile = $this->getCacheFile($key);
        if ($expire <= 0) {
            $expire = self::DEFAULT_EXPIRE;
        }

        $data = $this->embedExpiry ? time() + $expire . "\n" . $value : $value;
        if (@file_put_contents($cacheFile, $data, LOCK_EX) !== false) {
            @chmod($cacheFile, $this->cacheFilePermission);
            return true;
        }

        return false;
    }

    /**
     * Deletes a cached value based on a given key.
     */
    public function delete(string $key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        return @unlink($cacheFile);
    }

    /**
     * Gets the absolute path to the cache file for a given key.
     */
    private function getCacheFile(string $key): string
    {
        $base = $this->cachePath . DIRECTORY_SEPARATOR . md5($key);
        if ($this->directoryLevels > 0) {
            $hash = md5($key);

            for ($i = 0; $i < $this->directoryLevels; ++$i) {
                $base .= DIRECTORY_SEPARATOR . substr($hash, $i + 1, 2);
            }
        }

        return $base . $this->cacheFileSuffix;
    }

    /**
     * Performs garbage collection on expired cache files.
     */
    private function gc(bool $expiredOnly = true): void
    {
        $this->_gced = true;
        foreach (glob($this->cachePath . DIRECTORY_SEPARATOR . '*' . $this->cacheFileSuffix) as $file) {
            if ($file[0] === '.') {
                continue;
            }

            if ($expiredOnly && $this->isExpired($file)) {
                @unlink($file);
            } elseif (!$expiredOnly) {
                @unlink($file);
            }
        }

        $this->_gced = false;
    }

    /**
     * Gets the garbage collection (GC) probability.
     */
    private function getGCProbability(): int
    {
        return $this->_gcProbability;
    }

    /**
     * Sets the garbage collection (GC) probability.
     */
    private function setGCProbability(int $value): void
    {
        $this->_gcProbability = max(0, min(1000000, $value));
    }

    /**
     * Checks whether a cached file has expired.
     */
    private function isExpired(string $cacheFile): bool
    {
        if ($this->embedExpiry) {
            $data = file_get_contents($cacheFile);
            $timestamp = (int) substr($data, 0, 10);

            return $timestamp > 0 && time() > $timestamp + (int) substr($data, 10);
        }

        clearstatcache();
        return filemtime($cacheFile) + $this->getExpire() < time();
    }

    /**
     * Gets the default expiration time for cache files.
     */
    public function getExpire(): int
    {
        return $this->expire;
    }

    /**
     * Gets all cache files in the cache directory.
     */
    public function getAllFiles(): array
    {
        $ext = $this->cacheFileSuffix;
        return glob($this->cachePath . DIRECTORY_SEPARATOR . "*$ext");
    }
}
