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
use Exception;

class Install extends BaseCommand
{
    /**
     * The Command's Group
     */
    protected string $group = 'Utilities';

    /**
     * The Command's Name
     */
    protected string $name = 'install';

    /**
     * The Command's Description
     */
    protected string $description = 'Install a new package';

    /**
     * The Command's Usage
     */
    protected string $usage = 'install [name] --option';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [];

    /**
     * The Command's Options
     */
    protected array $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $composerLockPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.lock';
        $composerJsonPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_file($composerJsonPath)) {
            CLI::error(self::ARROW_SYMBOL . 'composer.json file not found in current directory.');
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
     */
    private function runCommand(string $command)
    {
        try {
            exec($command);
            CLI::success(self::ARROW_SYMBOL . 'The package was successfully installed.');
            CLI::newLine();
        } catch (Exception $e) {
            CLI::error(self::ARROW_SYMBOL . 'An error occurred while installing the package:');
            CLI::error($e->getMessage());
            CLI::newLine();
        }
    }
}
