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
 * Generates a skeleton Entity file.
 */
class EntityGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Generators';

    /**
     * The Command's Name
     */
    protected string $name = 'make:entity';

    /**
     * The Command's Description
     */
    protected string $description = 'Generates a new entity file.';

    /**
     * The Command's Usage
     */
    protected string $usage = 'make:entity <name> [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'name' => 'The entity class name.',
    ];

    /**
     * The Command's Options
     **/
    protected array $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix' => 'Append the component title to the class name (e.g. User => UserEntity).',
        '--force' => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Entity';
        $this->directory = 'Entities';
        $this->template = 'entity.tpl.php';

        $this->classNameLang = 'Class Name entity';
        $this->execute($params);
    }
}
