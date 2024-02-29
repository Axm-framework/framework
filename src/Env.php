<?php

declare(strict_types=1);

/**
 * Class for handling environment variables
 */
class Env
{
    /**
     * Stores environment variables
     * @var array
     */
    protected static $data = [];

    /**
     * Load environment variables from a file
     * @param string $file Environment variables file path
     */
    public static function load(string $file)
    {
        $env = parse_ini_file($file, true) ?: [];
        self::$data = array_merge(self::$data, $env);
    }

    /**
     * Get the value of an environment variable
     * 
     * @param string|null $name Environment variable name
     * @param mixed $default Default value if the variable is not defined
     * @return mixed Value of the environment variable or default value
     */
    public static function get(string $name = null, $default = null)
    {
        return self::$data[$name] ?? $default;
    }

    /**
     * Set the value of an environment variable
     * 
     * @param string $name Name of the environment variable Name of the environment variable
     * @param mixed $value Value of the environment variable
     */
    public static function set(string $name, $value): void
    {
        self::$data[$name] = $value;
    }

    /**
     * Check if an environment variable is seta
     * 
     * @param string $name Name of the environment variable Name of the environment variable
     * @return bool true if defined, false otherwise
     */
    public static function has(string $name): bool
    {
        return isset(self::$data[$name]);
    }
}
