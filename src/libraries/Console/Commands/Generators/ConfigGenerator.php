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
 * Generates a skeleton config file.
 */
class ConfigGenerator extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Generators';

    /**
     * The Command's Name
     */
    protected string $name = 'make:config';

    /**
     * The Command's Description
     */
    protected string $description = 'Generates a new config file.';

    /**
     * The Command's Usage
     */
    protected string $usage = 'make:config <name> [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        'name' => 'The config class name.',
    ];

    /**
     * The Command's Options
     */
    protected array $options = [
        '--force' => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Config';
        $this->directory = 'Config';
        $this->template  = 'config.tpl.php';

        $this->classNameLang = 'CLI.generator.className.config';
        $this->execute($params);
    }

    /**
     * Prepare options and do the necessary replacements.
     */
    protected function prepare(string $class): string
    {
        $namespace = $this->getOption('namespace') ?? APP_NAMESPACE;

        if ($namespace === APP_NAMESPACE) {
            $class = substr($class, strlen($namespace . '\\'));
        }

        return $this->parseTemplate($class);
    }
}
