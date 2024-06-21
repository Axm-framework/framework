<?php

declare(strict_types=1);

/**
 * Axm Framework PHP.
 *
 * Class BaseCommand
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */
namespace Console;

use Console\CLI;
use Console\Commands;
use Psr\Log\LoggerInterface;


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
     */
    protected string $group;

    /**
     * The Command's name
     */
    protected string $name;

    /**
     * the Command's usage description
     */
    protected string $usage;

    /**
     * the Command's short description
     */
    protected string $description;

    /**
     * the Command's options description
     */
    protected array $options = [];

    /**
     * the Command's Arguments description
     */
    protected array $arguments = [];

    /**
     * Instance of Commands so
     * commands can call other commands.
     * @var Commands
     */
    protected $commands = [];

    private array $params = [];

    protected const ARROW_SYMBOL = '➜ ';


    /**
     * Actually execute a command.
     */
    abstract public function run(array $params);

    /** 
     * Define a protected method commands that returns a new instance of the Commands class
     */
    protected function commands()
    {
        if ($this->commands !== []) {
            return;
        }

        return $this->commands = new Commands();
    }

    /**
     * Can be used by a command to run other commands.
     */
    protected function call(string $command, array $params = [])
    {
        return $this->commands()->run($command, $params);
    }

    /**
     * Show Help includes (Usage, Arguments, Description, Options).
     */
    public function showHelp()
    {
        CLI::write('CLI help Usage: ', 'yellow');

        $usage = $this->usage ?? $this->name;

        if (!empty ($this->arguments)) {
            $usage .= ' [arguments]';
        }

        CLI::write($this->setPad($usage, 0, 0, 2));

        if (!empty ($this->description)) {
            CLI::newLine();
            CLI::write(self::ARROW_SYMBOL . 'CLI help Description: ', 'yellow');
            CLI::write($this->setPad($this->description, 0, 0, 2));
        }

        if (!empty ($this->arguments)) {
            CLI::newLine();
            CLI::write(self::ARROW_SYMBOL . 'CLI help Arguments: ', 'yellow');

            $length = max(array_map('strlen', array_keys($this->arguments)));

            foreach ($this->arguments as $argument => $description) {
                CLI::write(CLI::color($this->setPad($argument, $length, 2, 2), 'green') . $description);
            }
        }

        if (!empty ($this->options)) {
            CLI::newLine();
            CLI::write(self::ARROW_SYMBOL . 'CLI help Options: ', 'yellow');

            $length = max(array_map('strlen', array_keys($this->options)));

            foreach ($this->options as $option => $description) {
                CLI::write(CLI::color($this->setPad($option, $length, 2, 2), 'green') . $description);
            }
        }
    }

    /**
     * Pads our string out so that all titles are the same length to nicely line up descriptions.
     */
    public function setPad(string $item, int $max, int $extra = 2, int $indent = 0): string
    {
        $max += $extra + $indent;
        return str_pad(str_repeat(' ', $indent) . $item, $max);
    }

    /**
     * Get pad for $key => $value array output
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
        return isset ($this->{$key});
    }
}
