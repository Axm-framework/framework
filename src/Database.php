<?php

namespace Axm;

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
    private static $conection;

    /**
     * Create a new database connection for models
     *
     * @param string|null $driver
     */
    public static function connect(string $driver = null)
    {
        $config = config('/DataBase.php');

        $driver = $driver ?? $config['db']['default'] ?? 'mysql';

        $capsule = static::$conection = new Capsule;
        $capsule->addConnection(
            $config['db']['connections'][$driver]
        );

        // Set the event dispatcher used by Eloquent models... (optional)
        // $capsule->setEventDispatcher(new Dispatcher(new Container));

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * Get database connection
     */
    public static function get()
    {
        return static::$conection;
    }

    /**
     * Database disconnect
     */
    public static function disconnect(string $driver)
    {
        return static::$conection->getConnection()->disconnect($driver);
    }
}
