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
use RuntimeException;
use Throwable;
use Illuminate\Support\Str;

/**
 * Runs all of the migrations in reverse order, until they have
 * all been unapplied.
 */
class MigrateRollback extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'migrate:rollback';

    /**
     * The Command's short description
     */
    protected string $description = 'Runs the "down" method for all migrations in the last batch.';

    /**
     * The Command's usage
     */
    protected string $usage = 'migrate:rollback [options]';

    /**
     * the Command's Options
     */
    protected array $options = [
        '-s' => 'The batch to rollback',
        '-f' => 'Rollback a particular file',
    ];

    /**
     * The number of values to display before the file name in the migration list.
     * This is used for better formatting of the output.
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

    protected function processRollback(string $migration, bool $step, bool $file): int
    {
        $fileInfo = pathinfo($migration);
        $filename = $fileInfo['filename'];

        if (!$file) {
            $this->down($file);
        }

        if ($file && $this->shouldRollback($migration, $file)) {
            $this->down($file);
            $this->messageSuccess();
            return 0;
        }
    }

    /**
     * Get the list of migration files.
     */
    protected function getMigrationFiles(): array|bool
    {
        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        return glob($migrationsPath . "*.php");
    }

    /**
     * Check if a migration should be rolled back.
     */
    protected function shouldRollback(string $migrationFile, $rollback): bool
    {
        return str_contains($migrationFile, Str::snake("_create_$rollback.php"));
    }

    /**
     * Rolls back a migration by including its file and calling the `up` method.
     */
    protected function down(string $filename)
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
     */
    protected function messageError(string $file)
    {
        CLI::error(self::ARROW_SYMBOL . " Rollback $file not found!");
    }
}
