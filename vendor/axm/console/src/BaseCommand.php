<?php

/**
 * This file is part of Raxm framework.
 *
 * (c) Raxm Foundation <admin@Raxm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console;

use Axm\Console\CLI;
use Axm\Console\Commands;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

/**
 * BaseCommand is the base class used in creating CLI commands.
 *
 * @property array           $arguments
 * @property Commands        $commands
 * @property string          $description
 * @property string          $group
 * @property LoggerInterface $logger
 * @property string          $name
 * @property array           $options
 * @property string          $usage
 */
abstract class BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     * @var string
     */
    protected $group;

    /**
     * The Command's name
     * @var string
     */
    protected $name;

    /**
     * the Command's usage description
     * @var string
     */
    protected $usage;

    /**
     * the Command's short description
     * @var string
     */
    protected $description;

    /**
     * the Command's options description
     * @var array
     */
    protected $options = [];

    /**
     * the Command's Arguments description
     * @var array
     */
    protected $arguments = [];


    /**
     * Instance of Commands so
     * commands can call other commands.
     * @var Commands
     */
    protected $commands = [];

    private $params = [];

    /**
     * Actually execute a command.
     * @param array<string, mixed> $params
     */
    abstract public function run(array $params);


    protected function commands()
    {
        if ($this->commands !== []) {
            return;
        }

        return $this->commands = new Commands();
    }


    /**
     * Can be used by a command to run other commands.
     *
     * @throws ReflectionException
     * @return mixed
     */
    protected function call(string $command, array $params = [])
    {
        return $this->commands->run($command, $params);
    }

    /**
     * A simple method to display an error with line/file,
     * in child commands.
     */
    protected function showError(Throwable $e)
    {
        $message   = $e->getMessage();

        require AXM_PATH . '/views/src/cli/error_exception.php';
    }

    /**
     * Show Help includes (Usage, Arguments, Description, Options).
     */
    public function showHelp()
    {
        CLI::write('CLI help Usage: ', 'yellow');

        if (!empty($this->usage)) {
            $usage = $this->usage;
        } else {
            $usage = $this->name;

            if (!empty($this->arguments)) {
                $usage .= ' [arguments]';
            }
        }

        CLI::write($this->setPad($usage, 0, 0, 2));

        if (!empty($this->description)) {
            CLI::newLine();
            CLI::write('CLI help Description: ', 'yellow');
            CLI::write($this->setPad($this->description, 0, 0, 2));
        }

        if (!empty($this->arguments)) {
            CLI::newLine();
            CLI::write('CLI help Arguments: ', 'yellow');
            $length = max(array_map('strlen', array_keys($this->arguments)));

            foreach ($this->arguments as $argument => $description) {
                CLI::write(CLI::color($this->setPad($argument, $length, 2, 2), 'green') . $description);
            }
        }

        if (!empty($this->options)) {
            CLI::newLine();
            CLI::write('CLI help Options: ', 'yellow');
            $length = max(array_map('strlen', array_keys($this->options)));

            foreach ($this->options as $option => $description) {
                CLI::write(CLI::color($this->setPad($option, $length, 2, 2), 'green') . $description);
            }
        }
    }

    /**
     * Pads our string out so that all titles are the same length to nicely line up descriptions.
     *
     * @param int $extra How many extra spaces to add at the end
     */
    public function setPad(string $item, int $max, int $extra = 2, int $indent = 0): string
    {
        $max += $extra + $indent;
        return str_pad(str_repeat(' ', $indent) . $item, $max);
    }

    /**
     * Get pad for $key => $value array output
     *
     * @deprecated Use setPad() instead.
     * @codeCoverageIgnore
     */
    public function getPad(array $array, int $pad): int
    {
        $max = 0;
        foreach (array_keys($array) as $key) {
            $max = max($max, strlen($key));
        }

        return $max + $pad;
    }


    /**
     * Makes it simple to access our protected properties.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->{$key} ?? null;
    }

    /**
     * Makes it simple to check our protected properties.
     */
    public function __isset(string $key): bool
    {
        return isset($this->{$key});
    }
}