<?php

use Console\BaseCommand;

/**
 * Axm Framework PHP.
 *
 * CI Help command for the axm script.
 *
 * Lists the basic usage information for The axm script,
 * and provides a way to list help for other commands.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */
class Help extends BaseCommand
{
    /**
     * The group The command is lumped under
     * when listing commands.
     */
    protected string $group = 'Axm';

    /**
     * The Command's name
     */
    protected string $name = 'help';

    /**
     * The Command's short description
     */
    protected string $description = 'Displays basic usage information.';

    /**
     * The Command's usage
     */
    protected string $usage = 'help command_name';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'command_name' => 'The command name [default: "help"]',
    ];

    /**
     * The Command's Options
     */
    protected array $options = [];

    /**
     * Displays The help for axm commands.
     */
    public function run(array $params)
    {
        $command  = array_shift($params);
        $command  = $command ?? 'help';
        $commands = $this->commands->getCommands();

        if (! $this->commands->verifyCommand($command, $commands)) {
            return;
        }

        $class = new $commands[$command]['class']('', $this->commands);
        $class->showHelp();
    }
}
