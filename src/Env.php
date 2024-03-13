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
     * @return void
     */
    public static function load(string $file): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_contains($line, '=') && !str_starts_with($line, '#')) {
                [$name, $value] = explode('=', $line, 2);
                $name  = trim($name);
                $value = trim($value);
                self::$data[$name] = self::expandValue($value);
            }
        }
    }

    /**
     * Get the value of an environment variable
     * 
     * @param mixed $default Default value if the variable is not defined
     * @return mixed Value of the environment variable or default value
     */
    public static function get(string $name = null, $default = null)
    {
        return self::$data[$name] ?? $default;
    }

    /**
     * Set the value of an environment variable
     * @param mixed $value Value of the environment variable
     */
    public static function set(string $name, $value): void
    {
        self::$data[$name] = $value;
    }

    /**
     * Check if an environment variable is seta
     */
    public static function has(string $name): bool
    {
        return isset(self::$data[$name]);
    }

    /**
     * Expand variables in a string value, replacing placeholders with their corresponding values.
     * @return string The string value with variables replaced.
     */
    protected static function expandValue(string $value): string
    {
        $value = trim($value, '"');

        return preg_replace_callback('/\${(\w+)}/', function ($matches) {
            return self::get($matches[1]) ?? $matches[0];
        }, $value);
    }
}
