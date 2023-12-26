<?php

declare(strict_types=1);

namespace Axm;

use Axm\Cache\Cache;
use RuntimeException;

/**
 *  Class BaseConfig 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */

class BaseConfig
{
    private static $instance;
    private array $config = [];
    private array $cache  = [];
    private array $loadedFiles = [];
    const ROOT_PATH_CONFIG = APP_PATH . DIRECTORY_SEPARATOR . 'Config';

    private function __construct()
    {
    }

    /**
     * Get the instance of the class.
     * @return BaseConfig
     */
    public static function make()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Loads a configuration file or multiple files.
     *
     * @param string|array $file      Configuration file name or an array of file names.
     * @param bool          $merge     Whether to merge the loaded configuration.
     * @return self Instance of the class.
     */
    public function load(string|array $file, bool $merge = true, ?string $pathConfig = null): self
    {
        is_string($file)
            ? $this->openFileConfig($file, $merge, $pathConfig)
            : $this->recursiveLoadFiles($file, $merge, $pathConfig);

        return self::$instance;
    }

    /**
     * Opens and loads a configuration file.
     *
     * @param string      $file        Configuration file name.
     * @param bool        $merge       Whether to merge the loaded configuration.
     * @param string|null $pathConfig  Optional path to the configuration directory.
     * @return array Loaded configuration.
     * @throws \RuntimeException When the file is not found or has an invalid format.
     */
    private function openFileConfig(string $file, bool $merge = true, ?string $pathConfig = null): array
    {
        // Check if the file has already been previously uploaded
        if (in_array($file, $this->loadedFiles)) return $this->config;

        $filePath = $this->resolverDirPath($file, $pathConfig);

        if (!is_file($filePath))
            throw new RuntimeException(sprintf('Configuration file not found: %s', $filePath));

        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $data = $this->getData($filePath, $ext);

        if (!is_array($data))
            throw new RuntimeException(sprintf('Invalid configuration file: %s', $filePath));

        $this->config = ($merge) ? array_merge($this->config, $data) : $data;

        // Register the file as uploaded
        $this->loadedFiles[] = $file;
        return $this->config;
    }

    /**
     * Resolves the full path of a configuration file.
     *
     * @param string      $file        Configuration file name.
     * @param string|null $pathConfig  Optional path to the configuration directory.
     * @return string Full path of the configuration file.
     */
    private function resolverDirPath(string $file, ?string $pathConfig = null)
    {
        $file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($file, '\/'));
        $basePath = realpath($pathConfig ?? self::ROOT_PATH_CONFIG) . DIRECTORY_SEPARATOR . $file;

        return $basePath;
    }

    /**
     * Get parsed data from a configuration file.
     *
     * @param string $file The file path.
     * @param string $ext  The format ('php', 'json', 'ini').
     * @return mixed Parsed data.
     * @throws RuntimeException If the format is invalid.
     */
    private function getData(string $file, string $ext)
    {
        return match ($ext) {
            'php'   => require $file,
            'json'  => json_decode(file_get_contents($file), true),
            'ini'   => parse_ini_file($file, true),
            default => throw new RuntimeException(sprintf('Invalid format: %s', $ext)),
        };
    }

    /**
     * Recursive load a configuration file.
     *
     * @param string $file
     * @param bool   $merge
     * @return array
     * @throws RuntimeException
     */
    private function recursiveLoadFiles(array $files, bool $merge = true, ?string $pathConfig = null): array
    {
        $config = [];
        foreach ($files as $file) {
            $data = $this->openFileConfig($file, $merge, $pathConfig);
            $config = array_merge($config, $data);
        }

        return $config;
    }

    /**
     * Get the file cache.
     *
     * @param string $file
     * @return bool
     */
    protected function getCache(string $file)
    {
        $cache = Cache::driver()->get($file);
        return $cache;
    }

    /**
     * Save view data to cache.
     *
     * @param string $file
     * @param mixed  $data
     * @return bool
     */
    protected function saveCache(string $file, $data)
    {
        $cache = Cache::driver()->set($file, $data);
        return $cache;
    }

    /**
     * Get a configuration value by key.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string|null $key = null, $default = null)
    {
        if (is_null($key)) return (array) $this->config;

        $value = $this->config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                $this->cache[$key] = $default;
                return $default;
            }

            $value = $value[$segment];
        }

        $this->cache[$key] = $value;
        return $value;
    }

    /**
     * Check if a cache entry exists.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->cache[$name]);
    }

    /**
     * Get all configuration settings.
     * @return array
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Set default configuration values.
     * @param array $defaults
     */
    public function setDefaults(array $defaults)
    {
        $this->config = array_merge($defaults, $this->config);
    }

    /**
     * Clear the cache.
     */
    public function clearCache()
    {
        $this->cache = [];
    }

    /**
     * Magic method to retrieve configuration values.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $config = ((object) $this->config[$key]) ?? null;
        return $config;
    }
}
