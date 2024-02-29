<?php

namespace Raxm\Support;

use ReflectionObject;
use ReflectionProperty;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait InteractsWithProperties
 *
 * This trait provides methods for interacting with object properties, including hydration, public property retrieval,
 * property checks, and resetting properties.
 * @package Axm\Raxm\Support
 */
trait InteractsWithProperties
{
    /**
     * Handles the hydration of a specific property.
     *
     * @param string $property The name of the property.
     * @param mixed  $value    The value to be hydrated.
     *
     * @return mixed The hydrated value.
     */
    public function handleHydrateProperty($property, $value)
    {
        $newValue = $value;

        if (method_exists($this, 'hydrateProperty')) {
            $newValue = $this->hydrateProperty($property, $newValue);
        }

        foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
            $method = 'hydratePropertyFrom' . class_basename($trait);

            if (method_exists($this, $method)) {
                $newValue = $this->{$method}($property, $newValue);
            }
        }

        return $newValue;
    }

    /**
     * Handles the dehydration of a specific property.
     *
     * @param string $property The name of the property.
     * @param mixed  $value    The value to be dehydrated.
     * @return mixed The dehydrated value.
     */
    public function handleDehydrateProperty($property, $value)
    {
        $newValue = $value;

        if (method_exists($this, 'dehydrateProperty')) {
            $newValue = $this->dehydrateProperty($property, $newValue);
        }

        foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
            $method = 'dehydratePropertyFrom' . class_basename($trait);

            if (method_exists($this, $method)) {
                $newValue = $this->{$method}($property, $newValue);
            }
        }

        return $newValue;
    }

    /**
     * Retrieves public properties defined by the subclass (excluding the base class).
     * @return array Associative array of public properties and their values.
     */
    public function getPublicPropertiesDefinedBySubClass()
    {
        $reflection = new ReflectionObject($this);
        $publicProperties = array_filter($reflection->getProperties(), function ($property) {
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
     * Retrieves protected or private properties defined by the subclass.
     * @return array Associative array of protected or private properties 
     * and their values.
     */
    public function getProtectedOrPrivatePropertiesDefinedBySubClass()
    {
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
       
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
     * Gets the initialized value of a property using reflection.
     *
     * @param ReflectionProperty $property The reflection property.
     * @return mixed|null The initialized value of the property.
     */
    public function getInitializedPropertyValue(ReflectionProperty $property)
    {
        if (!$property->isInitialized($this)) {
            return null;
        }

        $property->setAccessible(true);
        return $property->getValue($this);
    }

    /**
     * Checks if a property exists on the object.
     *
     * @param string $prop The name of the property.
     * @return bool Whether the property exists.
     */
    public function hasProperty($prop)
    {
        return property_exists($this, $this->beforeFirstDot($prop));
    }

    /**
     * Get the value of a property, supporting dot notation.
     *
     * @param string $name The name of the property.
     * @return mixed The value of the property.
     */
    public function getPropertyValue($name)
    {
        $value = $this->{$this->beforeFirstDot($name)};

        if ($this->containsDots($name)) {
            return data_get($value, $this->afterFirstDot($name));
        }

        return $value;
    }

    /**
     * Set the value of a protected property.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value to set.
     * @return mixed The set value.
     */
    public function setProtectedPropertyValue($name, $value)
    {
        return $this->{$name} = $value;
    }

    /**
     * Check if the string contains dots.
     *
     * @param string $subject The string to check.
     * @return bool Whether the string contains dots.
     */
    public function containsDots(string $subject): bool
    {
        return strpos($subject, '.') !== false;
    }

    /**
     * Get the substring before the first dot in a string.
     *
     * @param string $subject The input string.
     * @return string The substring before the first dot.
     */   
 public function beforeFirstDot($subject)
    {
        return explode('.', $subject)[0];
    }

    /**
     * Get the substring after the first dot in a string.
     *
     * @param string $subject The input string.
     * @return string The substring after the first dot.
     */
    public function afterFirstDot($subject)
    {
        return substr($subject, strpos($subject, '.') + 1);
    }

    /**
     * Check if a property is public and not defined in the base class.
     *
     * @param string $propertyName The name of the property.
     * @return bool Whether the property is public and not defined in the base class.
     */
    public function propertyIsPublicAndNotDefinedOnBaseClass($propertyName)
    {
        $publicProperties = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        $propertyNames = array_map(function ($property) {
            return $property->name;
        }, $publicProperties);

        return in_array($propertyName, $propertyNames);
    }

    /**
     * Fill the object's public properties with values.
     * @param mixed $values The values to fill.
     */
    public function fill($values)
    {
        $publicProperties = array_keys($this->getPublicPropertiesDefinedBySubClass());

        if ($values instanceof Model) {
            $values = $values->toArray();
        }

        foreach ($values as $key => $value) {
            if (in_array($this->beforeFirstDot($key), $publicProperties)) {
                data_set($this, $key, $value);
            }
        }
    }

    /**
     * Reset specified or all public properties to their default values.
     * @param string ...$properties The properties to reset.
     */
    public function reset(...$properties)
    {
        $propertyKeys = array_keys($this->getPublicPropertiesDefinedBySubClass());

        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        if (empty($properties)) {
            $properties = $propertyKeys;
        }

        foreach ($properties as $property) {
            $freshInstance = new static();

            $this->{$property} = $freshInstance->{$property};
        }
    }

    /**
     * Reset all public properties except the specified ones.
     * @param string ...$properties The properties to exclude from the reset.
     */
    protected function resetExcept(...$properties)
    {
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        $keysToReset = array_diff(array_keys($this->getPublicPropertiesDefinedBySubClass()), $properties);
        $this->reset($keysToReset);
    }

    /**
     * Get only the specified properties from the object.
     *
     * @param array $properties The properties to retrieve.
     * @return array An associative array of specified properties and their values.
     */
    public function only($properties)
    {
        $results = [];

        foreach ($properties as $property) {
            $results[$property] = $this->hasProperty($property) ? $this->getPropertyValue($property) : null;
        }

        return $results;
    }

    /**
     * Exclude the specified properties from all properties of the object.
     *
     * @param array $properties The properties to exclude.
     * @return array An associative array of properties excluding the specified ones.
     */
    public function except($properties)
    {
        return array_diff_key($this->all(), array_flip($properties));
    }

    /**
     * Get all public properties defined by the subclass.
     * @return array An associative array of all public properties and their values.
     */
    public function all()
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
