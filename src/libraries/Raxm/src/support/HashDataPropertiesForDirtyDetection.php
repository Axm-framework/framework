<?php

namespace Raxm\Support;

/**
 * Interface HydrationMiddleware
 *
 * Defines methods for hydrating and dehydrating instances.
 * @package Raxm\Support
 */
interface HydrationMiddleware
{
    /**
     * Hydrates the given instance with data from the request.
     *
     * @param object $instance The object to be hydrated.
     * @param array  $request  The data used for hydration.
     */
    public static function hydrate($instance, $request);

    /**
     * Dehydrates the given instance with data from the response.
     *
     * @param object $instance The object to be dehydrated.
     * @param array  $response The data used for dehydrating.
     */
    public static function dehydrate($instance, $response);
}

/**
 * Class HashDataPropertiesForDirtyDetection
 *
 * Implements the HydrationMiddleware interface to hash and track property changes for dirty detection.
 * @package Raxm\Support
 */
class HashDataPropertiesForDirtyDetection implements HydrationMiddleware
{
    /**
     * @var array Holds property hashes indexed by component ID.
     */
    protected static $propertyHashesByComponentId = [];

    /**
     * Hydrates the instance with data from the request.
     *
     * @param object $instance The object to be hydrated.
     * @param array  $request  The data used for hydration.
     */
    public static function hydrate($instance, $request)
    {
        $data = dataGet($request, 'memo.data', []);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach (self::flattenArrayWithPrefix($value, $key . '.') as $dottedKey => $value) {
                    self::rehashProperty($dottedKey, $value, $instance);
                }
            } else {
                static::rehashProperty($key, $value, $instance);
            }
        }
    }

    /**
     * Dehydrates the instance with data from the response and tracks dirty properties.
     *
     * @param object $instance The object to be dehydrated.
     * @param array  $response The data used for dehydrating.
     */
    public static function dehydrate($instance, $response)
    {
        $data = dataGet($response, 'memo.data', []);

        $dirtyProps = [];

        if (isset(static::$propertyHashesByComponentId[$instance->id])) {
            foreach (static::$propertyHashesByComponentId[$instance->id] as $key => $hash) {
                $value = dataGet($data, $key);

                if (static::hash($value) !== $hash) {
                    $dirtyProps[] = $key;
                }
            }
        }

        dataSet($response, 'effects.dirty', $dirtyProps);
    }

    /**
     * Rehashes a specific property of the component.
     *
     * @param string $name      The name of the property.
     * @param mixed  $value     The value of the property.
     * @param object $component The component object.
     */
    public static function rehashProperty($name, $value, $component)
    {
        static::$propertyHashesByComponentId[$component->id][$name] = static::hash($value);
    }

    /**
     * Hashes a value to a unique identifier.
     *
     * @param mixed $value The value to be hashed.
     * @return int|string The hash value.
     */
    public static function hash($value)
    {
        if (!is_null($value) && !is_string($value) && !is_numeric($value) && !is_bool($value)) {
            if (is_array($value)) {
                return json_encode($value);
            }

            $value = method_exists($value, '__toString')
                ? (string) $value
                : json_encode($value);
        }

        return crc32($value ?? '');
    }

    /**
     * Recursively flattens a multidimensional array with a given prefix.
     *
     * @param array  $array  The array to be flattened.
     * @param string $prefix The prefix for the flattened keys.
     * @return array The flattened array.
     */
    private static function flattenArrayWithPrefix($array, $prefix)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flattenArrayWithPrefix($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
