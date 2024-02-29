<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
     *
     * @var string
     */
    protected $group = 'Axm';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'namespaces';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Verifies your namespaces are setup correctly.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'namespaces';

    /**
     * the Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * the Command's Options
     *
     * @var array
     */
    protected $options = [];

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
