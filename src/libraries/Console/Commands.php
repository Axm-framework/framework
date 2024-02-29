<?php

/**
 * This file is part of Axm 4 framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console;

use Console\CLI;
use Console\BaseCommand;
use Exception;
use ReflectionClass;
use ReflectionException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Core functionality for running, listing, etc commands.
 */
class Commands
{
    /**
     * The found commands.
     * @var array
     */
    public $commands    = [];
    private $classCache = [];
    private $cachedCommands = [];
    const COMMAND_EXTENSION = 'php';

    /**
     * Constructor
     * @param Logger|null $logger
     */
    public function __construct()
    {
        return $this->discoverCommands();
    }

    /**
     * run
     *
     * @param  mixed $command
     * @param  mixed $params
     * @return void
     */
    public function run(string $command, array $params)
    {
        if (!$this->verifyCommand($command, $this->commands)) {
            return;
        }

        // The file would have already been loaded during the
        // createCommandList function...
        $className = $this->commands[$command]['class'];
        $class     = new $className();

        return $class->run($params);
    }

    /**
     * Provide access to the list of commands.
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Discovers all commands in the framework, within user code, and also
     * within the vendor/composer/axm/ directory and its subdirectories.
     */
    protected function discoverCommands(): void
    {
        if ($this->commands !== []) return;

        $commandsFolder = AXM_PATH;
        $appCommandsFolder = config('paths.commandsPath') . DIRECTORY_SEPARATOR;

        // Caching
        if ($cachedCommands = $this->loadCachedCommands()) {
            $this->commands = $cachedCommands;
            return;
        }

        // Create an array of directories to scan, including the vendor directory
        $directoriesToScan = [$commandsFolder, $appCommandsFolder];
        foreach ($directoriesToScan as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || $fileInfo->getExtension() !== self::COMMAND_EXTENSION) {
                    continue;
                }

                $className = self::getClassnameFromFile($fileInfo->getPathname());
                if (!$className || !class_exists($className)) {
                    continue;
                }

                try {

                    $class = new ReflectionClass($className);
                    if (!$class->isInstantiable() || !$class->isSubclassOf(BaseCommand::class)) {
                        continue;
                    }

                    /** @var BaseCommand $class */
                    $class = new $className();
                    if (isset($class->group)) {
                        $this->commands[$class->name] = [
                            'class'       => $className,
                            'file'        => $fileInfo->getPathname(),
                            'group'       => $class->group,
                            'description' => $class->description,
                        ];
                    }

                    unset($class);
                } catch (ReflectionException $e) {
                    CLI::error($e->getMessage());
                }
            }
        }

        // Caching
        $this->saveCachedCommands($this->commands);
        asort($this->commands);
    }

    /**
     * loadCachedCommands
     * @return void
     */
    private function loadCachedCommands()
    {
        // Implement your caching logic to load from an array here
        // For example, if using a class property for caching:
        if (isset($this->cachedCommands)) {
            return $this->cachedCommands;
        }

        return null;
    }

    /**
     * @param mixed $commands
     */
    private function saveCachedCommands($commands)
    {
        // Implement your caching logic to save to an array here
        // For example, if using a class property for caching:
        $this->cachedCommands = $commands;
    }

    /**
     * @param string $filePath
     * @param bool $includeNamespace
     */
    private function getClassnameFromFile(string $filePath, bool $includeNamespace = true)
    {
        // Check if the result is cached
        if (isset($this->classCache[$filePath][$includeNamespace])) {
            return $this->classCache[$filePath][$includeNamespace];
        }

        // Check if the file exists and is readable
        if (!file_exists($filePath)) {
            throw new Exception("The $filePath file does not exist.");
        }

        if (!is_readable($filePath)) {
            throw new Exception("The $filePath file cannot be read.");
        }

        // Read file contents
        $contents = file_get_contents($filePath);

        // Search the namespace of the class
        $namespace = '';
        $namespaceRegex = '/^\s*namespace\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*)\s*;/m';
        if (preg_match($namespaceRegex, $contents, $matches)) {
            $namespace = '\\' . trim($matches[1], '\\');
        }

        // Search for the class name
        $class = '';
        $classRegex = '/^\s*(abstract\s+|final\s+)?class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m';
        if (preg_match($classRegex, $contents, $matches)) {
            $class = trim($namespace . '\\' . $matches[2], '\\');
        }

        // Cache and return result
        $this->classCache[$filePath][$includeNamespace] = $class;
        return $includeNamespace ? $class : substr(strrchr($class, "\\"), 1);
    }

    /**
     * Verifies if the command being sought is found
     * in the commands list.
     */
    public function verifyCommand(string $command, array $commands): bool
    {
        if (isset($commands[$command])) {
            return true;
        }

        $command = $command;
        $message = "Command Not Found: [$command]";

        if ($alternatives = $this->getCommandAlternatives($command, $commands)) {
            if (count($alternatives) === 1) {
                $message .= "\n\n" . 'Command in Singular' . "\n    ";
            } else {
                $message .= "\n\n" . 'Did you mean one of these?' . "\n    ";
            }

            $message .= implode("\n    ", $alternatives);
        }

        CLI::error($message);
        CLI::newLine();

        return false;
    }

    /**
     * Finds alternative of `$name` among collection
     * of commands.
     */
    protected function getCommandAlternatives(string $name, array $collection): array
    {
        $alternatives = [];

        foreach (array_keys($collection) as $commandName) {
            $lev = levenshtein($name, $commandName);

            if ($lev <= strlen($commandName) / 3 || strpos($commandName, $name) !== false) {
                $alternatives[$commandName] = $lev;
            }
        }

        ksort($alternatives, SORT_NATURAL | SORT_FLAG_CASE);

        return array_keys($alternatives);
    }
}
