<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands;

use Console\BaseCommand;

/**
 * CI Help command for the spark script.
 *
 * Lists the basic usage information for the spark script,
 * and provides a way to list help for other commands.
 */
class Help extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Axm';

    /**
     * The Command's name
     */
    protected string $name = 'help';

    /**
     * the Command's short description
     */
    protected string $description = 'Displays basic usage information.';

    /**
     * the Command's usage
     */
    protected string $usage = 'help command_name';

    /**
     * the Command's Arguments
     */
    protected array $arguments = [
        'command_name' => 'The command name [default: "help"]',
    ];

    /**
     * the Command's Options
     */
    protected array $options = [];

    /**
     * Displays the help for spark commands.
     */
    public function run(array $params)
    {
        $this->commands();

        array_shift($params);

        $command = $params[0] ?? 'help';
        $commands = $this->commands->getCommands();

        if (!$this->commands->verifyCommand($command, $commands)) {
            return;
        }

        $class = new $commands[$command]['class']();
        $class->showHelp();
    }
}
