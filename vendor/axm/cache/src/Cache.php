<?php

namespace Axm\Cache;

use Axm\Cache\InterfaceCache;

abstract class Cache implements InterfaceCache
{
    protected static $_drivers = [];
    protected static $_default_driver = 'File';
    protected static $_namespace = 'Axm\\Cache\\Drivers\\';
    protected $_id;
    protected $_group = 'default';
    protected $_lifetime = '';
    protected $_start = [];

    /**
     * 
     */
    public static function driver($driver = null)
    {
        if (empty($driver)) {
            $driver = self::$_default_driver;
        }

        if (!array_key_exists($driver, self::$_drivers)) {
            $class = $driver . 'Cache';
            $classNamespace = static::$_namespace . $class;
            self::$_drivers[$driver] = new $classNamespace();
        }

        self::$_drivers[$driver]->init();

        return self::$_drivers[$driver];
    }


    public static function setDefaultDriver($driver = 'File')
    {
        self::$_default_driver = $driver;
    }
}