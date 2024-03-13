<?php


class Config
{
    /**
     * Contain all the config
     * @var array<array-key,mixed>
     */
    protected static $config = [];

    /**
     * default config path 
     */
    private const DEFAULT_DIR = APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    /**
     * Get config
     * 
     * @throws Exception
     * @return mixed
     */
    public static function get(string $var)
    {
        $sections = explode('.', $var);
        self::$config[$sections[0]] ??= self::load($sections[0]);

        return match (count($sections)) {
            3 => self::$config[$sections[0]][$sections[1]][$sections[2]] ?? null,
            2 => self::$config[$sections[0]][$sections[1]] ?? null,
            1 => self::$config[$sections[0]] ?? null,
            default => throw new \Exception('Maximum 3 levels in Config::get(file.section.variable), order: ' . $var)
        };
    }

    /**
     * Get all configs
     * @return array<array-key,mixed>
     */
    public static function getAll()
    {
        return self::$config;
    }

    /**
     * Set variable in config
     * 
     * @throws Exception
     * @return void
     */
    public static function set(string $var, $value)
    {
        $sections = explode('.', $var);
        match (count($sections)) {
            3 => self::$config[$sections[0]][$sections[1]][$sections[2]] = $value,
            2 => self::$config[$sections[0]][$sections[1]] = $value,
            1 => self::$config[$sections[0]] = $value,
            default => throw new \Exception('Maximum 3 levels in Config::get(file.section.variable), order: ' . $var)
        };
    }

    /**
     * Read config file
     * @return array<array-key,mixed>
     */
    public function read(string $file, string $path = null, bool $force = false)
    {
        if ($force) {
            return self::$config[$file] = self::load($file, $path);
        }

        return self::$config[$file] ??= self::load($file, $path);
    }

    /**
     * Load config file
     * @return array<array-key,mixed>
     */
    public static function load(string $name, string $path = null): array
    {
        $path = $path ?? self::DEFAULT_DIR;
        if (is_file($fileConfig = $path . $name . '.php')) {
            return require $fileConfig;
        }

        throw new \Exception(sprintf('Error when opening the configuration file [ %s ] ', $fileConfig));
    }
}
