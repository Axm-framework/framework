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

use Console\BaseCommand;
use Console\GeneratorTrait;

/**
 * Generates a skeleton Model file.
 */
class ModelGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     * @var string
     */
    protected $group = 'Generators';

    /**
     * The Command's Name
     * @var string
     */
    protected $name = 'make:model';

    /**
     * The Command's Description
     * @var string
     */
    protected $description = 'Generates a new model file.';

    /**
     * The Command's Usage
     * @var string
     */
    protected $usage = 'make:model <name> [options]';

    /**
     * The Command's Arguments
     * @var array
     */
    protected $arguments = [
        'name' => 'The model class name.',
    ];

    /**
     * The Command's Options
     * @var array
     */
    protected $options = [
        '--table'     => 'Supply a table name. Default: "the lowercased plural of the class name".',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserModel).',
        '--force'     => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params)
    {
        $this->component = 'Model';
        $this->directory = 'Models';
        $this->template  = 'model.tpl.php';

        $this->classNameLang = 'Class name model';
        $this->execute($params);
    }

    /**
     * Prepare options and do the necessary replacements.
     * 
     * @param string $class
     * @return string
     */
    protected function prepare(string $class): string
    {
        $table   = $this->getOption('table');
        $baseClass = class_basename($class);
        if (preg_match('/^(\S+)Model$/i', $baseClass, $match) === 1) {
            $baseClass = $match[1];
        }

        $table = is_string($table) ? $table : plural(strtolower($baseClass));
        return $this->parseTemplate($class, ['{table}' => $table]);
    }
}
