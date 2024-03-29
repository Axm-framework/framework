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

use Console\BaseCommand;
use Console\GeneratorTrait;

/**
 * Generates a skeleton Filter file.
 */
class FilterGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Generators';

    /**
     * The Command's Name
     */
    protected string $name = 'make:filter';

    /**
     * The Command's Description
     */
    protected string $description = 'Generates a new filter file.';

    /**
     * The Command's Usage
     */
    protected string $usage = 'make:filter <name> [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'name' => 'The filter class name.',
    ];

    /**
     * The Command's Options
     */
    protected array $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix' => 'Append the component title to the class name (e.g. User => UserFilter).',
        '--force' => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Filter';
        $this->directory = 'Filters';
        $this->template = 'filter.tpl.php';

        $this->classNameLang = 'CLI.generator.className.filter';
        $this->execute($params);
    }
}
