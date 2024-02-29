<?php

declare(strict_types=1);

namespace Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 *  Class Database 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class Database
{
    /**
     * @var Illuminate\Database\Capsule\Manager
     */
    private static $connection;

    /**
     * Create a new database connection for models
     * @param string|null $driver
     */
    public static function connect(string $driver = null)
    {
        // If there is already a connection, do nothing
        if (isset(static::$connection)) return;

        $config = config('DataBase');
        $driver = $driver ?? $config['default'] ?? 'mysql';

        $capsule = static::$connection = new Capsule;
        $capsule->addConnection(
            $config['connections'][$driver]
        );

        // Set the event dispatcher used by Eloquent models... (optional)
        // $capsule->setEventDispatcher(new Dispatcher(new Container));

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // if (php_sapi_name() === 'cli') {
        //     Schema::$capsule = $capsule;
        // }
    }

    /**
     * Get database connection
     * @return Illuminate\Database\Capsule\Manager
     */
    public static function db()
    {
        if (static::$connection) {
            return static::$connection;
        }

        static::connect();

        return static::$connection;
    }

    /**
     * Database disconnect
     */
    public static function disconnect(string $driver)
    {
        return static::$connection
            ->getConnection()
            ->disconnect($driver);
    }
}
