<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console\Commands\Utilities;

use Axm\Console\BaseCommand;
use Axm\Console\CLI;
use Exception;

class Install extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Utilities';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'install';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Install a new package';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'install [name] --option';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $composerLockPath = ROOT_PATH . '/composer.lock';
        $composerJsonPath = ROOT_PATH . '/composer.json';

        if (!is_file($composerJsonPath)) {
            CLI::error('composer.json file not found in current directory.');
            CLI::newLine();
            return;
        }

        if (!is_file($composerLockPath)) {
            $this->runCommand('composer update');
        } else {
            $this->runCommand('composer install');
        }
    }

    /**
     * Run a command and handle errors.
     *
     * @param string $command
     * @return void
     */
    private function runCommand(string $command)
    {
        try {
            exec($command);
            CLI::success('The package was successfully installed.');
            CLI::newLine();
        } catch (Exception $e) {
            CLI::error('An error occurred while installing the package:');
            CLI::error($e->getMessage());
            CLI::newLine();
        }
    }
}
