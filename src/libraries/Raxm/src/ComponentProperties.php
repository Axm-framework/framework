<?php

namespace Raxm;

/**
 * Class ComponentProperties
 *
 * This class provides methods for working with properties defined by subclasses.
 * @package Axm\Raxm
 */
class ComponentProperties
{
    /**
     * Get public properties defined by the subclass.
     * @return array
     */
    public static function getPublicPropertiesDefinedBySubClass()
    {
        $publicProperties = array_filter((new \ReflectionObject($this))->getProperties(), function ($property) {
            return $property->isPublic() && !$property->isStatic();
        });

        $data = [];

        foreach ($publicProperties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $this->getInitializedPropertyValue($property);
            }
        }

        return $data;
    }

    /**
     * Get protected or private properties defined by the subclass.
     * @return array
     */
    public static function getProtectedOrPrivatePropertiesDefinedBySubClass()
    {
        $properties = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
        $data = [];

        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $property->setAccessible(true);
                $data[$property->getName()] = $this->getInitializedPropertyValue($property);
            }
        }

        return $data;
    }

    /**
     * Get the initialized property value.
     *
     * @param \ReflectionProperty $property The property reflection.
     * @return mixed|null
     */
    public function getInitializedPropertyValue(\ReflectionProperty $property)
    {
        // Ensures typed property is initialized in PHP >=7.4, if so, return its value,
        // if not initialized, return null (as expected in earlier PHP Versions)
        if (method_exists($property, 'isInitialized') && !$property->isInitialized($this)) {
            return null;
        }

        return $property->getValue($this);
    }

    /**
     * Check if the class has a specific property.
     *
     * @param string $prop The property name.
     * @return bool
     */
    public static function hasProperty($prop)
    {
        return property_exists(
            $this,
            $prop
        );
    }

    /**
     * Set the value of a specific property.
     *
     * @param string $name The property name.
     * @param mixed $value The value to set.
     * @return mixed
     */
    public static function setPropertyValue($name, $value)
    {
        return $this->{$name} = $value;
    }

    /**
     * Get public properties of an object.
     *
     * @param object $instance The object instance.
     * @return array
     */
    public static function getPublicProperties(Object $instance): array
    {
        $class = new \ReflectionClass(get_class($instance));
        $properties = $class->getProperties(\ReflectionMethod::IS_PUBLIC);

        $publicProperties = [];
        foreach ($properties as $property) {
            if ($property->class == $class->getName()) {
                $publicProperties[$property->getName()] = $property->getValue($instance);
            }
        }
        return $publicProperties;
    }

    /**
     * Get public methods of an object, excluding specified exceptions.
     *
     * @param object|string $instance The object instance or class name.
     * @param array $exceptions Methods to exclude.
     * @return array
     */
    public static function getPublicMethods($instance, array $exceptions = [])
    {
        $class   = new \ReflectionClass(is_string($instance) ? $instance : get_class($instance));
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $publicMethods = [];

        foreach ($methods as $method) {
            if ($method->class == $class->getName() && !in_array($method->name, $exceptions)) {
                $publicMethods[] = $method->name;
            }
        }
        return $publicMethods;
    }

    /**
     * Check if a property is public in the object instance.
     *
     * @param object $instance The object instance.
     * @param string $propertyName The property name.
     * @return bool
     */
    public static function propertyIsPublic(Object $instance, $propertyName)
    {
        $property   = [];
        $reflection = new \ReflectionObject($instance);
        $properties = $reflection->getProperties(\ReflectionMethod::IS_PUBLIC);

        foreach ($properties as $key => $prop) {
            $property[] = $prop->getName();
        }

        return in_array($propertyName, $property) ? true : false;
    }

    /**
     * Check if a method is public in the object instance.
     *
     * @param object $instance The object instance.
     * @param string $methodName The method name.
     * @return bool
     */
    public static function methodIsPublic(Object $instance, $methodName)
    {
        $methodes   = [];
        $reflection = new \ReflectionObject($instance);
        $methods    = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $key => $method) {
            $methodes[] = $method->getName();
        }

        return in_array($methodName, $methodes) ? true : false;
    }

    /**
     * Reset specified properties to their initial values or default values.
     * @param mixed ...$properties List of properties to reset.
     */
    public static function reset(...$properties)
    {
        $propertyKeys = array_keys($this->getPublicProperties($this));

        // Keys to reset from array
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        // Reset all
        if (empty($properties)) $properties = $propertyKeys;

        foreach ($properties as $property) {
            $freshInstance = new static($this->id);

            $this->{$property} = $freshInstance->{$property};
        }
    }

    /**
     * Allows the resetting of properties from outside the class
     * 
     * @param mixed $name
     * @return void
     */
    public static function resetProperty($name)
    {
        $this->{$name} = $defaultValue;
    }

    /**
     * Reset all properties except the specified ones to their initial values or default values.
     * @param mixed ...$properties List of properties to keep; others will be reset.
     */
    public static function resetExcept(...$properties)
    {
        if (count($properties) && is_array($properties[0]))
            $properties = $properties[0];

        $keysToReset = array_diff(array_keys($this->getPublicProperties($this)), $properties);
        $this->reset($keysToReset);
    }

    /**
     * Get an array of all public properties of the instance.
     * @return array
     */
    public static function all()
    {
        return $this->getPublicProperties($this);
    }
}
