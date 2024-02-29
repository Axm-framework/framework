<?php

/**
 * This file is part of Axm 4 framework.
 *
 * (c) axm Foundation <admin@axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console\Commands\Database;

use Console\BaseCommand;
use Console\CLI;
use RuntimeException;
use Throwable;

/**
 * Runs all of the migrations in reverse order, until they have
 * all been unapplied.
 */
class MigrateRollback extends BaseCommand
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
    protected $name = 'migrate:rollback';

    /**
     * The Command's short description
     * @var string
     */
    protected $description = 'Runs the "down" method for all migrations in the last batch.';

    /**
     * The Command's usage
     * @var string
     */
    protected $usage = 'migrate:rollback [options]';

    /**
     * the Command's Options
     *
     * @var array
     */
    protected $options = [
        '-s' => 'The batch to rollback',
        '-f' => 'Rollback a particular file',
    ];

    /**
     * The number of values to display before the file name in the migration list.
     * This is used for better formatting of the output.
     * @var int
     */
    private int $numberOfValuesBeforeFileName = 20;


    /**
     * Runs all of the migrations in reverse order, until they have
     * all been unapplied.
     */
    public function run(array $params)
    {
        $step = $params['s'] ?? CLI::getOption('s') ?? false;
        $file = $params['f'] ?? CLI::getOption('f') ?? false;

        try {

            $migrationFile = $this->getMigrationFiles();
            $migrations = ($step == 'all') ? $migrationFile : array_slice($migrationFile, -abs($step), abs($step), true);

            foreach ($migrations as $migration) {
                $this->processRollback($migration, $step, $file);
            }

            if ($file && !in_array($file, $migrations)) {
                $this->messageError($file);
                return 1;
            }

            CLI::write(self::ARROW_SYMBOL . ' Database rollback completed!', 'green');
            CLI::newLine();
            return 0;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }


    protected function processRollback(string $migration, bool $step, bool $file)
    {
        $fileInfo = pathinfo($migration);
        $filename = $fileInfo['filename'];

        if (!$file) {
            $this->down($file, $migration);
        }

        if ($file && $this->shouldRollback($migration, $file)) {
            $this->down($file, $migration);
            $this->messageSuccess();
            return 0;
        }
    }

    /**
     * Get the list of migration files.
     * @return array The list of migration files.
     */
    protected function getMigrationFiles(): array|false
    {
        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        return glob($migrationsPath . "*.php");
    }

    /**
     * Check if a migration should be rolled back.
     *
     * @param string $migrationFile
     * @param mixed $rollback
     * @return bool
     */
    protected function shouldRollback($migrationFile, $rollback)
    {
        return str_contains($migrationFile, Str::snake("_create_$rollback.php"));
    }

    /**
     * Rolls back a migration by including its file and calling the `up` method.
     * @param string $filename The filename of the migration.
     */
    protected function down($filename)
    {
        try {

            $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
            $class = include $migrationsPath . $filename . '.php';
            $class->down();
        } catch (\Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * This method is used to display a success message when a seeding process is completed
     */
    protected function messageSuccess()
    {
        CLI::write(self::ARROW_SYMBOL . " Database rollback completed!", 'green');
    }

    /**
     * This method is used to display a error message
     * @param string $file The name of the rollback file
     */
    protected function messageError(string $file)
    {
        CLI::error(self::ARROW_SYMBOL . " Rollback $file not found!");
    }
}
