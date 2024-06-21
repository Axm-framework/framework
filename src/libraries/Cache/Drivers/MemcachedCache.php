<?php

namespace Cache\Drivers;

use Cache\Cache;
use Memcached;
use RuntimeException;

/**
 * MemcachedCache is a caching driver that stores cache data in Memcached.
 *
 * @package Axm\Cache\Drivers
 */
class MemcachedCache extends Cache
{
    /**
     * The Memcached instance.
     */
    protected Memcached $memcached;

    /**
     * Memcached server configurations.
     */
    protected array $servers = [
        [
            'host' => 'localhost',
            'port' => 11211,
            'persistent' => true,
            'weight' => 1,
            'timeout' => 1,
            'retry_interval' => 15,
        ],
    ];

    /**
     * Initializes the connection to Memcached.
     */
    public function init()
    {
        try {
            $this->memcached = new Memcached();

            // Configure Memcached servers
            foreach ($this->servers as $server) {
                $this->memcached->addServer(
                    $server['host'],
                    $server['port'],
                    $server['weight'],
                    $server['persistent'],
                    $server['timeout'],
                    $server['retry_interval']
                );
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to connect to Memcached: ' . $e->getMessage());
        }
    }

    /**
     * Gets a value from Memcached for the given key.
     */
    public function get(string $key): false|string|int|array|object|null
    {
        try {
            $value = $this->memcached->get($key);

            // Automatically unserialize objects
            return ($value !== false && is_string($value)) ? unserialize($value) : $value;
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Error getting value from Memcached: %s', $e->getMessage()));
        }
    }

    /**
     * Stores a value in Memcached with the given key.
     */
    public function set(string $key, mixed $value, int $expire = 0): bool
    {
        try {
            // Automatically serialize objects
            $serializedValue = (is_object($value) || is_array($value)) ? serialize($value) : $value;

            return $this->memcached->set($key, $serializedValue, $expire);
        } catch (\Exception $e) {
            throw new RuntimeException('Error setting value in Memcached: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a value from Memcached with the given key.
     */
    public function delete(string $key): bool
    {
        try {
            return $this->memcached->delete($key);
        } catch (\Exception $e) {
            throw new RuntimeException('Error deleting value from Memcached: ' . $e->getMessage());
        }
    }

    /**
     * Flushes all values from Memcached.
     */
    public function flush(): bool
    {
        try {
            return $this->memcached->flush();
        } catch (\Exception $e) {
            throw new \Exception('Error flushing Memcached cache: ' . $e->getMessage());
        }
    }

}
