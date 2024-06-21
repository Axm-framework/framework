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
 * @package Cache\Drivers
 */
class RedisCache extends Cache
{
    /**
     * Redis client instance.
     */
    protected $redis;

    /**
     * Indicates whether Redis is configured as a cluster.
     */
    protected bool $cluster;

    /**
     * Redis connection options.
     */
    protected array $options = [
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
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
            throw new \Exception('Failed to connect to Redis: ' . $e->getMessage());
        }
    }

    /**
     * Gets the value associated with a key from Redis.
     */
    public function get(string $key): false|string|int|array|object|null
    {
        try {
            return $this->redis->get($key);
        } catch (RedisException $e) {
            throw new RuntimeException(sprintf('Error getting value from Redis: %s', $e->getMessage()));
        }
    }

    /**
     * Sets a value identified by a key into Redis.
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
            throw new RuntimeException('Error setting value in Redis: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a value with the specified key from Redis.
     */
    public function delete(string $key): bool
    {
        try {
            return $this->redis->del($key) > 0;
        } catch (RedisException $e) {
            throw new RuntimeException('Error deleting value from Redis: ' . $e->getMessage());
        }
    }

    /**
     * Flushes all values from Redis cache.
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
            throw new \Exception('Error flushing Redis cache: ' . $e->getMessage());
        }
    }

    /**
     * Sets the expiration time for a key in Redis.
     */
    public function expire(string $key, int $expire): bool
    {
        try {
            return $this->redis->expire($key, $expire);
        } catch (RedisException $e) {
            throw new \Exception('Error setting expiration for key in Redis: ' . $e->getMessage());
        }
    }

    /**
     * Gets multiple values from Redis with the specified keys.
     */
    public function mget(array $keys): array
    {
        try {
            return $this->redis->mget($keys);
        } catch (RedisException $e) {
            throw new \Exception('Error getting multiple values from Redis: ' . $e->getMessage());
        }
    }
}
