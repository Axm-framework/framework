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
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * This class is responsible for managing database migrations.
 */
class Migrate extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'db:migrate';

    /**
     * The Command's short description
     */
    protected string $description = 'Run the database migrations.';

    /**
     * The Command's usage
     */
    protected string $usage = 'migrate [options]';

    /**
     * The Command's Options
     */
    protected array $options = [
        '-f'    => 'Rollback a particular file. (optional)',
        '-s'    => 'Run seeds after migration',
    ];

    /**
     * The number of values to display before the file name in the migration list.
     * This is used for better formatting of the output.
     */
    private int $numberOfValuesBeforeFileName = 20;


    /**
     * The main method that runs the migration process.
     * @return int 0 if the migration is successful, or an error code if not.
     */
    public function run(array $params): int
    {
        $seeds    = $params['s'] ?? CLI::getOption('s') ?? false;
        $rollback = $params['f'] ?? CLI::getOption('f') ?? false;

        try {
            $migrationFiles = $this->getMigrationFiles();

            foreach ($migrationFiles as $migrationFile) {
                $this->processMigration($migrationFile, $seeds, $rollback);
            }

            CLI::write(self::ARROW_SYMBOL . 'Database migration completed!', 'green');
            CLI::newLine();
            return 0;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
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
     * Process a migration file.
     */
    protected function processMigration(string $migrationFile, bool $seeds, bool $rollback): void
    {
        $fileInfo = pathinfo($migrationFile);
        $filename = $fileInfo['filename'];

        if ($filename !== 'Schema') {
            if ($rollback && $this->shouldRollback($migrationFile, $rollback)) {
                $this->migrate($filename);
                $this->displayMigrationInfo($migrationFile, $seeds, $filename, 'migrated');
            } else {
                $this->migrate($filename);
                $this->displayMigrationInfo($migrationFile, $seeds, $filename, 'applied');
            }
        }
    }

    /**
     * Check if a migration should be rolled back.
     */
    protected function shouldRollback(string $migrationFile, $rollback): bool
    {
        return str_contains($migrationFile, Str::snake("_create_$rollback.php"));
    }

    /**
     * Migrates a given migration file.
     */
    protected function migrate(string $filename)
    {
        try {

            $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
            $class = include $migrationsPath . $filename . '.php';
            $class->up();
        } catch (\Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Display information about the migration that was just run
     */
    private function displayMigrationInfo(string $migrationFile, bool $seeds, string $filename, string $status): void
    {
        CLI::write(self::ARROW_SYMBOL . " db $status on " . CLI::color($filename, 'yellow'), 'blue');
        if ($seeds) {
            $this->messageInfo($seeds, $filename);
        }
    }

    /**
     * This method is used to display a success message when a seeding process is completed
     */
    protected function messageInfo(bool $seeds, string $filename)
    {
        if ($seeds) {
            $seederClass = str_replace('Create', '', Str::studly(substr($filename, $this->numberOfValuesBeforeFileName)));

            if ($seederClass) CLI::write(self::ARROW_SYMBOL . " \"{$seederClass}\" seeded successfully!", 'green');
        }
    }
}
