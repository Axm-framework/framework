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
     * Retrieves a value from cache associated with the given key.
     */
    public function get(string $key): false|string|int|array|object|null;

    /**
     * Stores a value identified by a key into cache.
     */
    public function set(string $key, mixed $value, int $expire = 0): bool;

    /**
     * Deletes a value with the specified key from cache.
     */
    public function delete(string $key): bool;
}