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

use Axm;
use Console\BaseCommand;
use Console\CLI;
use Publisher\Publisher;

/**
 * Discovers all Publisher classes from the "Publishers/" directory
 * across namespaces. Executes `publish()` from each instance, parsing
 * each result.
 */
class Publish extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Axm';

    /**
     * The Command's name
     */
    protected string $name = 'publish';

    /**
     * The Command's short description
     */
    protected string $description = 'Discovers and executes all predefined Publisher classes.';

    /**
     * The Command's usage
     */
    protected string $usage = 'publish [<directory>]';

    /**
     * The Command's arguments
     */
    protected array $arguments = [
        'directory' => '[Optional] The directory to scan within each namespace. Default: "Publishers".',
    ];

    /**
     * the Command's Options
     */
    protected array $options = [];

    /**
     * Displays the help for the axm cli script itself.
     */
    public function run(array $params)
    {
        $directory = array_shift($params) ?? 'Publishers';
        if ([] === $publishers = (new Publisher)->discover($directory)) {
            CLI::write(self::ARROW_SYMBOL . 'Publish Missing %s ', [$directory]);
            return;
        }

        foreach ($publishers as $publisher) {
            if ($publisher->publish()) {
                CLI::write(self::ARROW_SYMBOL . '%s published %s file(s) to %s.', [
                    get_class($publisher),
                    count($publisher->getPublished()),
                    $publisher->getDestination(),
                ], 'green');
            } else {
                CLI::error(self::ARROW_SYMBOL . '%s failed to publish to %s!', [
                    get_class($publisher),
                    $publisher->getDestination(),
                ], 'light_gray', 'red');

                foreach ($publisher->getErrors() as $file => $e) {
                    CLI::write($file);
                    CLI::error($e->getMessage());
                    CLI::newLine();
                }
            }
        }
    }
}
