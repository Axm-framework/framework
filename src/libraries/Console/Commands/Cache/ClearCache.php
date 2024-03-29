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
 * Clears current cache.
 */
class ClearCache extends BaseCommand
{
    /**
     * Command grouping.
     */
    protected string $group = 'Cache';

    /**
     * The Command's name
     */
    protected string $name = 'clear:cache';

    /**
     * the Command's short description
     */
    protected string $description = 'Clears the current system caches.';

    /**
     * the Command's usage
     */
    protected string $usage = 'clear:cache [driver]';

    /**
     * the Command's Arguments
     */
    protected array $arguments = [
        'driver' => 'The cache driver to use',
    ];

    /**
     * Clears the cache
     */
    public function run(array $params)
    {
        $config = config()->load('Cache.php');

        $handler = (string) ($params[1] ?? $config->cache->handler);
        if (!array_key_exists($handler, $config->cache->validHandlers)) {
            CLI::error($handler . ' is not a valid cache handler.');
            return;
        }

        $cache = Cache::driver($handler);
        if (!$cache->flush()) {
            CLI::error(self::ARROW_SYMBOL . 'Error while clearing the cache.');
            return;
        }

        CLI::write(CLI::color(self::ARROW_SYMBOL . 'Cache cleared.', 'green'));
    }
}
