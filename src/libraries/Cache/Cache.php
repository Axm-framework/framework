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
     * @var string
     */
    protected static string $defaultDriver = 'File';

    /**
     * Namespace for cache driver classes.
     * @var string
     */
    protected static string $namespace = 'Axm\\Cache\\Drivers\\';

    /**
     * Unique identifier for the cache instance.
     * @var string
     */
    protected string $id;

    /**
     * Cache group identifier.
     * @var string
     */
    protected string $group = 'default';

    /**
     * Cache lifetime.
     * @var string
     */
    protected string $lifetime = '';

    /**
     * Array to store starting times for profiling.
     * @var array
     */
    protected array $start = [];

    /**
     * Retrieves or initializes a cache driver instance.
     *
     * @param string|null $driver Optional. The name of the cache driver.
     * Defaults to the default driver.
     * @return InterfaceCache The cache driver instance.
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
     *
     * @param string $driver The name of the cache driver.
     * @return void
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
     *
     * @param string $driver The name of the default cache driver.
     * @return void
     */
    public static function setDefaultDriver(string $driver = 'File'): void
    {
        self::$defaultDriver = $driver;
    }
}
