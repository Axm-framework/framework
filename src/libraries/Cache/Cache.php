<?php

declare(strict_types=1);

namespace Cache;

use Cache\InterfaceCache;

/**
 * Abstract class for cache handling.
 *
 * @package Axm\Cache
 */
abstract class Cache implements InterfaceCache
{
    /**
     * Array of instantiated cache drivers.
     * @var InterfaceCache[]
     */
    protected static array $drivers = [];

    /**
     * Default cache driver name.
     */
    protected static string $defaultDriver = 'File';

    /**
     * Namespace for cache driver classes.
     */
    protected static string $namespace = 'Axm\\Cache\\Drivers\\';

    /**
     * Unique identifier for the cache instance.
     */
    protected string $id;

    /**
     * Cache group identifier.
     */
    protected string $group = 'default';

    /**
     * Cache lifetime.
     */
    protected string $lifetime = '';

    /**
     * Array to store starting times for profiling.
     */
    protected array $start = [];

    /**
     * Retrieves or initializes a cache driver instance.
     */
    public static function driver(string $driver = null): InterfaceCache
    {
        $driver = $driver ?? self::$defaultDriver;
        self::resolveDriver($driver);

        self::$drivers[$driver]->init();
        return self::$drivers[$driver];
    }

    /**
     * Resolves and instantiates a cache driver if not already initialized.
     */
    protected static function resolveDriver(string $driver = 'File'): void
    {
        if (!isset(self::$drivers[$driver])) {
            $class = $driver . 'Cache';
            $classNamespace = self::$namespace . $class;
            self::$drivers[$driver] = new $classNamespace();
        }
    }

    /**
     * Sets the default cache driver.
     */
    public static function setDefaultDriver(string $driver = 'File'): void
    {
        self::$defaultDriver = $driver;
    }
}
