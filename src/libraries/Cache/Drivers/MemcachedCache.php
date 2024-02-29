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
     * @var Memcached The Memcached instance.
     */
    protected $memcached;

    /**
     * @var array Memcached server configurations.
     */
    protected $servers = [
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
     *
     * @throws \Exception If failed to connect to Memcached.
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
            // Handle the Memcached connection exception as needed
            throw new \Exception('Failed to connect to Memcached: ' . $e->getMessage());
        }
    }

    /**
     * Gets a value from Memcached for the given key.
     *
     * @param string $key The key to retrieve the value.
     * @return mixed|null The stored value or null if not found.
     * @throws \RuntimeException If an error occurs during the operation with Memcached.
     */
    public function get($key)
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
     *
     * @param string $key The key to store the value.
     * @param mixed $value The value to store.
     * @param int $expire The expiration time in seconds (0 for never expire).
     * @return bool True if stored successfully, false otherwise.
     * @throws \RuntimeException If an error occurs during the operation with Memcached.
     */
    public function set($key, $value, $expire = 0)
    {
        try {
            // Automatically serialize objects
            $serializedValue = (is_object($value) || is_array($value)) ? serialize($value) : $value;

            return $this->memcached->set($key, $serializedValue, $expire);
        } catch (\Exception $e) {
            // Handle the Memcached operation exception as needed
            throw new RuntimeException('Error setting value in Memcached: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a value from Memcached with the given key.
     *
     * @param string $key The key of the value to be deleted.
     * @return bool True if deleted successfully, false otherwise.
     * @throws \RuntimeException If an error occurs during the operation with Memcached.
     */
    public function delete($key)
    {
        try {
            return $this->memcached->delete($key);
        } catch (\Exception $e) {
            // Handle the Memcached operation exception as needed
            throw new RuntimeException('Error deleting value from Memcached: ' . $e->getMessage());
        }
    }

    /**
     * Flushes all values from Memcached.
     *
     * @return bool True if the flush operation was successful, false otherwise.
     * @throws \RuntimeException If an error occurs during the operation with Memcached.
     */
    public function flush()
    {
        try {
            return $this->memcached->flush();
        } catch (\Exception $e) {
            // Handle the Memcached operation exception as needed
            throw new \Exception('Error flushing Memcached cache: ' . $e->getMessage());
        }
    }

}
