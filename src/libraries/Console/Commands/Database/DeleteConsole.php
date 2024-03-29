<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Database;

use Console\BaseCommand;
use Console\CLI;

class DeleteConsole extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'd:command';

    /**
     * The Command's short description
     */
    protected string $description = 'Delete a console command';

    /**
     * The Command's usage
     */
    protected string $usage = 'd:command <command_name>';

    /**
     * The Command's arguments
     */
    protected array $arguments = [];

    /**
     * The Command's options
     */
    protected array $options = [];

    /**
     * Runs the command based on the provided parameters.
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
     */
    private function getCommandFilePath(string $command): string
    {
        return config('paths.commandsPath') . DIRECTORY_SEPARATOR . "$command.php";
    }

    /**
     * Deletes the file associated with the specified command.
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
     */
    private function printSuccessMessage(string $command): void
    {
        CLI::write(self::ARROW_SYMBOL . ' Command ' . CLI::color($command, 'yellow') .  ' deleted successfully!.', 'green');
        CLI::newLine();
    }
}
