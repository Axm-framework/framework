<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
     *
     * @var string
     */
    protected $group = 'Axm';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'publish';

    /**
     * The Command's short description
     *
     * @var string
     */
    protected $description = 'Discovers and executes all predefined Publisher classes.';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'publish [<directory>]';

    /**
     * The Command's arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'directory' => '[Optional] The directory to scan within each namespace. Default: "Publishers".',
    ];

    /**
     * the Command's Options
     *
     * @var array
     */
    protected $options = [];

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
