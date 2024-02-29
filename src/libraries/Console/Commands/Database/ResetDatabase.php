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

class ResetDatabase extends BaseCommand
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
    protected $name = 'db:reset';

    /**
     * The Command's short description
     *
     * @var string
     */
    protected $description = 'Rollback, migrate and seed database.';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'db:reset [options]';

    /**
     * The Command's arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'environment' => '[Optional] The new environment to set. If none is provided, 
        this will print the current environment.',
    ];

    /**
     * The Command's options
     *
     * @var array
     */
    protected $options = [];


    public function run(array $params)
    {
        $this->rollback();
        $this->startMigration();
        CLI::info(self::ARROW_SYMBOL . 'Database migration completed!');
        CLI::newLine();

        return 0;
    }

    /**
     * @return [type]
     */
    protected function rollback()
    {
        $ext = '.php';
        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        $migrations = glob($migrationsPath . "*$ext");

        foreach ($migrations as $migration) {
            $file = pathinfo($migration);
            $this->down($file, $migration);
        }

        CLI::write(self::ARROW_SYMBOL . 'Database rollback completed!', 'green');
        CLI::newLine();
    }

    /**
     * @param mixed $file
     * @param mixed $migration
     */
    protected function down($file, $migration)
    {
        require_once $migration;
        $className = Str::studly(\substr($file['filename'], 17));

        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        $migrationName = str_replace([$migrationsPath, '.php'], '', $migration);

        $class = new $className;
        $class->down();

        CLI::write(self::ARROW_SYMBOL . "db rollback on  \"{$migrationName}\" ", 'green');
    }

    /**
     * @return [type]
     */
    protected function startMigration()
    {
        $ext = '.php';
        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;
        $migrations = glob($migrationsPath . "*$ext");

        foreach ($migrations as $migration) {
            $file = pathinfo($migration);
            $filename = $file['filename'];

            if ($filename !== 'Schema') :
                $className = Str::studly(\substr($filename, 17));

                $this->migrate($className, $filename);
                CLI::info(self::ARROW_SYMBOL . 'db migration on ' . str_replace($migrationsPath, '', $migration));

                if (!CLI::getOption('noSeed')) {
                    $seederClass = str_replace(
                        'Create',
                        '',
                        Str::studly(\substr($filename, 17))
                    );

                    if ($seederClass) {
                        CLI::write(self::ARROW_SYMBOL . "\"{$seederClass}\" seeded successfully!", 'green');
                    }
                }
            endif;
        }

        CLI::info(self::ARROW_SYMBOL . "Database migration completed!");
        CLI::newLine();
    }

    /**
     * @param mixed $className
     * @param mixed $filename
     */
    protected function migrate($className, $filename)
    {
        require_once config('paths.migrationsPath') . DIRECTORY_SEPARATOR . $filename . '.php';

        $class = new $className;
        $class->up();
    }
}
