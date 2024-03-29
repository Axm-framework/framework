<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Utilities;

use Console\BaseCommand;
use Console\CLI;
use Exception;

class Update extends BaseCommand
{
    protected string $group = 'Utilities';
    protected string $name = 'update';
    protected string $description = 'Updates all application dependencies to their latest version.';
    protected string $usage = 'update';
    protected array $arguments = [];
    protected array $options = [];
    private const COMPOSER_JSON_PATH = 'composer.json';
    private const SEARCH_STRING = 'axm/app';


    /**
     * run
     *
     * @param  mixed $params
     * @return void
     */
    public function run(array $params)
    {
        $this->excludePackageFromUpdate();
        $this->runCommand("@php -r \"\"");
    }

    /**
     * excludePackageFromUpdate
     *
     * @return void
     */
    private function excludePackageFromUpdate()
    {
        if ($this->isPackageExcluded()) {
            echo self::ARROW_SYMBOL . 'Excluding ' . self::SEARCH_STRING . ' from update.' . PHP_EOL;
            $this->modifyComposerJson();
        }
    }

    /**
     * isPackageExcluded
     *
     * @return bool
     */
    private function isPackageExcluded(): bool
    {
        return str_contains(file_get_contents(self::COMPOSER_JSON_PATH), self::SEARCH_STRING);
    }

    /**
     * modifyComposerJson
     *
     * @return void
     */
    private function modifyComposerJson()
    {
        $composerContent = file_get_contents(self::COMPOSER_JSON_PATH);
        $updateKey = '\"update\": [';
        $replacement = '\"update\": [\"--no-plugins\", \"--ignore-platform-reqs\",';
        $modifiedContent = str_replace($updateKey, $replacement, $composerContent);

        file_put_contents(self::COMPOSER_JSON_PATH, $modifiedContent);
    }

    /**
     * runCommand
     *
     * @param  mixed $command
     * @return void
     */
    private function runCommand(string $command)
    {
        try {
            exec($command, $output, $exitCode);
     
            if ($exitCode == 0) {
                CLI::success(self::ARROW_SYMBOL . 'The packages have been updated correctly.');
                CLI::newLine();
            }
        } catch (Exception $e) {
            CLI::error(self::ARROW_SYMBOL . 'An error occurred while updating the package: ' . $e->getMessage());
            CLI::newLine();
        }
    }
}
