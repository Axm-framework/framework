<?php

namespace Socialite;


class Socialite
{
    /**
     * @var object
     */
    private static $instance;

    /**
     * @var array
     */
    protected static $tokens = [];

    /**
     * @var string
     */
    protected string $providerName = '';

    /**
     */
    private function __construct()
    {
    }

    /**
     * Define a static method to get an instance of the class
     */
    public static function make()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns a new instance of a provider's adapter by name
     *
     * @param string $name
     * @throws InvalidArgumentException
     * @return object
     */
    public function getProvider(string $name): object
    {
        $this->providerName = $name;
        $config = $this->getProviderConfig($name);
        $provider = sprintf('Axm\\Socialite\\Providers\\%sProvider', ucfirst($name));

        if (!class_exists($provider)) {
            throw new \InvalidArgumentException(sprintf('Provider [ %s ] not supported.', $provider));
        }

        return new $provider($config);
    }

    /**
     * Return an instance of the specified provider's driver.
     *
     * @param string $provider The name of the provider to return the driver for.
     * @return object The driver instance for the specified provider.
     */
    public static function driver(string $provider): object
    {
        $instance = self::make();
        return $instance->getProvider($provider);
    }

    /**
     * Returns the configuration array for the specified provider.
     * @return array The configuration array for the specified provider.
     */
    public function getProviderConfig(): array
    {
        $tokens = $this->openConfig();
        return $tokens;
    }

    /**
     * Opens and returns the configuration array for the specified provider.
     *
     * @return array The configuration array for the specified provider.
     * @throws \InvalidArgumentException If the configuration file does not exist.
     */
    public function openConfig(): array
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('The configuration file [ %s ] does not exist.', $file));
        }

        $config = require($file);
        return $config[$this->providerName];
    }
}
