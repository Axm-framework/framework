<?php

declare(strict_types=1);

namespace Fluent;

use Exception;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 *  Class Fluent
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 *
 * Fluent Interface class for method chaining and control flow.
 * 
 * The Fluent class provides a fluent interface to facilitate method chaining and flow control 
 * in your PHP applications. and flow control in your PHP applications. You can use this class to perform a variety 
 * of operations by chaining methods in a concise and readable way. 
 */
class Fluent
{
    private $obj;
    private $result = [];
    private $condition = true;
    private $isReturn  = false;
    private $customMethods = [];

    
    /**
     * Constructor that accepts a class name or object.
     *
     * @param mixed $obj The class name or object to work with.
     * @throws InvalidArgumentException If the argument is not a valid class name or object.
     */
    public function __construct(string|object|array $data)
    {
        $this->createInstance($data);
    }

    /**
     * Checks whether the argument is a string or an existing class and creates an instance.
     *
     * @param mixed $data The argument to be used to create the instance.
     * @return void
     */
    private function createInstance(string|object|array $data)
    {
        $this->obj = match (true) {
            is_string($data) && class_exists($data) => new $data(),
            is_object($data) => $data,
            is_array($data)  => new Collection($data),
        };

        $this->result[get_class($this->obj)] = $this->obj;
    }

    /**
     * Create a new instance of a class and set it as the current object.
     *
     * @param array $args An associative array where the keys represent the class name and constructor arguments.
     * @param bool $return Whether to return the created object or $this.
     * @return $this|object
     */
    public function new(string|object|array $data, bool $return = true): Fluent
    {
        if ($this->condition && !$this->isReturn) {
            $this->createInstance($data);

            return $return ? $this->obj : $this;
        }
    }

    /**
     * Get the current result.
     * @return mixed The current result.
     */
    public function all(): array
    {
        return $this->result;
    }

    /**
     * Get the current result, which is the last element in the results array.
     * @return mixed|null The current result.
     */
    public function get(string $key = null)
    {
        if ($key !== null && array_key_exists($key, $this->result)) {
            return $this->result[$key];
        }

        return end($this->result);
    }

    /**
     * Add a key-value pair to the result array.
     *
     * @param string $key   The key to add to the array.
     * @param mixed  $value The value associated with the key.
     * @return $this        Returns the current instance of Fluent for method chaining.
     */
    public function addValue($key, $value)
    {
        $this->result[$key] = $value;
        return $this;
    }

    /**
     * Iterates over the elements of the collection and applies a callback function to each element.
     *
     * @param callable $callback The callback function to apply to each element.
     * @param string|null $key (Optional) The key under which you want to store the results in the buffer.
     * @return $this The current Fluent object.
     */
    public function each(callable $callback, $key = null)
    {
        if ($this->condition) {
            $result = $this->result;

            // Apply the callback function to each element
            $processedResult = array_map($callback, $result);

            if ($key !== null) {
                // Store the results under a custom key if provided
                $this->result[$key] = $processedResult;
            } else {
                // Store the results in the buffer
                $this->result['each'] = $processedResult;
            }
        }

        return $this;
    }

    /**
     * Get the value of a property of the object.
     *
     * @param string $name The name of the property.
     * @return mixed|null The value of the property, or null if it doesn't exist.
     */
    public function getProperty($name)
    {
        if (property_exists($this->obj, $name)) {
            return $this->obj->$name;
        }

        throw new InvalidArgumentException(sprintf('The property [ %s ] does not exist on the object [ %s ] .', $name, get_class($this->obj)));
    }

