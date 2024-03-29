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
use Console\CLI;
use Console\GeneratorTrait;

/**
 * Generates a skeleton controller file.
 */
class ControllerGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Generators';

    /**
     * The Command's Name
     */
    protected string $name = 'make:controller';

    /**
     * The Command's Description
     */
    protected string $description = 'Generates a new controller file.';

    /**
     * The Command's Usage
     */
    protected string $usage = 'make:controller <name> [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'name' => 'The controller class name.',
    ];

    /**
     * The Command's Options
     */
    protected array $options = [
        '--bare'      => 'Extends from Controller instead of BaseController.',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserController).',
        '--force'     => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Controller';
        $this->directory = 'Controllers';
        $this->template  = 'controller.tpl.php';

        $this->classNameLang = 'CLI.generator.className.controller';
        $this->execute($params);
    }

    /**
     * Prepare options and do the necessary replacements.
     */
    protected function prepare(string $class): string
    {
        $extends = 'BaseController';

        return $this->parseTemplate(
            $class . 'Controller',
            [ '{extends}' => $extends ],
        );
    }
}
