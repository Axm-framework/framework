<?php

/**
 * This file is part of Axm 4 framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console\Commands;

use Axm\Console\BaseCommand;

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
     *
     * @var string
     */
    protected $group = 'Axm';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Displays basic usage information.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'help command_name';

    /**
     * the Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'command_name' => 'The command name [default: "help"]',
    ];

    /**
     * the Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Displays the help for spark commands.
     */
    public function run(array $params)
    {
        $this->commands();

        array_shift($params);

        $command  = $params[0] ?? 'help';
        $commands = $this->commands->getCommands();

        if (!$this->commands->verifyCommand($command, $commands)) {
            return;
        }

        $class = new $commands[$command]['class']();
        $class->showHelp();
    }
}
