<?php

class Container
{
    private $storage = [];
    private static $instance;


    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public function bind(string $key, $value)
    {
        $this->storage[$key] = $value;
        return $value;
    }

    public function singleton(string $key, callable $value)
    {
        $this->bind($key, function () use ($key, $value) {
            static $instance;
            $instance[$key] ??= $value($this);

            return $instance[$key];
        });

        return $this;
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

    public function get(string $key): object
    {
        if ($this->has($key)) {
            $class = $this->storage[$key] ?? null;

            $instance = is_object($class)
                ? $class
                : $this->storage[$key] = new $class();

            return $instance;
        }

        return false;
    }

    public function set(string $key, $value): void
    {
        $this->storage[$this->key($key)] = $value;
    }

    public function services(array $values): void
    {
        $this->storage += $values;
    }

    public function all(): array
    {
        return $this->storage;
    }

    public function remove(string $key)
    {
        unset($this->storage[$this->key($key)]);
    }

    public function clear()
    {
        $this->storage = [];
    }

    /**
     * Array Access
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function count()
    {
        return count($this->storage);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->storage);
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    public function __isset(string $key)
    {
        return $this->has($key);
    }

    public function __unset(string $key)
    {
        $this->remove($key);
    }
}
