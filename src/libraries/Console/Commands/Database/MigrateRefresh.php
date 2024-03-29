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

/**
 * Does a rollback followed by a latest to refresh the current state
 * of the database.
 */
class MigrateRefresh extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'migrate:refresh';

    /**
     * The Command's short description
     */
    protected string $description = 'Does a rollback followed by a latest to refresh the current state of the database.';

    /**
     * The Command's usage
     */
    protected string $usage = 'migrate:refresh [options]';

    /**
     * The Command's Options
     */
    protected array $options = [
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
