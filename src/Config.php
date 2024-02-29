<?php


class Config
{
    /**
     * Contain all the config
     * @var array<array-key,mixed>
     */
    protected static $config = [];

    /**
     * Get config
     * 
     * @param string $var fichero.sección.variable
     * @throws Exception
     * @return mixed
     */
    public static function get($var)
    {
        $sections = explode('.', $var);
        self::$config[$sections[0]] ??= self::load($sections[0]);

        return match (count($sections)) {
            3 => self::$config[$sections[0]][$sections[1]][$sections[2]] ?? null,
            2 => self::$config[$sections[0]][$sections[1]] ?? null,
            1 => self::$config[$sections[0]] ?? null,
            default => throw new \Exception('Máximo 3 niveles en Config::get(fichero.sección.variable), pedido: ' . $var)
        };
    }

    /**
     * Get all configs
     * 
     * @return array<array-key,mixed>
     */
    public static function getAll()
    {
        return self::$config;
    }

    /**
     * Set variable in config
     * 
     * @param string $var   variable de configuración
     * @param mixed  $value valor para atributo
     * @throws Exception
     * @return void
     */
    public static function set($var, $value)
    {
        $sections = explode('.', $var);
        match (count($sections)) {
            3 => self::$config[$sections[0]][$sections[1]][$sections[2]] = $value,
            2 => self::$config[$sections[0]][$sections[1]] = $value,
            1 => self::$config[$sections[0]] = $value,
            default => throw new \Exception('Máximo 3 niveles en Config::set(fichero.sección.variable), pedido: ' . $var)
        };
    }

    /**
     * Read config file
     * 
     * @param string $file  archivo .php o .ini
     * @param bool   $force forzar lectura de .php o .ini
     * @return array<array-key,mixed>
     */
    public static function read($file, $force = false)
    {
        if ($force) {
            return self::$config[$file] = self::load($file);
        }

        return self::$config[$file] ??= self::load($file);
    }

    /**
     * Load config file
     * 
     * @param string $file archivo
     * @return array<array-key,mixed>
     */
    private static function load(string $file): array
    {
        if (is_file($fileConfig = APP_PATH . DIRECTORY_SEPARATOR
            . 'config' . DIRECTORY_SEPARATOR . $file . '.php')) {
            return require $fileConfig;
        }

        throw new \Exception(sprintf('Error when opening the configuration file %s ', $fileConfig));
    }
}
