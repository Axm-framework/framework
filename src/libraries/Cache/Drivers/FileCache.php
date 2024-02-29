<?php

namespace Cache\Drivers;

use Cache\Cache;
use RuntimeException;

/**
 * FileCache is a caching driver that stores cache data in files.
 *
 * @package Axm\Cache\Drivers
 */
class FileCache extends Cache
{
    public const DEFAULT_CACHE_PATH_PERMISSION = 0777;
    public const DEFAULT_CACHE_FILE_SUFFIX = '.bin';
    public const DEFAULT_CACHE_FILE_PERMISSION = 0666;
    public const DEFAULT_EXPIRE = 31536000; // 1 year

    /**
     * @var string|null The path to the cache directory.
     */
    public $cachePath;

    /**
     * @var int The permission for creating the cache directory.
     */
    public $cachePathPermission = self::DEFAULT_CACHE_PATH_PERMISSION;

    /**
     * @var string The suffix for cache file names.
     */
    public $cacheFileSuffix = self::DEFAULT_CACHE_FILE_SUFFIX;

    /**
     * @var int The permission for creating cache files.
     */
    public $cacheFilePermission = self::DEFAULT_CACHE_FILE_PERMISSION;

    /**
     * @var int The number of directory levels to create within the cache directory.
     */
    public $directoryLevels = 0;

    /**
     * @var bool Whether to embed expiry information in the cache file.
     */
    public $embedExpiry = false;

    /**
     * @var int The default expiration time for cache items (1 year by default).
     */
    public $expire = self::DEFAULT_EXPIRE;

    /**
     * @var int The garbage collection probability (default is 100).
     * Higher values increase the likelihood of garbage collection.
     */
    private $_gcProbability = 100;

    /**
     * @var bool Internal flag to track whether garbage collection has been performed.
     */
    private $_gced = false;

    /**
     * Initializes the cache component.
     *
     * If the cache path is not set, it defaults to the 'cacheViewPath' configuration.
     * If the cache directory doesn't exist, it will be created with the specified permission.
     *
     * @throws RuntimeException If the cache path is not writable.
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
     * @return bool Whether the operation was successful.
     */
    public function flush(): bool
    {
        $this->gc(false);
        return true;
    }

    /**
     * Retrieves a cached value based on a given key.
     *
     * @param string $key The key for the cached value.
     * @return mixed|false The cached value if found, or false if not found or expired.
     */
    public function get(string $key)
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
     *
     * @param string $key The key for the cached value.
     * @param mixed $value The value to be cached.
     * @param int $expire The expiration time for the cache entry in seconds.
     * @return bool Whether the operation was successful.
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
     *
     * @param string $key The key for the cached value.
     * @return bool Whether the operation was successful.
     */
    public function delete(string $key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        return @unlink($cacheFile);
    }

    /**
     * Gets the absolute path to the cache file for a given key.
     *
     * @param string $key The key for the cached value.
     * @return string The absolute path to the cache file.
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
     * @param bool $expiredOnly If true, only expired files will be deleted.
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
     * @return int The GC probability.
     */
    private function getGCProbability(): int
    {
        return $this->_gcProbability;
    }

    /**
     * Sets the garbage collection (GC) probability.
     * @param int $value The GC probability to set, clamped between 0 and 1,000,000.
     */
    private function setGCProbability(int $value): void
    {
        $this->_gcProbability = max(0, min(1000000, $value));
    }

    /**
     * Checks whether a cached file has expired.
     *
     * @param string $cacheFile The absolute path to the cache file.
     * @return bool Whether the cache file has expired.
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
     * @return int The default expiration time in seconds.
     */
    public function getExpire(): int
    {
        return $this->expire;
    }

    /**
     * Gets all cache files in the cache directory.
     * @return array An array of cache file paths.
     */
    public function getAllFiles(): array
    {
        $ext = $this->cacheFileSuffix;
        return glob($this->cachePath . DIRECTORY_SEPARATOR . "*$ext");
    }
}
