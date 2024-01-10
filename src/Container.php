<?php

declare(strict_types=1);

namespace Axm;

use Closure;
use ReflectionFunction;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
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

    /**
     * @var string
     */
    private string $nameMethodBoot = 'boot';

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
     * @param string $alias
     * @return mixed
     */
    public function get($alias)
    {
        if (isset($this->sharedInstances[$alias])) {
            return $this->sharedInstances[$alias];
        }

        if (!$this->has($alias))
            throw new RuntimeException("Service not found: $alias");

        $service = $this->services[$alias];

        $parameters = $this->getParameters($service['closure']);
        $instance = $service['closure'](...$parameters);

        if (!is_object($instance))
            throw new RuntimeException("Invalid instance returned by service: $alias");

        if (isset($service['shared']) && $instance !== null) {
            $this->sharedInstances[$alias] = $instance;
        }

        if (method_exists($instance, $this->nameMethodBoot)) {
            try {
                $instance->{$this->nameMethodBoot}();
            } catch (\Throwable $th) {
                throw new RuntimeException(sprintf('Error: %s.', $th->getMessage()));
            }
        }

        return $instance;
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $alias
     * @return bool
     */
    public function has($alias): bool
    {
        return isset($this->services[$alias]);
    }

    /**
     * Set a service in the container.
     *
     * @param string $alias
     * @param Closure $closure
     * @param bool $shared
     * @param bool $overwrite
     */
    public function set(string $alias, Closure $serviceClosure, ?Closure $initial = null, bool $shared = false, bool $overwrite = true)
    {
        if ($overwrite) {
            $this->services[$alias] = [
                'closure' => $serviceClosure,
                'initial' => $initial,
                'shared'  => $shared,
            ];
        }
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
     * @param string $alias
     * @param bool $shared
     * @return Container
     */
    public function setShared(string $alias, bool $shared): Container
    {
        if ($this->has($alias)) {
            $this->services[$alias]['shared'] = $shared;
        }

        return $this;
    }

    /**
     * Check if an instance is shared.
     *
     * @param string $alias
     * @return bool
     */
    public function isShared(string $alias): bool
    {
        return isset($this->services[$alias]['shared']);
    }

    /**
     * Extend a service definition with additional configuration.
     *
     * @param string $alias
     * @param Closure $extension
     * @return Container
     */
    public function extend(string $alias, Closure $extension): Container
    {
        if ($this->has($alias)) {
            $originalClosure = $this->services[$alias]['closure'];

            $this->services[$alias]['closure'] = function (...$args) use ($originalClosure, $extension) {
                $instance = $originalClosure(...$args);
                return $extension($instance, $this);
            };
        }

        return $this;
    }

    /**
     * Register services from a file.
     *
     * @param string $serviceFilePath
     * @throws RuntimeException
     */
    public function register(string $serviceFilePath)
    {
        $this->validateServiceFile($serviceFilePath);

        $config = require $serviceFilePath;
        foreach ($config as $alias => $service) {
            $class   = $service['class']   ?? null;
            $closure = $service['closure'] ?? null;
            $initial = $service['initial'] ?? null;
            $shared  = $service['shared']  ?? false;
            $boot    = $service['boot']    ?? null;

            $callback = $this->getServiceCallback($alias, $class, $closure);

            // Register the service with the provided parameters
            $this->set($alias, $callback, $initial, $shared);
            if (($boot !== null) && is_callable($boot)) {
                fn () => $boot();
            }
        }
    }

    /**
     * Get the appropriate callback for the service.
     *
     * @param string     $alias
     * @param string|null $class
     * @param \Closure|null $closure
     * @return \Closure
     * @throws RuntimeException
     */
    private function getServiceCallback(string $alias, ?string $class, ?Closure $closure): Closure
    {
        return match (true) {
            ($class   !== null) => fn (...$args) => (new ReflectionClass($class))->newInstanceArgs($args),
            ($closure !== null) => $closure,
            default => throw new RuntimeException("Invalid service configuration for: $alias"),
        };
    }

    /**
     * Validate that the service file exists.
     *
     * @param string $serviceFilePath
     * @throws RuntimeException
     */
    private function validateServiceFile(string $serviceFilePath)
    {
        if (!is_file($serviceFilePath)) {
            throw new RuntimeException("Configuration file not found: $serviceFilePath");
        }
    }

    /**
     * Register multiple services at once.
     *
     * @param string $directory
     * @param string $ext
     * @throws RuntimeException
     */
    public function registerFromDirectory(string $directory, string $ext = '.php'): void
    {
        $files = glob("$directory/*$ext");
        foreach ($files as $file) {
            $this->register($file);
        }
    }

    /**
     * Remove a service from the container.
     * @param string $alias
     */
    public function remove(string $alias): void
    {
        unset($this->services[$alias]);
        unset($this->sharedInstances[$alias]);
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
     * @return array
     */
    public function getServices(): array
    {
        return array_keys($this->services);
    }

    /**
     * Get all shared service IDs registered in the container.
     * @return array
     */
    public function getSharedServices(): array
    {
        $sharedServices = [];
        foreach ($this->services as $alias => $service) {
            if ($service['shared']) {
                $sharedServices[] = $alias;
            }
        }

        return $sharedServices;
    }

    /**
     * Set alias for a service.
     *
     * @param string $alias
     * @param string $alias
     * @return Container
     */
    public function setAlias(string $alias, string $newAlias): Container
    {
        if ($this->has($alias)) {
            $this->services[$alias] = $newAlias;
        }

        return $this;
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
        if (!is_callable($callable))
            throw new InvalidArgumentException('Callable must be valid');

        $reflection = is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction($callable);

        $parameters = [];

        foreach ($reflection->getParameters() as $param) {
            $typeName = $this->getParameterTypeName($param);
            $defaultValue = $this->getParameterDefaultValue($param);

            $parameters[] = "$typeName \${$param->getName()}$defaultValue";
        }

        return $parameters;
    }

    /**
     * Get the type name of a parameter.
     *
     * @param ReflectionParameter $param
     * @return string
     */
    private function getParameterTypeName(ReflectionParameter $param): string
    {
        $paramType = $param->getType();
        return $paramType instanceof ReflectionNamedType
            ? ($paramType->allowsNull() ? '?' : '') . $paramType->getName()
            : 'mixed';
    }

    /**
     * Get the default value representation for a parameter.
     *
     * @param ReflectionParameter $param
     * @return string
     */
    private function getParameterDefaultValue(ReflectionParameter $param): string
    {
        if ($param->isDefaultValueAvailable()) {
            $defaultValue = $param->getDefaultValue();

            // Limitar la longitud de la cadena a 50 caracteres
            $formattedValue = mb_strlen(var_export($defaultValue, true)) > 50
                ? substr(var_export($defaultValue, true), 0, 50) . '...'
                : var_export($defaultValue, true);

            return ' = ' . (is_array($defaultValue) || is_scalar($defaultValue)
                ? $formattedValue
                : 'null');
        }

        if ($param->isVariadic()) {
            return ' ...';
        }

        return '';
    }

    /**
     * Dump information about a service.
     *
     * @param string|null $alias
     * @param array|null $options
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function dump($alias = null, array $options = null)
    {
        if (is_null($alias)) return dump($this->services);

        if (!isset($this->services[$alias]))
            throw new InvalidArgumentException("Service not found: $alias");

        $data = [
            'id' => $alias,
            'closure' => $this->services[$alias]['closure'],
            'initial' => $this->services[$alias]['initial'],
            'shared'  => $this->services[$alias]['shared'],
        ];

        if (isset($options['class'])) {
            $data['class'] = $this->services[$alias]['class'];
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
     * @param string $key
     */
    public function __unset($key)
    {
        $this->remove($key);
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
