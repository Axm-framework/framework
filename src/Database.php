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
     */
    public static function connect(string $n_driver = null)
    {
        #open config DataBase
        $config = app()->config(APP_PATH . '/Config/DataBase.php');

        $driver = $n_driver ?? $config['db']['default'] ?? 'mysql';

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
     * get database connection
     */
    public static function get()
    {
        return static::$conection;
    }
}
