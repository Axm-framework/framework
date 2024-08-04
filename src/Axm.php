<?php

/**
 * Axm Framework PHP.
 *
 * The Axm class serves as the entry point for the AXM Framework. It provides methods for
 * initializing the application.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Framework
 */
abstract class Axm
{
    private static ?App $_app = null;

    /**
     * Initializes the application.
     */
    public static function makeApplication()
    {
        return new App;
    }

    /**
     * Sets the application instance.
     *
     * This method sets the application instance, ensuring that it can only be set once.
     * If the application instance is already set, an exception is thrown.
     */
    public static function setApplication(App $app): void
    {
        if (self::$_app !== null)
            throw new Exception('Axm application can only be created once.');

        self::$_app = $app;
    }

    /**
     * Gets instance of the application.
     */
    public static function getApp(): ?App
    {
        return self::$_app;
    }

    /**
     * Returns a new instance of the ConsoleApplication class
     */
    public static function makeConsoleApplication()
    {
        static $console;
        return $console ??= new \Console\ConsoleApplication;
    }

    /**
     * Check if the application is in production mode.
     */
    public static function isProduction(): bool
    {
        return env('APP_ENVIRONMENT') === 'production';
    }

    /**
     * Get the version of a specified library.
     */
    public static function version(string $libraryName = 'axm/framework'): ?string
    {
        $v = \Composer\InstalledVersions::getVersion($libraryName);
        return $v;
    }
}
