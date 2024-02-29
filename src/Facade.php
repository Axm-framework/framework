<?php

abstract class Facade
{
    protected static $providers = [];

    /**
     * Set the providers.
     *
     * @param array $p key/value array with providers
     */
    public static function providers(array $p)
    {
        self::$providers = $p;
    }

    public static function getProviders()
    {
        return self::$providers;
    }

    /**
     * Getter for the alias of the component.
     */
    protected static function getAlias()
    {
        throw new \RuntimeException('Not implemented');
    }

    protected static function getInstance($name)
    {
        return isset(self::$providers[$name]) ? self::$providers[$name] : null;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = self::getInstance(static::getAlias());
        if (!$instance) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        switch (count($args)) {
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($args[0]);
            case 2:
                return $instance->$method($args[0], $args[1]);
            default:
                return call_user_func_array([$instance, $method], $args);
        }
    }
}
