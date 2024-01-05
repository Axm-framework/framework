<?php

declare(strict_types=1);

namespace Axm;

use Closure;
use ReflectionFunction;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use InvalidArgumentException;


/**
 *  Class Container 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class Container
{
    /**
     * @var Container|null
     */
    private static ?Container $instance = null;

    /**
     * @var array
     */
    private array $services = [];

    /**
     * @var array
     */
    private array $sharedInstances = [];


    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

    /**
     * Get the instance of the container.
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get a service by its ID.
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if (isset($this->sharedInstances[$id])) {
            return $this->sharedInstances[$id];
        }

        if (!$this->has($id)) {
            throw new RuntimeException("Service not found: $id");
        }

        $service = $this->services[$id];

        $parameters = $this->getParameters($service['closure']);
        $instance = $service['closure'](...$parameters);

        if (!is_object($instance)) {
            throw new RuntimeException("Invalid instance returned by service: $id");
        }

        if (isset($service['initial'])) {
            $service['initial']();
        }

        if (isset($service['shared']) && $instance !== null) {
            $this->sharedInstances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Set a service in the container.
     *
     * @param string $id
     * @param Closure $closure
     * @param bool $shared
     */
    public function set(string $id, Closure $serviceClosure, ?Closure $initial = null, bool $shared = false)
    {
        // if ($this->has($id)) {
        //     throw new RuntimeException("Service already exists: $id");
        // }

        $this->services[$id] = [
            'closure' => $serviceClosure,
            'initial' => $initial,
            'shared'  => $shared,
        ];
    }

    /**
     * Register a class as a singleton service in the container.
     *
     * @param string $classname
     * @param array|null $constructorArgs
     * @return Container
     * @throws RuntimeException
     */
    public function singleton(string $classname, ?array $constructorArgs = null)
    {
        $this->set($classname, function () use ($classname, $constructorArgs) {
            try {
                $reflectionClass = new ReflectionClass($classname);
                $instance = ($constructorArgs !== null)
                    ? $reflectionClass->newInstanceArgs($constructorArgs)
                    : $reflectionClass->newInstance();
                return $instance;
            } catch (ReflectionException $e) {
                throw new RuntimeException("Failed to instantiate class: $classname", 0, $e);
            }
        }, null, true);

        return $this;
    }

    /**
     * Set or change the shared instance of a service.
     *
     * @param string $id
     * @param bool $shared
     * @return Container
     */
    public function setShared(string $id, bool $shared): Container
    {
        if ($this->has($id)) {
            $this->services[$id]['shared'] = $shared;
        }

        return $this;
    }

    /**
     * Check if an instance is shared.
     *
     * @param string $id
     * @return bool
     */
    public function isShared(string $id): bool
    {
        return isset($this->services[$id]['shared']);
    }

    /**
     * Define an alias for a service.
     *
     * @param string $id
     * @param string $alias
     * @return Container
     */
    public function setAlias(string $id, string $alias): Container
    {
        if ($this->has($id)) {
            $this->services[$alias] = $this->services[$id];
            unset($this->services[$id]);
        }

        return $this;
    }

    /**
     * Extend a service definition with additional configuration.
     *
     * @param string $id
     * @param Closure $extension
     * @return Container
     */
    public function extend(string $id, Closure $extension): Container
    {
        if ($this->has($id)) {
            $originalClosure = $this->services[$id]['closure'];

            $this->services[$id]['closure'] = function (...$args) use ($originalClosure, $extension) {
                $instance = $originalClosure(...$args);
                return $extension($instance, $this);
            };
        }

        return $this;
    }

    /**
     * Load service configurations from a file.
     *
     * @param string $file
     * @throws RuntimeException
     */
    public function load(string $file)
    {
        $filePath = $file;

        if (!is_file($filePath)) {
            throw new RuntimeException("Configuration file not found: $filePath");
        }

        $config = include_once $filePath;

        foreach ($config as $id => $service) {
            if (isset($service['class'])) {
                $class   = $service['class'];
                $initial = $service['initial'] ?? null;
                $shared  = $service['shared'] ?? false;

                $closure = function (...$args) use ($class) {
                    return (new ReflectionClass($class))->newInstanceArgs($args);
                };

                $this->set($id, $closure, $initial, $shared);
            } elseif (isset($service['closure'])) {
                $closure = $service['closure'];
                $initial = $service['initial'] ?? null;
                $shared  = $service['shared'] ?? false;

                $this->set($id, $closure, $initial, $shared);
            } else {
                throw new RuntimeException("Invalid service configuration for: $id");
            }
        }
    }

    /**
     * Remove a service from the container.
     *
     * @param string $id
     */
    public function remove(string $id): void
    {
        unset($this->services[$id]);
        unset($this->sharedInstances[$id]);
    }

    /**
     * Clear all services from the container.
     */
    public function clear(): void
    {
        $this->services = [];
        $this->sharedInstances = [];
    }

    /**
     * Get all registered service IDs in the container.
     *
     * @return array
     */
    public function getServices(): array
    {
        return array_keys($this->services);
    }

    /**
     * Get all shared service IDs registered in the container.
     *
     * @return array
     */
    public function getSharedServices(): array
    {
        $sharedServices = [];

        foreach ($this->services as $id => $service) {
            if ($service['shared']) {
                $sharedServices[] = $id;
            }
        }

        return $sharedServices;
    }

    /**
     * Register multiple services at once.
     *
     * @param array $services
     * @throws RuntimeException
     */
    public function register(array $services): void
    {
        foreach ($services as $id => $service) {
            if (isset($service['class'])) {
                $class   = $service['class'];
                $initial = $service['initial'] ?? null;
                $shared  = $service['shared'] ?? false;

                $closure = function (...$args) use ($class) {
                    return (new ReflectionClass($class))->newInstanceArgs($args);
                };

                $this->set($id, $closure,  $initial, $shared);
            } elseif (isset($service['closure'])) {
                $closure = $service['closure'];
                $initial = $service['initial'] ?? null;
                $shared  = $service['shared'] ?? false;

                $this->set($id, $closure,  $initial, $shared);
            } else {
                throw new RuntimeException("Invalid service configuration for: $id");
            }
        }
    }

    /**
     * Load all service configuration files from a directory.
     *
     * @param string $directory
     * @param string $ext
     */
    public function loadFromDirectory(string $directory, string $ext = '.php'): void
    {
        $files = glob("$directory/*$ext");

        foreach ($files as $file) {
            $this->load($file);
        }
    }

    /**
     * Get the parameters of a callable.
     *
     * @param callable $callable
     * @return array
     * @throws InvalidArgumentException
     */
    private function getParameters(callable $callable): array
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Callable must be valid');
        }

        $reflection = is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction($callable);

        $parameters = [];
        foreach ($reflection->getParameters() as $param) {
            $paramType = $param->getType();

            if ($paramType instanceof ReflectionNamedType) {
                $typeName = $paramType->getName();
                if ($paramType->allowsNull()) {
                    $typeName = '?' . $typeName; // Handle nullable types
                }
            } else {
                $typeName = 'mixed'; // Handle non-class types (int, bool, etc.)
            }

            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
                if (is_array($defaultValue) || is_scalar($defaultValue)) {
                    $parameters[] = "$typeName \${$param->getName()} = " . var_export($defaultValue, true);
                } else {
                    $parameters[] = "$typeName \${$param->getName()} = null";
                }
            } elseif ($param->isVariadic()) {
                $parameters[] = "$typeName ... \${$param->getName()}";
            } else {
                $parameters[] = "$typeName \${$param->getName()}";
            }
        }

        return $parameters;
    }

    /**
     * Dump information about a service.
     *
     * @param string|null $id
     * @param array|null $options
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function dump($id = null, array $options = null)
    {
        if (is_null($id)) return dump($this->services);

        if (!isset($this->services[$id])) {
            throw new InvalidArgumentException("Service not found: $id");
        }

        $data = [
            'id' => $id,
            'closure' => $this->services[$id]['closure'],
            'initial' => $this->services[$id]['initial'],
            'shared'  => $this->services[$id]['shared'],
        ];

        if (isset($options['class'])) {
            $data['class'] = $this->services[$id]['class'];
        }

        return dump($data);
    }

    /**
     * Magic method to get a service by property access.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic method to set a service by property access.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic method to check if a service exists by property access.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Magic method to remove a service by property access.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->remove($key);
    }
}
