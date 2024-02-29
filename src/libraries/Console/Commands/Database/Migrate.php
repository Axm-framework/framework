<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
     *
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's name
     * @var string
     */
    protected $name = 'db:migrate';

    /**
     * The Command's short description
     * @var string
     */
    protected $description = 'Run the database migrations.';

    /**
     * The Command's usage
     * @var string
     */
    protected $usage = 'migrate [options]';

    /**
     * The Command's Options
     * @var array
     */
    protected $options = [
        '-f'    => 'Rollback a particular file. (optional)',
        '-s'    => 'Run seeds after migration',
    ];

    /**
     * The number of values to display before the file name in the migration list.
     * This is used for better formatting of the output.
     * @var int
     */
    private int $numberOfValuesBeforeFileName = 20;


    /**
     * The main method that runs the migration process.
     *
     * @param array $params The command line parameters.
     * @return int 0 if the migration is successful, or an error code if not.
     */
    public function run(array $params)
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
     * @return array The list of migration files.
     */
    protected function getMigrationFiles(): array|false
    {
        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        return glob($migrationsPath . "*.php");
    }

    /**
     * Process a migration file.
     *
     * @param string $migrationFile The path to the migration file.
     * @param bool $seeds Run the seeds command after the migration.
     * @param bool $rollback Rollback the migration.
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
     * Migrates a given migration file.
     * @param string $filename The name of the migration file.
     */
    protected function migrate($filename)
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
     *
     * @param string $migrationFile The name of the migration file
     * @param bool $seeds Whether or not seeds were run
     * @param string $filename The name of the seed file (if seeds were run)
     * @param string $status The status of the migration
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
     *
     * @param array $seeds An array of seeders that were run
     * @param string $filename The name of the migration file
     */
    protected function messageInfo($seeds, string $filename)
    {
        if ($seeds) {
            $seederClass = str_replace('Create', '', Str::studly(substr($filename, $this->numberOfValuesBeforeFileName)));

            if ($seederClass) CLI::write(self::ARROW_SYMBOL . " \"{$seederClass}\" seeded successfully!", 'green');
        }
    }
}
