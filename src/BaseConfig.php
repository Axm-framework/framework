<?php

namespace Axm;

use Axm\Cache\Cache;
use Axm\Exception\AxmException;

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
     *
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
     * Load a configuration file.
     *
     * @param string $file
     * @param bool   $merge
     * @return array
     * @throws AxmException
     */
    public function load(string $file, bool $merge = true)
    {
        // Check if the file has already been previously uploaded
        if (in_array($file, $this->loadedFiles)) {
            return $this->config;
        }

        $file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);

        if (!file_exists($file)) {
            throw new AxmException('Configuration file not found: ' . $file);
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'php':
                $data = require($file);
                break;
            case 'json':
                $data = json_decode(file_get_contents($file), true);
                break;
            case 'ini':
                $data = parse_ini_file($file, true);
                break;
            default:
                throw new AxmException('Invalid configuration file format: ' . $ext);
        }

        if (!is_array($data)) {
            throw new AxmException('Invalid configuration file: ' . $file);
        }

        if ($merge) {
            $this->config = array_merge($this->config, $data);
        } else {
            $this->config = $data;
        }

        // Register the file as uploaded
        $this->loadedFiles[] = $file;
        return (array) $this->config;
    }

    /**
     * Recursive load a configuration file.
     *
     * @param string $file
     * @param bool   $merge
     * @return array
     * @throws AxmException
     */
    public function recursiveLoadFiles(array $files, bool $merge = true): array
    {
        $config = [];
        foreach ($files as $file) {
            $data = $this->load($file, $merge);
            $config = array_merge($config, $data);
        }

        return $config;
    }

    /**
     * Get the view cache.
     *
     * @param string $view
     * @return bool
     */
    protected function getCache(string $view)
    {
        $cache = Cache::driver()->get($view);
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
    public function get($key, $default = null)
    {
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
     *
     * @return array
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Set default configuration values.
     *
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
        return $this->get($key);
    }
}
