<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console\Commands\Generators;

use Axm\Console\BaseCommand;
use Axm\Console\GeneratorTrait;

/**
 * Generates a skeleton Entity file.
 */
class EntityGenerator extends BaseCommand
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
    protected $name = 'make:entity';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a new entity file.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:entity <name> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The entity class name.',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserEntity).',
        '--force'     => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Entity';
        $this->directory = 'Entities';
        $this->template  = 'entity.tpl.php';

        $this->classNameLang = 'Class Name entity';
        $this->execute($params);
    }
}
