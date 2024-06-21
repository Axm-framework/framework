<?php

declare(strict_types=1);

abstract class Facade
{
    protected static array $providers = [];

    /**
     * Set the providers.
     */
    public static function providers(array $p): void
    {
        self::$providers = $p;
    }

    public static function getProviders(): array
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

    protected static function getInstance(string $name)
    {
        return isset(self::$providers[$name]) ? self::$providers[$name] : null;
    }

    /**
     * Handle dynamic, static calls to the object.
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        $instance = self::getInstance(static::getAlias());

        if (!$instance) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        return $instance->{$name}(...$arguments);
    }
}
