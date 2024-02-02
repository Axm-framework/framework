<?php

namespace Axm;


abstract class Facade
{
    /**
     * @var bool Indicates whether a new instance should always be created.
     */
    protected static $alwaysNewInstance;

    /**
     * Creates an instance of the facade.
     *
     * @param string $class Class or identifier.
     * @param array  $args  Variable arguments.
     * @param bool   $newInstance Indicates if a new instance should always be created.
     * @return object Instance of the class.
     */
    protected static function createFacade(string $class = null, array $args = [], bool $newInstance = false): object
    {
        $class = static::getFacadeClass() ?? $class ?? static::class;
        return Container::getInstance()->make(
            $class,
            $args,
            $newInstance || static::$alwaysNewInstance
        );
    }

    /**
     *  Gets the class corresponding to the facade 
     *  @return string Corresponding class.
     */
    protected static function getFacadeClass()
    {
    }

    /**
     * Creates an instance of the facade with parameters 
     * @return object Instance of the class.
     */
    public static function instance(...$args): ?object
    {
        if (static::class !== self::class) {
            return self::createFacade(null, $args);
        }
    }

    /**
     * Calls a method of the corresponding class 
     *
     * @param string $class Class or identifier 
     * @param array $args Arguments variables 
     * @param bool $newInstance Indicates whether a new instance should always be created 
     * @return mixed
     */
    public static function make(string $class, array $args = [], bool $newInstance = false): mixed
    {
        if (static::class !== self::class) {
            return self::__callStatic('make', func_get_args());
        }

        return static::createFacade($class, $args, $newInstance);
    }

    /**
     * Method to be called 
     * 
     * @param string $method Method to be called 
     * @param array $params Parameters of the method 
     * @return mixed Result of the method called 
     **/
    public static function __callStatic($method, $params): mixed
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}
