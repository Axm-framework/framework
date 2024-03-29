<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Cache;

use Cache\Cache;
use Console\BaseCommand;
use Console\CLI;

/**
 * Shows information on the cache.
 */
class InfoCache extends BaseCommand
{
    /**
     * Command grouping.
     */
    protected string $group = 'Cache';

    /**
     * The Command's name
     */
    protected string $name = 'info:cache';

    /**
     * the Command's short description
     */
    protected string $description = 'Shows file cache information in the current system.';

    /**
     * the Command's usage
     */
    protected string $usage = 'info:cache';

    /**
     * Clears the cache
     */
    public function run(array $params)
    {
        config()->load('Cache.php');
        $handler = config('cache.handler');

        if ($handler !== 'file') {
            CLI::error(self::ARROW_SYMBOL . 'This command only supports the file cache handler.');
            return;
        }

        $caches = Cache::driver()->getAllFiles();

        $tbody  = [];
        foreach ($caches as $key => $field) {
            $tbody[] = [
                $key,
                $field,
                filesize($field),
                date("F d Y H:i:s.", fileatime($field)),
            ];
        }

        $thead = [
            CLI::color('Name', 'green'),
            CLI::color('Server Path', 'green'),
            CLI::color('Size', 'green'),
            CLI::color('Date', 'green'),
        ];

        CLI::table($tbody, $thead);
    }
}
