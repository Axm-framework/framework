<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Utilities;

use Console\BaseCommand;
use Console\CLI;


class VendorPublish extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Axm';

    /**
     * The Command's name
     */
    protected string $name = 'vendor:publish';

    /**
     * The Command's short description
     */
    protected string $description = 'Publish any publishable assets from vendor packages.';

    /**
     * The Command's usage
     */
    protected string $usage = 'vendor:publish [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [];

    /**
     * The Command's Options
     */
    protected array $options = [];


    public function run($params)
    {
        if (is_file($path = ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json')) {
            $packages = json_decode(@file_get_contents($path), true);

            // Compatibility with Composer 2.0
            if (isset($packages['packages'])) {
                $packages = $packages['packages'];
            }

            foreach ($packages as $package) {
                // Verifica si la clave 'extra' está presente en el paquete actual
                if (isset($package['extra']['axm']['config'])) {
                    $installPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $package['name'] . DIRECTORY_SEPARATOR;
                    foreach ((array) $package['extra']['axm']['config'] as $name => $file) {
                        $configDir = config('paths.configPath');
                        if (!is_dir($configDir)) {
                            throw new \InvalidArgumentException("The directory  $configDir does not exist.");
                        }

                        $target = $configDir . $name . '.php';
                        $source = $installPath . $file;

                        static::publish($source, $target);
                        CLI::newLine(2);
                    }
                }
            }
        }
    }


    public static function publish(string $source, string $target): void
    {
        if (file_exists($target)) {
            CLI::write(self::ARROW_SYMBOL . "  File {$target} already exists. Skipping.", 'yellow');
            $files[] = $target;
        } else {
            copy($source, $target);
            $files[] = $target;
            CLI::write(self::ARROW_SYMBOL . "  File {$target} successfully published.", 'green');
        }

        CLI::write(self::ARROW_SYMBOL . "  File {$target} successfully published.", 'green');
    }
}
