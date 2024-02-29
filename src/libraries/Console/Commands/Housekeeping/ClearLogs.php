<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
     *
     * @var string
     */
    protected $group = 'Housekeeping';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'clear:logs';

    /**
     * The Command's short description
     *
     * @var string
     */
    protected $description = 'Clears all log files.';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'clear:logs [option]';

    /**
     * The Command's options
     *
     * @var array
     */
    protected $options = [
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
