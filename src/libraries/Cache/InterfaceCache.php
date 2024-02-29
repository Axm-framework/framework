<?php

namespace Cache;

/**
 * Interface for cache components.
 *
 * This interface must be implemented by classes supporting caching features.
 */
interface InterfaceCache
{
    /**
     * Retrieves a value from cache with a specified key.
     *
     * @param string $key A key identifying the cached value.
     * @return mixed|false The value stored in the cache, false if the value is not in the cache or expired.
     */
    public function get(string $key);

    /**
     * Stores a value identified by a key into cache.
     *
     * If the cache already contains such a key, the existing value and
     * expiration time will be replaced with the new ones.
     * @param string $key The key identifying the value to be cached.
     * @param mixed $value The value to be cached.
     * @param int $expire The number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool True if the value is successfully stored into the cache, false otherwise.
     */
    public function set(string $key, $value, int $expire = 0);

    /**
     * Deletes a value with the specified key from cache.
     *
     * @param string $key The key of the value to be deleted.
     * @return bool Whether the deletion is successful.
     */
    public function delete(string $key);

    /**
     * Deletes all values from cache.
     *
     * Be careful when performing this operation if the cache is shared by multiple applications.
     * @return bool Whether the flush operation was successful.
     */
    public function flush();
}