    /**
     * Set the value of a property of the object.
     *
     * @param string $name The name of the property.
     * @param mixed $value The value to set.
     * @return $this
     */
    public function setProperty($name, $value)
    {
        if (property_exists($this->obj, $name)) {
            $this->obj->$name = $value;
            return $this;
        }

        throw new InvalidArgumentException(sprintf('The property [ %s ] does not exist on the object [ %s ] .', $name, get_class($this->obj)));
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $id
     * @return bool
     */
    public function hasProperty($name): bool
    {
        return isset($this->obj->$name);
    }

    /**
     * Set a condition for method execution and specify a callback to execute if the condition is true.
     *
     * @param callable $callback The callback to execute if the condition is true.
     * @return $this
     */
    public function if(callable $callback)
    {
        $condition = $callback($this);
        $this->result['if'] = $condition;

        if ($this->condition && $condition) {
            $this->condition = true;
        } else {
            $this->condition = false;
        }

        return $this;
    }

    /**
     * Set a condition for method execution if the previous condition is false.
     *
     * @param callable $callback The callback to execute if the condition is true.
     * @return $this
     */
    public function elseif(callable $callback)
    {
        $condition = $callback($this);
        $this->result['elseif'] = $condition;

        if (!$this->condition && $condition) {
            $this->condition = true;
        } else {
            $this->condition = false;
        }

        return $this;
    }

    /**
     * Execute a callback if no previous conditions were met.
     *
     * @param callable $callback The callback to execute if no previous conditions were met.
     * @return $this
     */
    public function else()
    {
        if ($this->condition = !$this->condition) {
            $this->condition = true;
        }

        return $this;
    }

    /**
     * Set a value or execute a closure as the result.
     *
     * @param mixed|callable|null $valueOrClosure The value or closure to set as the result.
     * @return $this
     */
    public function return($valueOrClosure = null)
    {
        if ($this->condition) {
            $this->isReturn = true;

            if (is_callable($valueOrClosure)) {
                $this->result['return'] = $valueOrClosure();
            } elseif ($valueOrClosure !== null) {
                $this->result['return'] = $valueOrClosure;
            }
        }

        return $this;
    }

    /**
     * Chain method execution with a provided closure.
     *
     * @param callable $callback The closure to call with the current object.
     * @return $this The current instance of Fluent.
     */
    public function chain(callable $callback)
    {
        if ($this->condition) {
            // Clone the current object to avoid affecting the original object with modifications.
            $newInstance = clone $this;

            $this->result['chain'] = $callback($newInstance);

            return $newInstance;
        }

        return $this;
    }

    /**
     * Throw an exception if a condition is met.
     *
     * @param callable $callback The callback to execute if the condition is true.
     * @param string $exceptionMessage The exception message.
     * @return $this
     * @throws Exception If the condition is met.
     */
    public function throwIf(callable $callback, $exceptionMessage)
    {
        $condition = $callback($this);
        if ($condition) {
            throw new Exception($exceptionMessage);
        }
        return $this;
    }

    /**
     * Reflect and create an instance of a class with constructor arguments.
     *
     * This method allows you to create an instance of a specified class
     * using reflection and provide constructor arguments.
     * @param string $className The name of the class to reflect and instantiate.
     * @param mixed ...$constructorArgs Arguments to pass to the class constructor.
     * @return $this Returns the current instance of Fluent, allowing for method chaining.
     * @throws Exception If there is an error creating an instance of the class.
     */
    public function reflect($className, ...$constructorArgs)
    {
        if ($this->condition) {
            try {
                $reflection = new ReflectionClass($className);
                $this->result['reflect'] = $reflection->newInstanceArgs($constructorArgs);
            } catch (ReflectionException $e) {
                throw new InvalidArgumentException(sprintf('Error creating instance of %s: [ %s ] .', $className, $e->getMessage()));
            }
        }

        return $this;
    }

    /**
     * Loop through a callback for a specified number of iterations.
     *
     * @param callable $callback The callback to execute.
     * @param int $iterations The number of iterations.
     * @return $this
     * @throws Exception If an exception occurs within the callback.
     */
    public function loop(callable $callback, $iterations)
    {
        if ($this->condition) {
            for ($i = 0; $i < $iterations; $i++) {
                try {
                    $this->result['loop'] = $callback($i);
                } catch (Exception $e) {
                    throw new InvalidArgumentException(sprintf('Error in loop iteration %s: [ %s ] .', $i, $e->getMessage()));
                }
            }
        }

        return $this;
    }

    /**
     * Dump the result data using the Laravel "dd" function.
     *
     * @param string|null $key The optional key to retrieve a specific result entry.
     * @return $this The current instance of Fluent.
     */
    public function dd($key = null)
    {
        $value = isset($key) ? $this->result[$key] : $this->result;
        dd($value);

        return $this;
    }

    /**
     * Dump the result data using the Laravel "dump" function.
     *
     * @param string|null $key The optional key to retrieve a specific result entry.
     * @return $this The current instance of Fluent.
     */
    public function dump($key = null)
    {
        $value = isset($key) ? $this->result[$key] : $this->result;
        dump($value);

        return $this;
    }

    /**
     * Echo the result data if the condition is met.
     *
     * @param mixed $value The optional value to echo. If not provided, the entire result is echoed.
     * @return $this The current instance of Fluent.
     */
    public function write($value = null)
    {
        if ($this->condition) {
            $value = isset($value) ? $value : $this->result;
            if (is_string($value)) {
                echo $value;
            } elseif (is_array($value) || is_object($value)) {
                print_r($value);
            }
        }

        return $this;
    }

    /**
     * The Fluent with custom methods.
     * @param callable $extension A closure that defines the new method.
     */
    public function addCustomMethod($method, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('The callback must be callable.');
        }

        $this->customMethods[$method] = $callback;

        return $this;
    }

    /**
     * Reset the Fluent state.
     *
     * @param string|null $name The optional name of the result entry to reset.
     *      If provided, only that entry will be removed.
     *      If not provided, all entries are cleared, resetting the state.
     * @return $this The current instance of Fluent.
     */
    public function reset($name = null)
    {
        if ($name !== null) {
            unset($this->result[$name]);
        } else {
            $this->result = [];
        }

        return $this;
    }

    /**
     * Magic method to call methods on the object.
     *
     * @param string $name The method name.
     * @param array $arguments The method arguments.
     * @return $this
     * @throws Exception If an exception is thrown while calling the method.
     */
    public function __call($name, $arguments): Fluent
    {
        if ($this->condition && !$this->isReturn) {
            $result = null;

            $result = match (true) {
                $this->obj instanceof Collection, is_callable([$this->obj, $name]) => $this->callMethod($this->obj, $name, $arguments),
                array_key_exists($name, $this->customMethods) => call_user_func_array($this->customMethods[$name], $arguments),

                default  => $this->callMethod($this, $name, $arguments),
            };

            if ($result !== null) {
                $this->result[$name] = $result;
            }
        }

        return $this;
    }

    /**
     * Method to call a method on a service
     */
    private function callMethod(Object $obj, string $method, $arguments)
    {
        if (method_exists($obj, $method)) {
            return call_user_func_array([$obj, $method], $arguments);
        }
        // If the method does not exist, throw an exception
        throw new InvalidArgumentException(sprintf('The method [ %s ] does not exist in the Collection context.', $method));
    }

    /**
     * Magic method to get a service by property access.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this->obj, $name)) {
            return $this->obj->$name;
        }
        // If the property does not exist, throw an exception
        throw new RuntimeException(sprintf('The property  [ %s ] does not exist on the object [ %s ]', $name, get_class($this->obj)));
    }

    /**
     * Magic method to set a service by property access.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this->obj, $name)) {
            return $this->obj->$name = $value;
        }
        // If the property does not exist, throw an exception
        throw new RuntimeException(sprintf('The property  [ %s ] does not exist on the object [ %s ]', $name, get_class($this->obj)));
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
}
