<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console\Commands\Database;

use Console\BaseCommand;
use Console\CLI;

class DeleteConsole extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's name
     * @var string
     */
    protected $name = 'd:command';

    /**
     * The Command's short description
     * @var string
     */
    protected $description = 'Delete a console command';

    /**
     * The Command's usage
     * @var string
     */
    protected $usage = 'd:command <command_name>';

    /**
     * The Command's arguments
     * @var array<string, string>
     */
    protected $arguments = [];

    /**
     * The Command's options
     * @var array
     */
    protected $options = [];


    /**
     * Runs the command based on the provided parameters.
     *
     * @param array $params An array of command parameters.
     * @return int Returns 0 on success, 1 on failure.
     */
    public function run(array $params): int
    {
        $command = $params[1] ?? '';

        if (!str_contains($command, 'Command')) {
            $command .= 'Command';
        }

        $commandFile = $this->getCommandFilePath($command);
        if (!$this->deleteCommandFile($commandFile, $command)) {
            return 1;
        }

        $this->printSuccessMessage($command);
        return 0;
    }

    /**
     * Gets the file path for the specified command.
     *
     * @param string $command The name of the command.
     * @return string The file path for the command.
     */
    private function getCommandFilePath(string $command): string
    {
        return config('paths.commandsPath') . DIRECTORY_SEPARATOR . "$command.php";
    }

    /**
     * Deletes the file associated with the specified command.
     *
     * @param string $commandFile The file path for the command.
     * @param string $command     The name of the command.
     * @return bool Returns true on successful deletion, false otherwise.
     */
    private function deleteCommandFile(string $commandFile, string $command): bool
    {
        if (!file_exists($commandFile)) {
            CLI::error(self::ARROW_SYMBOL . " \"{$command}\" doesn't exist!");
            return false;
        }

        if (!unlink($commandFile)) {
            CLI::error(self::ARROW_SYMBOL . " Couldn't delete \"{$command}\", you might need to remove it manually");
            return false;
        }

        return true;
    }

    /**
     * Prints a success message for the deleted command.
     *
     * @param string $command The name of the deleted command.
     * @return void
     */
    private function printSuccessMessage(string $command): void
    {
        CLI::write(self::ARROW_SYMBOL . ' Command ' . CLI::color($command, 'yellow') .  ' deleted successfully!.', 'green');
        CLI::newLine();
    }
}
