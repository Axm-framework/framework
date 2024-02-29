<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
     *
     * @var string
     */
    protected $group = 'Generators';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:migration';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a new migration file.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:migration <name> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The migration class name.',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
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
     * @param string $className The name of the class to be formatted.
     * @return string The formatted and valid PHP class name.
     */
    function formatClassName($className)
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
