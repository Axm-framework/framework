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

class RollbackDatabase extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'db:rollback';

    /**
     * The Command's short description
     */
    protected string $description = 'Rollback all database migrations. (Don\'t use -s and -f together)';

    /**
     * The Command's usage
     */
    protected string $usage = 'db:rollback [options]';

    /**
     * The Command's arguments
     */
    protected array $arguments = [
        '-f ' => 'Rollback a particular file. (optional)',
        '-s ' => 'The batch to rollback. (optional)'
    ];

    /**
     * The Command's options
     */
    protected array $options = [];

    /**
     * The space name of the migration classes in the app
     */
    private string $nameSpaceMirations = 'App\\Database\\Migrations\\';

    /**
     * @var int
     */
    private int $numberOfValuesBeforeFileName = 20;


    /**
     * Actually execute the command.
     */
    public function run(array $params): int
    {
        try {

            $migrations = $this->getMigrationFiles();

            $step = CLI::getOption('step');
            $fileToRollback = CLI::getOption('file');

            $this->processOptions($step, $fileToRollback);
            foreach ($migrations as $migration) {
                $file = pathinfo($migration);
                $this->handleMigration($file, $migration, $fileToRollback, $migrations);
            }

            CLI::write(self::ARROW_SYMBOL . 'Database rollback completed!', 'green');
            CLI::newLine();
            return 0;
        } catch (\Exception $e) {
            CLI::error($e->getMessage());
            return 1;
        }
    }

    /**
     */
    protected function handleMigration($file, $migration, $fileToRollback, $migrations)
    {
        // Lógica para manejar cada migración
        require_once $migration;
        $className = Str::studly(substr($file['filename'], $this->numberOfValuesBeforeFileName));

        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        $migrationName = str_replace([$migrationsPath, '.php'], '', $migration);

        $className = $this->nameSpaceMirations . $className;

        $class = new $className;
        $class->down();

        CLI::write(self::ARROW_SYMBOL . "db rollback on  \"{$migrationName}\" ", 'green');
    }

    /**
     * Get all migration files.
     */
    protected function getMigrationFiles(): array|bool
    {
        $ext = '.php';
        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        return glob($migrationsPath . "*$ext");
    }

    /**
     * @param mixed $step
     * @param mixed $fileToRollback
     * 
     * @return void
     */
    protected function processOptions($step, $fileToRollback)
    {
        if ($step !== 'all') {
            $this->filterMigrationsByStep($step);
        }

        if ($fileToRollback) {
            $this->rollbackSpecificFile($fileToRollback);
        }
    }

    /**
     * @param mixed $step
     * @return void
     */
    protected function filterMigrationsByStep($step)
    {
        $migrations = $this->getMigrationFiles();
        $migrations = array_slice($migrations, -$step, null, true);

        foreach ($migrations as $migration) {
            $file = pathinfo($migration);
            $this->handleMigration($file, $migration, null, $migrations);
        }
    }

    /**
     * @param mixed $fileToRollback
     */
    protected function rollbackSpecificFile($fileToRollback)
    {
        $migrations = $this->getMigrationFiles();

        foreach ($migrations as $migration) {
            $file = pathinfo($migration);

            if (strpos($migration, Str::snake("_create_$fileToRollback.php")) !== false) {
                $this->handleMigration($file, $migration, $fileToRollback, $migrations);
                CLI::write(self::ARROW_SYMBOL . 'Database rollback completed!', 'green');
                CLI::newLine();
                exit(0);
            }
        }

        CLI::error("$fileToRollback not found!");
        exit(1);
    }

    /**
     * @param mixed $filename
     * @return string
     */
    protected function getNameAfterUnderscore($filename)
    {
        // Split the string into parts using underscore as the delimiter
        $parts = explode('_', $filename);

        // The last part of the array is what we want
        $nameAfterUnderscore = end($parts);
        $this->numberOfValuesBeforeFileName = (int) (strlen(reset($parts)) + 1);

        return $nameAfterUnderscore;
    }

    /**
     * @param mixed $file
     * @param mixed $migration
     */
    protected function down($file, $migration)
    {
        require_once $migration;
        $className = Str::studly(substr($file['filename'], $this->numberOfValuesBeforeFileName));

        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        $migrationName = str_replace([$migrationsPath, '.php'], '', $migration);

        $class = new $className;
        $class->down();

        CLI::write(self::ARROW_SYMBOL . "db rollback on  \"{$migrationName}\" ", 'green');
    }
}
