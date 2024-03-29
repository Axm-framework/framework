<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Generators;

use Console\CLI;
use Console\BaseCommand;
use Console\GeneratorTrait;

/**
 * Generates a skeleton migration file.
 */
class MigrationGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Generators';

    /**
     * The Command's Name
     */
    protected string $name = 'make:migration';

    /**
     * The Command's Description
     */
    protected string $description = 'Generates a new migration file.';

    /**
     * The Command's Usage
     */
    protected string $usage = 'make:migration <name> [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'name' => 'The migration class name.',
    ];

    /**
     * The Command's Options
     */
    protected array $options = [
        '--table'     => 'Table name to use for database sessions.',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserMigration).',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Migration';
        $this->directory = 'Database' . DIRECTORY_SEPARATOR . 'migrations';
        $this->template  = 'migration.tpl.php';

        $this->classNameLang = 'Class name migration';
        $this->execute($params);
    }

    /**
     * Prepare options and do the necessary replacements.
     */
    protected function prepare(string $class): string
    {
        $table = CLI::prompt('Table name: ', [], 'required');
        CLI::newLine();

        return $this->parseTemplate($class, ['{table}' => $table]);
    }

    /**
     * Change file basename before saving.
     */
    protected function basename(string $filename): string
    {
        $timeFormat = config()->load('Migrations.php')->get('migrations.timestampFormat');
        return  'm-' . gmdate($timeFormat) . basename($filename);
    }

    /**
     * Filters and formats a given string to a valid PHP class name.
     *
     * The function applies the following rules:
     * - Removes any characters that are not letters, numbers, or underscores.
     * - Ensures the first character is a letter or underscore.
     * - Converts the name to CamelCase format.
     */
    function formatClassName(string $className): string
    {
        // Remove characters that are not letters, numbers, or underscores
        $filteredName = preg_replace('/[^\p{L}\p{N}_]/u', '', $className);

        // Ensure the first character is a letter or underscore
        $filteredName = preg_replace('/^[^a-zA-Z_]+/', '', $filteredName);

        // Convert to CamelCase format
        $filteredName = str_replace('_', '', ucwords($filteredName, '_'));

        return $filteredName;
    }
}
