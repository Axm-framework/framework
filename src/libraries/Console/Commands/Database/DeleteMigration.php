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
use Console\GeneratorTrait;

class DeleteMigration extends BaseCommand
{
    use GeneratorTrait;

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
    protected $name = 'd:migration';

    /**
     * The Command's short description
     * @var string
     */
    protected $description = 'Delete a migration';

    /**
     * The Command's usage
     * @var string
     */
    protected $usage = 'd:migration <migration_name>';

    /**
     * The Command's arguments
     * @var array<string, string>
     */
    protected $arguments = [];

    /**
     * The Command's options
     * @var array
     */
    protected $options = [];


    /**
     * Runs the migration deletion process based on the provided file name.
     *
     * @param array $params An array of parameters, with the file name at index 1.
     * @return int Returns 0 on success, 1 on failure.
     */
    public function run(array $params): int
    {
        $filename = ($params[1]) ?? '';
        if (!str_contains($filename, 'Migration')) {
            $filename .= 'Migration';
        }

        $migrationsPath = config('paths.migrationsPath') . DIRECTORY_SEPARATOR;

        $migrations = glob($migrationsPath . "*_$filename.php") ?? [];

        if (empty($migrations)) {
            CLI::error(self::ARROW_SYMBOL . " File \"{$filename}\" not found");
            CLI::newLine();
            return 1;
        }

        foreach ($migrations as $migrationFile) {
            $this->deleteMigrationFile($migrationFile);
        }

        CLI::newLine();
        return 0;
    }

    /**
     * Deletes a migration file.
     *
     * @param string $filePath The path to the migration file.
     * @return void
     */
    private function deleteMigrationFile(string $filePath): void
    {
        unlink($filePath);
        $file = str_replace('.php', '', basename($filePath));
        CLI::info(self::ARROW_SYMBOL . ' Migration ' . CLI::color($file, 'yellow') .  ' has been deleted successfully.');
    }
}
