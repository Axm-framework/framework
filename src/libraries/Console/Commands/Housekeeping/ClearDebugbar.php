<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Housekeeping;

use Console\BaseCommand;
use Console\CLI;

/**
 * ClearDebugbar Command
 */
class ClearDebugbar extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Housekeeping';

    /**
     * The Command's name
     */
    protected string $name = 'clear:debugbar';

    /**
     * The Command's usage
     */
    protected string $usage = 'clear:debugbar';

    /**
     * The Command's short description.
     */
    protected string $description = 'Clears all debugbar JSON files.';

    /**
     * Actually runs the command.
     */
    public function run(array $params)
    {
        helpers('filesystem');
        if (
            !deleteFiles(STORAGE_PATH . DIRECTORY_SEPARATOR . 'framework'
                . DIRECTORY_SEPARATOR . 'debugbar')
        ) {

            CLI::error(self::ARROW_SYMBOL . 'Error deleting the debugbar JSON files.');
            CLI::newLine();
            return;
        }

        CLI::write(self::ARROW_SYMBOL . 'Debugbar cleared.', 'green');
        CLI::newLine();
    }
}
