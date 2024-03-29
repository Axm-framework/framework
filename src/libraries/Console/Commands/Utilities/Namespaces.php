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

use Axm;
use Console\BaseCommand;
use Console\CLI;

/**
 * Lists namespaces set in Config\Autoload with their
 * full server path. Helps you to verify that you have
 * the namespaces setup correctly.
 */
class Namespaces extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Axm';

    /**
     * The Command's name
     */
    protected string $name = 'namespaces';

    /**
     * the Command's short description
     */
    protected string $description = 'Verifies your namespaces are setup correctly.';

    /**
     * the Command's usage
     */
    protected string $usage = 'namespaces';

    /**
     * the Command's Arguments
     */
    protected array $arguments = [];

    /**
     * the Command's Options
     */
    protected array $options = [];

    /**
     * Displays the help for the spark cli script itself.
     */
    public function run(array $params)
    {
        $config = $this->getFromComposer();

        $tbody = [];
        foreach ($config as $ns => $path) {
            $path = realpath($path) ?: $path;

            $tbody[] = [
                $ns,
                realpath($path) ?: $path,
                is_dir($path) ? CLI::color('Yes', 'blue') : CLI::color('MISSING', 'red'),
            ];
        }

        $thead = [
            CLI::color('Namespace', 'green'),
            CLI::color('Path', 'green'),
            CLI::color('Found?', 'green'),
        ];

        CLI::table($tbody, $thead);
    }


    public function getFromComposer()
    {
        // Carga la configuración de espacios de nombres PSR-4 desde el archivo composer.json
        $composerJson = json_decode(file_get_contents(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json'), true);
        $autoloadConfig = $composerJson['autoload']['psr-4'];

        return (array) $autoloadConfig;
    }
}
