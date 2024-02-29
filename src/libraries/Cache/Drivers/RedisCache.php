<?php

declare(strict_types=1);

namespace Cache\Drivers;

use Cache\Cache;
use Redis;
use RedisCluster;
use RedisException;
use RuntimeException;

/**
 * RedisCache - Implementation of the class for handling cache with Redis.
 *
 * @package Axm\Cache\Drivers
 */
class RedisCache extends Cache
{
    /**
     * @var Redis|RedisCluster Redis client instance.
     */
    protected $redis;

    /**
     * @var bool Indicates whether Redis is configured as a cluster.
     */
    protected $cluster;

    /**
     * @var array Redis connection options.
     */
    protected $options = [
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
        'timeout' => 2.5,
        'read_timeout' => 2.5,
    ];

    /**
     * Initializes the connection to Redis.
     *
     * @throws \Exception If the connection to Redis fails.
     */
    public function init()
    {
        try {
            if (class_exists('RedisCluster')) {
                $this->cluster = true;
                $this->redis = new RedisCluster(null, [$this->options]);
            } elseif (extension_loaded('redis')) {
                $this->cluster = false;
                $this->redis = new Redis();
                $this->redis->connect(
                    $this->options['host'],
                    $this->options['port'],
                    $this->options['timeout'],
                    null,
                    0,
                    $this->options['read_timeout']
                );
            }
        } catch (RedisException $e) {
            // Handle the Redis connection exception as needed
            throw new \Exception('Failed to connect to Redis: ' . $e->getMessage());
        }
    }

    /**
     * Gets the value associated with a key from Redis.
     *
     * @param string $key The key to look up in Redis.
     * @return mixed The value associated with the key, or null if not found.
     * @throws RuntimeException If an error occurs while getting the value.
     */
    public function get(string $key)
    {
        try {
            return $this->redis->get($key);
        } catch (RedisException $e) {
            throw new RuntimeException(sprintf('Error getting value from Redis: %s', $e->getMessage()));
        }
    }

    /**
     * Sets a value identified by a key into Redis.
     *
     * @param string $key The key identifying the value to be cached.
     * @param mixed $value The value to be cached.
     * @param int $expire The number of seconds until the cached value will expire. 0 means never expire.
     * @return bool True if the value is successfully stored in Redis, false otherwise.
     * @throws RuntimeException If an error occurs while setting the value.
     */
    public function set(string $key, $value, int $expire = 0): bool
    {
        try {
            if ($expire > 0) {
                return $this->redis->setex($key, $expire, $value);
            } else {
                return $this->redis->set($key, $value);
            }
        } catch (RedisException $e) {
            // Handle the Redis operation exception as needed
            throw new RuntimeException('Error setting value in Redis: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a value with the specified key from Redis.
     *
     * @param string $key The key of the value to be deleted.
     * @return bool True if the value is successfully deleted, false otherwise.
     * @throws RuntimeException If an error occurs while deleting the value.
     */
    public function delete(string $key): bool
    {
        try {
            return $this->redis->del($key) > 0;
        } catch (RedisException $e) {
            // Handle the Redis operation exception as needed
            throw new RuntimeException('Error deleting value from Redis: ' . $e->getMessage());
        }
    }

    /**
     * Flushes all values from Redis cache.
     *
     * @return bool True if the flush operation was successful, false otherwise.
     * @throws \Exception If an error occurs while flushing the cache.
     */
    public function flush(): bool
    {
        try {
            if ($this->cluster) {
                return $this->redis->flushall();
            } else {
                return $this->redis->flushDB();
            }
        } catch (RedisException $e) {
            // Handle the Redis operation exception as needed
            throw new \Exception('Error flushing Redis cache: ' . $e->getMessage());
        }
    }

    /**
     * Sets the expiration time for a key in Redis.
     *
     * @param string $key The key for which to set the expiration time.
     * @param int $expire The number of seconds until the key will expire.
     * @return bool True if the expiration time is set successfully, false otherwise.
     * @throws \Exception If an error occurs while setting the expiration time.
     */
    public function expire(string $key, int $expire): bool
    {
        try {
            return $this->redis->expire($key, $expire);
        } catch (RedisException $e) {
            // Handle the Redis operation exception as needed
            throw new \Exception('Error setting expiration for key in Redis: ' . $e->getMessage());
        }
    }

    /**
     * Gets multiple values from Redis with the specified keys.
     *
     * @param array $keys A list of keys identifying the cached values.
     * @return array A list of cached values indexed by the keys.
     * @throws \Exception If an error occurs while getting multiple values.
     */
    public function mget(array $keys): array
    {
        try {
            return $this->redis->mget($keys);
        } catch (RedisException $e) {
            // Handle the Redis operation exception as needed
            throw new \Exception('Error getting multiple values from Redis: ' . $e->getMessage());
        }
    }
}
