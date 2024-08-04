<?php

declare(strict_types=1);

/**
 * Class Config
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class Config
{
    /**
     * Contain all the config
     */
    protected static $config = [];

    /**
     * Default config path 
     */
    private const DEFAULT_DIR = APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    /**
     * Get config
     * @throws Exception
     */
    public static function get(string $var): mixed
    {
        $sections = explode('.', $var);
        self::$config[$sections[0]] ??= self::load($sections[0]);

        return match (count($sections)) {
            3 => self::$config[$sections[0]][$sections[1]][$sections[2]] ?? null,
            2 => self::$config[$sections[0]][$sections[1]] ?? null,
            1 => self::$config[$sections[0]] ?? null,
            default => throw new \Exception('Maximum 3 levels in Config::get(file.section.variable), order: ' . $var)
        };
    }

    /**
     * Get all configs
     */
    public static function getAll(): array
    {
        return self::$config;
    }

    /**
     * Set variable in config
     * @throws Exception
     */
    public static function set(string $var, $value): void
    {
        $sections = explode('.', $var);
        match (count($sections)) {
            3 => self::$config[$sections[0]][$sections[1]][$sections[2]] = $value,
            2 => self::$config[$sections[0]][$sections[1]] = $value,
            1 => self::$config[$sections[0]] = $value,
            default => throw new \Exception('Maximum 3 levels in Config::get(file.section.variable), order: ' . $var)
        };
    }

    /**
     * Read config file
     */
    public function read(string $file, string $path = null, bool $force = false): array
    {
        if ($force)
            return self::$config[$file] = self::load($file, $path);

        return self::$config[$file] ??= self::load($file, $path);
    }

    /**
     * Load config file
     */
    public static function load(string $name, string $path = null): array
    {
        $path = $path ?? self::DEFAULT_DIR;
        if (is_file($fileConfig = $path . $name . '.php'))
            return require $fileConfig;

        throw new \Exception(sprintf('Error when opening the configuration file [ %s ] ', $fileConfig));
    }
}
