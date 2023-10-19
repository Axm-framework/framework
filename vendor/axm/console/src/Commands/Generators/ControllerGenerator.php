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
use Axm\Console\CLI;
use Axm\Console\GeneratorTrait;

/**
 * Generates a skeleton controller file.
 */
class ControllerGenerator extends BaseCommand
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
    protected $name = 'make:controller';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a new controller file.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:controller <name> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The controller class name.',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--bare'      => 'Extends from Axm\Controller instead of BaseController.',
        '--restful'   => 'Extends from a RESTful resource, Options: [controller, presenter]. Default: "controller".',
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
        $bare = $this->getOption('bare');
        $rest = $this->getOption('restful');

        $useStatement = trim(APP_NAMESPACE, '\\') . '\Controllers\BaseController';
        $extends      = 'BaseController';

        // Gets the appropriate parent class to extend.
        if ($bare || $rest) {
            if ($bare) {
                $useStatement = 'Axm\Controller';
                $extends      = 'Controller';
            } elseif ($rest) {
                $rest = is_string($rest) ? $rest : 'controller';

                if (!in_array($rest, ['controller', 'presenter'], true)) {
                    // @codeCoverageIgnoreStart
                    $rest = CLI::prompt(lang('CLI.generator.parentClass'), ['controller', 'presenter'], 'required');
                    CLI::newLine();
                    // @codeCoverageIgnoreEnd
                }

                if ($rest === 'controller') {
                    $useStatement = 'Axm\RESTful\ResourceController';
                    $extends      = 'ResourceController';
                } elseif ($rest === 'presenter') {
                    $useStatement = 'Axm\RESTful\ResourcePresenter';
                    $extends      = 'ResourcePresenter';
                }
            }
        }

        return $this->parseTemplate(
            $class,
            ['{useStatement}', '{extends}'],
            [$useStatement, $extends],
            ['type' => $rest]
        );
    }
}
