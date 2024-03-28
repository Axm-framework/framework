<?php

declare(strict_types=1);

/**
 * Class Container
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Framework
 */
class Container
{
    private array $storage = [];
    private $instances = [];

    public static function getInstance()
    {
        if (!isset(static::$instances)) {
            static::$instances = new self();
        }

        return static::$instances;
    }

    public function bind(string $key, mixed $value): mixed
    {
        $this->storage[$key] = $value;
        return $value;
    }

    public function singleton(string $key, object $value)
    {
        if (!$this->has($key)) {
            return $this->bind($key, $value);
        }

        return $this->storage[$key];
    }

    public function key(string $key)
    {
        if ($this->has($key)) {
            return $key;
        }

        throw new \Exception("Key '{$key}' not found in container.");
    }

    public function resolve(string $key)
    {
        $resolver = $this->storage[$this->key($key)];

        return $resolver($this);
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    public function isSingleton(string $key): bool
    {
        return $this->has($key);
    }

    public function get(string $key): mixed
    {
        if ($this->has($key)) {
            $class = $this->storage[$key];
            $instance = is_object($class)
                ? $class
                : new $class();

            $this->storage[$key] = $instance;
            return $instance;
        }

        return false;
    }

    public function set(string $key, mixed $value): void
    {
        $this->storage[$this->key($key)] = $value;
    }

    public function components(array $values): void
    {
        $this->storage += $values;
    }

    public function all(): array
    {
        return $this->storage;
    }

    public function remove(string $key): void
    {
        unset($this->storage[$this->key($key)]);
    }

    public function clear(): void
    {
        $this->storage = [];
    }

    /**
     * Array Access
     */
    public function offsetExists(string $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(string $offset): bool|object
    {
        return $this->get($offset);
    }

    public function offsetSet(string $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(string $offset): void
    {
        $this->remove($offset);
    }

    public function count(): int
    {
        return count($this->storage);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->storage);
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }
}
