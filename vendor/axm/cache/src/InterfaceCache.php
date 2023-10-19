<?php

namespace Axm\Cache;

/**
 * ICache is the interface that must be implemented by cache components.
 *
 * This interface must be implemented by classes supporting caching feature.
 */
interface InterfaceCache
{
    /**
     * Retrieves a value from cache with a specified key.
     * @param string $id a key identifying the cached value
     * @return mixed the value stored in cache, false if the value is not in the cache or expired.
     */
    public function get($id);

    /**
     * Stores a value identified by a key into cache.
     * If the cache already contains such a key, the existing value and
     * expiration time will be replaced with the new ones.
     *
     * @param string $id the key identifying the value to be cached
     * @param mixed $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    public function set($key, $value, $expire = 0);

    /**
     * Deletes a value with the specified key from cache
     * @param string $id the key of the value to be deleted
     * @return boolean whether the deletion is successful
     */
    public function delete($key);

    /**
     * Deletes all values from cache.
     * Be careful of performing this operation if the cache is shared by multiple applications.
     * @return boolean whether the flush operation was successful.
     */
    public function flush();
}
