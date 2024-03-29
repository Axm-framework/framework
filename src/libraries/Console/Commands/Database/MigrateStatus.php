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
use Config\Services;

/**
 * Displays a list of all migrations and whether they've been run or not.
 */
class MigrateStatus extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'migrate:status';

    /**
     * the Command's short description
     */
    protected string $description = 'Displays a list of all migrations and whether they\'ve been run or not.';

    /**
     * the Command's usage
     */
    protected string $usage = 'migrate:status [options]';

    /**
     * the Command's Options
     */
    protected array $options = [
        '-g' => 'Set database group',
    ];

    /**
     * Namespaces to ignore when looking for migrations.
     */
    protected array $ignoredNamespaces = [
        'Axm',
        'Psr\Log',
    ];

    /**
     * Displays a list of all migrations and whether they've been run or not.
     */
    public function run(array $params)
    {
        //logo
        $runner = Services::migrations();
        $group  = $params['g'] ?? CLI::getOption('g');

        // Get all namespaces
        $namespaces = Services::autoloader()->getNamespace();

        // Collection of migration status
        $status = [];

        foreach (array_keys($namespaces) as $namespace) {
            if (env('APP_ENVIRONMENT') !== 'testing') {
                // Make Tests\\Support discoverable for testing
                $this->ignoredNamespaces[] = 'Tests\Support'; // @codeCoverageIgnore
            }

            if (in_array($namespace, $this->ignoredNamespaces, true)) {
                continue;
            }

            if (APP_NAMESPACE !== 'App' && $namespace === 'App') {
                continue; // @codeCoverageIgnore
            }

            $migrations = $runner->findNamespaceMigrations($namespace);

            if (empty($migrations)) {
                continue;
            }

            $history = $runner->getHistory((string) $group);
            ksort($migrations);

            foreach ($migrations as $uid => $migration) {
                $migrations[$uid]->name = mb_substr($migration->name, mb_strpos($migration->name, $uid . '_'));

                $date  = '---';
                $group = '---';
                $batch = '---';

                foreach ($history as $row) {
                    // @codeCoverageIgnoreStart
                    if ($runner->getObjectUid($row) !== $migration->uid) {
                        continue;
                    }

                    $date  = date('Y-m-d H:i:s', $row->time);
                    $group = $row->group;
                    $batch = $row->batch;
                    // @codeCoverageIgnoreEnd
                }

                $status[] = [
                    $namespace,
                    $migration->version,
                    $migration->name,
                    $group,
                    $date,
                    $batch,
                ];
            }
        }

        if (!$status) {
            // @codeCoverageIgnoreStart
            CLI::error(self::ARROW_SYMBOL . 'Migrations none found', 'light_gray', 'red');
            CLI::newLine();
            return;
            // @codeCoverageIgnoreEnd
        }

        $headers = [
            CLI::color('Migrations namespace', 'yellow'),
            CLI::color('Migrations version', 'yellow'),
            CLI::color('Migrations filename', 'yellow'),
            CLI::color('Migrations group', 'yellow'),
            CLI::color(str_replace(': ', '', 'Migrations.on'), 'yellow'),
            CLI::color('Migrations.batch', 'yellow'),
        ];

        CLI::table($status, $headers);
    }
}
