<?php

declare(strict_types=1);

namespace Axm;

use Closure;
use Exception;
use Traversable;
use ArrayIterator;
use ArrayAccess;
use IteratorAggregate;
use Countable;
use ReflectionClass;
use RuntimeException;
use ReflectionMethod;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;

class Container implements ArrayAccess, IteratorAggregate, Countable
{
    protected $bind = [];
    protected array $instances = [];
    private string $nameMethodBoot = 'boot';
    protected $aliases = [];

    /**
     * Get the singleton instance of the class.
     * @return self
     */
    public static function getInstance(): self
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Register a service in the container.
     *
     * @param string|array|object $abstract Abstract type of service to be registered.
     * @param mixed $concrete (optional) Concrete type of service to be registered.
     * @param bool $shared (optional) Determines if the service should be shared.
     */
    public function bind(string|array $abstract, $concrete = null)
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $val) {
                $this->bind($key, $val);
            }
        } elseif ($concrete instanceof Closure) {
            $this->bind[$abstract] = $concrete;
        } elseif (is_object($concrete)) {
            $this->instance($abstract, $concrete);
        } else {
            $abstract = $this->getAlias($abstract);
            if ($abstract != $concrete) {
                $this->bind[$abstract] = $concrete;
            }
        }

        return $this;
    }
    /**
     * Binds the given abstract to the given instance.
     *
     * @param string $abstract The abstract or interface name.
     * @param object $instance The instance of the class that implements the abstract.
     * @return static The container instance itself.
     */
    public function instance(string $abstract, $instance)
    {
        $abstract = $this->getAlias($abstract);

        $this->instances[$abstract] = $instance;

        return $this;
    }

    /**
     * Binds the given abstract to the concrete implementation as a singleton.
     *
     * @param string $abstract The abstract or interface name.
     * @param string|null $concrete The concrete implementation class name.
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Binds the given abstract to a factory closure.
     *
     * @param string $abstract The abstract or interface name.
     * @param Closure $factory A closure that returns an instance of the concrete implementation.
     */
    public static function factory(string $name, string $namespace = '', ...$args)
    {
        $class = str_contains($name, '\\') ? $name : $namespace . ucwords($name);

        return Container::getInstance()->buildClass($class, $args);
    }

    /**
     * Calls the given callable with the specified parameters.
     *
     * @param callable $callable The callable to call.
     * @param array $parameters The parameters to pass to the callable.
     * @param bool $accessible Whether to allow access to protected and private methods.
     * @return mixed The result of calling the callable.
     */
    public function call($callable, array $parameters = [], bool $accessible = false)
    {
        if ($callable instanceof Closure) {
            return $this->buildFunction($callable, $parameters);
        } elseif (is_string($callable) && !str_contains($callable, '::')) {
            return $this->buildFunction($callable, $parameters);
        } else {
            return $this->buildMethod($callable, $parameters, $accessible);
        }
    }

    /**
     * Build a function or a Closure with the given variables.
     *
     * @param string|Closure $function The function or Closure to invoke.
     * @param $parameters The variables to pass to the function.
     * @return mixed The result of the function call.
     * @throws RuntimeException If the function does not exist.
     */
    public function buildFunction(string|Closure $function, array $parameters = [])
    {
        try {
            $reflect = new ReflectionFunction($function);
        } catch (ReflectionException $e) {
            throw new RuntimeException("Function not exists: {$function}() " . $e->getMessage());
        }

        $args = $this->bindParams($reflect, $parameters);
        return $function(...$args);
    }

    /**
     * Invokes a method on a class with the given parameters.
     *
     * @param array|string $method The method to invoke, either as an array [class, method]
     * @param array $parameters The parameters to pass to the method.
     * @param bool $accessible Whether to make the method accessible if it is not public.
     * @return mixed The result of the method call.
     * @throws Exception If the method does not exist.
     */
    public function buildMethod($method, array $parameters = [], bool $accessible = false): mixed
    {
        if (is_array($method)) {
            [$class, $method] = $method;

            $class = is_object($class) ? $class : $this->buildClass($class);
        } else {
            [$class, $method] = explode('::', $method);
        }

        try {
            $reflect = new ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            $class = is_object($class) ? $class::class : $class;
            throw new Exception("Method not exists: " . $class . '::' . $method . '()' . ' ' . $e->getMessage());
        }

        $args = $this->bindParams($reflect, $parameters);

        if ($accessible) {
            $reflect->setAccessible($accessible);
        }

        return $reflect->invokeArgs(is_object($class) ? $class : null, $args);
    }

    /**
     * Resolves and returns the given abstract.
     *
     * @param string $abstract abstract or interface name.
     * @param array $parameters An array of parameters to pass to the constructor of the concrete implementation.
     * @return object
     */
    public function make(string $abstract, array $parameters = [], bool $newInstance = false): object
    {
        $abstract = $this->getAlias($abstract);

        if (!$newInstance && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $instance = $this->resolveInstance($abstract, $parameters);

        if (!$newInstance) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Resolves the given abstract to an instance of a class.
     *
     * @param string $abstract The abstract or interface name.
     * @param array $parameters An array of parameters to be passed to the constructor of the class.
     * @return object The instance of the class that implements the abstract.
     */
    protected function resolveInstance(string $abstract, array $parameters): object
    {
        if (isset($this->bind[$abstract]) && $this->bind[$abstract] instanceof Closure) {
            return $this->buildFunction($this->bind[$abstract], $parameters);
        } else {
            return $this->buildClass($abstract, $parameters);
        }
    }

    /**
     * Initializes the services.
     *
     * This method iterates over the array of services, creates an instance of each class,
     * and calls the method specified in the `$this->nameMethodBoot` property if it exists.
     */
    public function initializeServices()
    {
        $services = $this->services();
        foreach ($services as $alias => $className) {

            $instance = $this->make($alias);
            if (isset($instance) && method_exists($instance, $this->nameMethodBoot)) {
                $instance->{$this->nameMethodBoot}();
            }
        }
    }

    /**
     * Creates a new instance of given abstract class.
     *
     * @param string $abstract abstract or interface name.
     * @param array $parameters An array of parameters to to the constructor. * @return object
     */
    public function buildClass(string $abstract, array $parameters = [])
    {
        try {
            $reflect = new ReflectionClass($abstract);
        } catch (ReflectionException $e) {
            throw new Exception($e->getMessage());
        }

        $constructor = $reflect->getConstructor();
        $args = $constructor ? $this->bindParams($constructor, $parameters) : [];
        $object = $reflect->newInstanceArgs($args);

        return $object;
    }

    /**
     * This method binds the values in the `$params` array to the parameters of the method
     * 
     * represented by the ReflectionFunctionAbstract object passed to it.
     * @param ReflectionFunctionAbstract $reflection A reflection of a constructor or method.
     * @return array An array of bound parameters.
     */
    protected function bindParams(ReflectionFunctionAbstract $reflection, array $params = [])
    {
        $bindings = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            if (isset($params[$name])) {
                $bindings[$name] = $params[$name];
            } else if ($param->isOptional()) {
                $bindings[$name] = $param->getDefaultValue();
            } else {
                throw new Exception("Missing required parameter $name");
            }
        }

        return $bindings;
    }

    /**
     * Retrieves the alias for the given abstract class or interface.
     *
     * @param string $abstract The abstract class or interface to get the alias for.
     * @return string The alias for the abstract class or interface.
     */
    public function getAlias(string $abstract): string
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];

            if (is_string($bind)) {
                return $this->getAlias($bind);
            }
        }

        return $abstract;
    }

    /**
     * Registers the definitions from the given directory.
     * 
     * @param string $directory The directory to scan for definitions.
     * @param string $ The file extension to look for. Default is '.php'.
     * @return void
     */
    public function registerFromDirectory(string $directory, string $ext = '.php'): void
    {
        $files = glob("$directory/*$ext") ?? [];
        foreach ($files as $file) {
            $this->loadDefinitions($file);
        }
    }

    /**
     * Loads the definitions from the given file.
     *
     * @param string $file The file to load the definitions from.
     * @return void
     */
    public function loadDefinitions(string $file): void
    {
        $definitions = require $file;
        foreach ($definitions as $name => $definition) {

            $this->bind($name, $definition);
        }
    }

    /**
     * Returns the currently bound services.
     *
     * @return array The array of bound services.
     */
    public function services()
    {
        return $this->bind ?? [];
    }

    /**
     * Checks if the given abstract is bound or already resolved.
     *
     * @param string $abstract The abstract to check.
     * @return bool True if the abstract is bound or resolved, false otherwise.
     */
    public function has(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Returns an instance of the given abstract.
     *
     * @param string $abstract The abstract to retrieve.
     * @return object The instance of the abstract.
     */
    public function get(string $abstract)
    {
        if ($this->has($abstract)) {
            return $this->make($abstract);
        }

        throw new Exception('class not exists: ' . $abstract);
    }

    /**
     * Checks if the given abstract is shared.
     *
     * @param string $abstract The abstract to check.
     * @return bool True if the abstract is shared, false otherwise.
     */
    public function isShared($abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->shared[$abstract]);
    }

    /**
     * Unbinds the given abstract from the container.
     * @param string $abstract The abstract class or interface name.
     */
    public function unbind(string $abstract): void
    {
        unset($this->bind[$abstract]);
        unset($this->instances[$abstract]);
    }

    /**
     * Deletes the given named instance from the container.
     */
    public function delete(string $name)
    {
        $name = $this->getAlias($name);

        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
        }
    }

    /**
     * Deletes the given named instance from the container.
     * @param string $name The name of the instance to delete
     */
    public function exists(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);

        return isset($this->instances[$abstract]);
    }

    /**
     * Returns the instances that this node has
     */
    public function instances(): array
    {
        return $this->instances;
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function __set(string $key, $value)
    {
        $this->bind($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key)
    {
        $this->unbind($key);
    }

    public function count(): int
    {
        return count($this->instances);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->instances);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->exists($key);
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->make($key);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->bind($key, $value);
    }

    public function offsetUnset(mixed $key): void
    {
        $this->delete($key);
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
