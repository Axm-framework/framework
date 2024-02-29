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
 * ClearDebugbar Command
 */
class ClearDebugbar extends BaseCommand
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
    protected $name = 'clear:debugbar';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'clear:debugbar';

    /**
     * The Command's short description.
     *
     * @var string
     */
    protected $description = 'Clears all debugbar JSON files.';

    /**
     * Actually runs the command.
     */
    public function run(array $params)
    {
        helpers('filesystem');
        if (!deleteFiles(STORAGE_PATH . DIRECTORY_SEPARATOR . 'framework'
            . DIRECTORY_SEPARATOR . 'debugbar')) {

            CLI::error(self::ARROW_SYMBOL . 'Error deleting the debugbar JSON files.');
            CLI::newLine();
            return;
        }

        CLI::write(self::ARROW_SYMBOL . 'Debugbar cleared.', 'green');
        CLI::newLine();
    }
}
