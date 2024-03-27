<?php

declare(strict_types=1);

/**
 * Class Env
 *
 * Class for handling environment variables
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class Env
{
    /**
     * Stores environment variables
     */
    protected static array $data = [];

    /**
     * Load environment variables from a file
     */
    public static function load(string $file): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_contains($line, '=') && !str_starts_with($line, '#')) {
                [$name, $value] = explode('=', $line, 2);
                self::$data[trim($name)] = trim(self::expandValue($value));
            }
        }
    }

    /**
     * Get the value of an environment variable
     */
    public static function get(string $name = null, $default = null): mixed
    {
        return self::$data[$name] ?? $default;
    }

    /**
     * Set the value of an environment variable
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
     * Expand variables in a string value, 
     * replacing placeholders with their corresponding values.
     */
    protected static function expandValue(string $value): string
    {
        $value = trim($value, '"');

        return preg_replace_callback('/\${(\w+)}/', function ($matches) {
            return self::get($matches[1]) ?? $matches[0];
        }, $value);
    }
}
