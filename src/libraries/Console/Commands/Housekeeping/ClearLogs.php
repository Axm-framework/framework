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
 * ClearLogs command.
 */
class ClearLogs extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Housekeeping';

    /**
     * The Command's name
     */
    protected string $name = 'clear:logs';

    /**
     * The Command's short description
     */
    protected string $description = 'Clears all log files.';

    /**
     * The Command's usage
     */
    protected string $usage = 'clear:logs [option]';

    /**
     * The Command's options
     */
    protected array $options = [
        '--force' => 'Force delete of all logs files without prompting.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $force = array_key_exists('force', $params) || CLI::getOption('force');

        if (!$force && CLI::prompt('Are you sure you want to delete the logs?', ['n', 'y']) === 'n') {
            CLI::error(self::ARROW_SYMBOL . 'Deleting logs aborted.', 'light_gray', 'red');
            CLI::error(self::ARROW_SYMBOL . 'If you want, use the "-force" option to force delete all log files.', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        helpers('filesystem');
        $path = config('paths.logsPath');
        if (!deleteFiles($path, false, true)) {
            CLI::error(self::ARROW_SYMBOL . 'Error in deleting the logs files.', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        CLI::write(self::ARROW_SYMBOL . 'Logs cleared.', 'green');
        CLI::newLine();
    }
}
