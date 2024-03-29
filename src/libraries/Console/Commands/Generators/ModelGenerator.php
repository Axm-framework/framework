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
 * Generates a skeleton Model file.
 */
class ModelGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Generators';

    /**
     * The Command's Name
     */
    protected string $name = 'make:model';

    /**
     * The Command's Description
     */
    protected string $description = 'Generates a new model file.';

    /**
     * The Command's Usage
     */
    protected string $usage = 'make:model <name> [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'name' => 'The model class name.',
    ];

    /**
     * The Command's Options
     */
    protected array $options = [
        '--table' => 'Supply a table name. Default: "the lowercased plural of the class name".',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix' => 'Append the component title to the class name (e.g. User => UserModel).',
        '--force' => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Model';
        $this->directory = 'Models';
        $this->template = 'model.tpl.php';

        $this->classNameLang = 'Class name model';
        $this->execute($params);
    }

    /**
     * Prepare options and do the necessary replacements.
     */
    protected function prepare(string $class): string
    {
        $table = $this->getOption('table');
        $baseClass = class_basename($class);
        if (preg_match('/^(\S+)Model$/i', $baseClass, $match) === 1) {
            $baseClass = $match[1];
        }

        $table = is_string($table) ? $table : plural(strtolower($baseClass));
        return $this->parseTemplate($class, ['{table}' => $table]);
    }
}
