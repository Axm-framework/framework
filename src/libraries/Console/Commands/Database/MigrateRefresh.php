<?php

/**
 * This file is part of axm 4 framework.
 *
 * (c) axm Foundation <admin@axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console\Commands\Database;

use Console\BaseCommand;
use Console\CLI;

/**
 * Does a rollback followed by a latest to refresh the current state
 * of the database.
 */
class MigrateRefresh extends BaseCommand
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
    protected $name = 'migrate:refresh';

    /**
     * The Command's short description
     * @var string
     */
    protected $description = 'Does a rollback followed by a latest to refresh the current state of the database.';

    /**
     * The Command's usage
     * @var string
     */
    protected $usage = 'migrate:refresh [options]';

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--all' => 'Set latest for all namespace, will ignore (-n) option',
        '-f'    => 'Force command - this option allows you to bypass the confirmation question when running this command in a production environment',
    ];

    /**
     * Does a rollback followed by a latest to refresh the current state
     * of the database.
     */
    public function run(array $params)
    {
        $params['b'] = 0;
        if (env('APP_ENVIRONMENT') === 'production') {

            $force = array_key_exists('f', $params) || CLI::getOption('f');

            if (!$force && CLI::prompt(self::ARROW_SYMBOL . ' Refresh confirm migrations?', ['y', 'n']) === 'n') {
                return;
            }

            $params['f'] = null;

        }

        $this->call('migrate:rollback', $params);
        $this->call('migrate', $params);
    }
}
