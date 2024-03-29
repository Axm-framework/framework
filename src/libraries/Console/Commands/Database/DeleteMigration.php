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
use Console\GeneratorTrait;

class DeleteMigration extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'd:migration';

    /**
     * The Command's short description
     */
    protected string $description = 'Delete a migration';

    /**
     * The Command's usage
     */
    protected string $usage = 'd:migration <migration_name>';

    /**
     * The Command's arguments
     */
    protected array $arguments = [];

    /**
     * The Command's options
     */
    protected array $options = [];


    /**
     * Runs the migration deletion process based on the provided file name.
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
     */
    private function deleteMigrationFile(string $filePath): void
    {
        unlink($filePath);
        $file = str_replace('.php', '', basename($filePath));
        CLI::info(self::ARROW_SYMBOL . ' Migration ' . CLI::color($file, 'yellow') .  ' has been deleted successfully.');
    }
}
