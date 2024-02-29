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

class RollbackDatabase extends BaseCommand
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
     *
     * @var string
     */
    protected $name = 'db:rollback';

    /**
     * The Command's short description
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations. (Don\'t use -s and -f together)';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'db:rollback [options]';

    /**
     * The Command's arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        '-f ' => 'Rollback a particular file. (optional)',
        '-s ' => 'The batch to rollback. (optional)'
    ];

    /**
     * The Command's options
     *
     * @var array
     */
    protected $options = [];

    /**
     * The space name of the migration classes in the app
     * @var string
     */
    private $nameSpaceMirations = 'App\\Database\\Migrations\\';

    /**
     * @var int
     */
    private int $numberOfValuesBeforeFileName = 20;


    /**
     * @param array $params
     * 
     * @return int
     */
    public function run(array $params)
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
     * @param mixed $file
     * @param mixed $migration
     * @param mixed $fileToRollback
     * @param mixed $migrations
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
     *
     * @return array
     */
    protected function getMigrationFiles()
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
     * @return CLI
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
