<?php

if (!function_exists('searchArrayByDot')) {
    /**
     * Searches for a value in an array using dot notation.
     * 
     * Supports wildcard searches like foo.*.bar
     * @param string $key   The key to search for
     * @param array  $array The array to search in
     * @return mixed|null The value found or null if not found
     */
    function searchArrayByDot(string $key, array $array)
    {
        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (!isset($array[$segment]) && $segment !== '*') {
                return null;
            }

            $array = $segment === '*' ? array_values($array) : $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('recursiveArraySearchByDot')) {
    /**
     * Recursive helper function for searchArrayByDot.
     *
     * @param array $keys  The remaining keys to search
     * @param mixed $array The array or value to search in
     * @return mixed|null The value found or null if not found
     */
    function recursiveArraySearchByDot(array $keys, $array)
    {
        if (empty($keys)) {
            return $array;
        }

        $currentKey = array_shift($keys);

        if ($currentKey === '*') {
            $result = [];

            foreach ($array as $value) {
                $result[] = recursiveArraySearchByDot($keys, $value);
            }

            $result = array_filter($result, static function ($value) {
                return $value !== null;
            });

            if (count($result) === 1) {
                return current($result);
            }

            return $result;
        }

        if (is_array($array) && isset($array[$currentKey])) {
            return recursiveArraySearchByDot($keys, $array[$currentKey]);
        }

        return null;
    }
}

if (!function_exists('deepSearchArray')) {
    /**
     * Recursively searches for a value in a multidimensional array.
     *
     * @param mixed $key   The key to search for
     * @param array $array The array to search in
     * @return mixed|null The value found or null if not found
     */
    function deepSearchArray($key, array $array)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach ($array as $value) {
            if (is_array($value)) {
                $result = deepSearchArray($key, $value);

                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }
}

if (!function_exists('sortArrayByMultipleKeys')) {
    /**
     * Sorts a multidimensional array by multiple keys.
     *
     * @param array $array       The array to sort
     * @param array $sortColumns An array of columns to sort by
     * @return bool True if the sorting was successful, false otherwise
     */
    function sortArrayByMultipleKeys(array &$array, array $sortColumns): bool
    {
        if (empty($sortColumns) || empty($array)) {
            return false;
        }

        $columns   = [];
        $sortFlags = [];

        foreach ($sortColumns as $column => $sortFlag) {
            $columns[] = $column;
            $sortFlags[] = $sortFlag;
        }

        $args = array_merge($array, $columns, $sortFlags);

        return array_multisort(...$args);
    }
}

if (!function_exists('flattenArrayWithDots')) {
    /**
     * Flattens a multidimensional array using dot notation as separators.
     *
     * @param iterable $array  The multidimensional array
     * @param string   $prefix Something to initially prepend to the flattened keys
     * @return array The flattened array
     */
    function flattenArrayWithDots(iterable $array, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened += flattenArrayWithDots($value, $prefix . $key . '.');
            } else {
                $flattened[$prefix . $key] = $value;
            }
        }

        return $flattened;
    }
}
