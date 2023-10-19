<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console\Commands\Cache;

use Axm;
use Axm\Cache\Cache;
use Axm\Console\BaseCommand;
use Axm\Console\CLI;


/**
 * Clears current cache.
 */
class ClearCache extends BaseCommand
{
    /**
     * Command grouping.
     *
     * @var string
     */
    protected $group = 'Cache';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'cache:clear';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Clears the current system caches.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'cache:clear [driver]';

    /**
     * the Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'driver' => 'The cache driver to use',
    ];

    /**
     * Clears the cache
     */
    public function run(array $params)
    {
        Axm::app()
            ->config()
            ->load(APP_PATH . '/Config/Cache.php');

        $handler = (string) ($params[1] ?? config('cache.handler'));

        if (!array_key_exists($handler, config('cache.validHandlers'))) {
            CLI::error($handler . ' is not a valid cache handler.');

            return;
        }

        $cache = Cache::driver($handler);
        if (!$cache->flush()) {

            CLI::error('Error while clearing the cache.');
            return;
        }

        CLI::write(CLI::color('Cache cleared.', 'green'));
    }
}
