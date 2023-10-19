<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console\Commands\Utilities;

use Axm;
use Axm\Console\BaseCommand;
use Axm\Console\CLI;

class Test extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Utilities';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'test';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'This command executes the unit tests';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'test';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Allowed test 
     * @var array<int, string>
     */
    private static $testTypes = [
        'phpunit',
        'pestphp',
    ];


    public function run(array $params)
    {
        array_shift($params);

        if (empty($params)) {
            $testEngine = self::$testTypes[0];
        } else {
            $testEngine = strtolower(array_shift($params));

            if (!in_array($testEngine, self::$testTypes)) {
                CLI::error('Invalid test engine. Please use "phpunit" or "pestphp".', 'light_gray', 'red');
                CLI::newLine();
                return;
            }
        }

        $basePath = VENDOR_PATH;
        $command = "php $basePath/bin/$testEngine";
        passthru($command);
    }
}
