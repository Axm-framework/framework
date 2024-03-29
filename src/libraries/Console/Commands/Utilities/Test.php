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

class Test extends BaseCommand
{
    /**
     * The Command's Group
     */
    protected string $group = 'Utilities';

    /**
     * The Command's Name
     */
    protected string $name = 'test';

    /**
     * The Command's Description
     */
    protected string $description = 'This command executes the unit tests';

    /**
     * The Command's Usage
     */
    protected string $usage = 'test';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [];

    /**
     * The Command's Options
     */
    protected array $options = [];

    /**
     * Allowed test 
     */
    private static array $testTypes = [
        'phpunit',
        'pestphp',
    ];


    /**
     * @param array $params
     */
    public function run(array $params)
    {
        array_shift($params);

        if (empty($params)) {
            $testEngine = self::$testTypes[0];
        } else {
            $testEngine = strtolower(array_shift($params));

            if (!in_array($testEngine, self::$testTypes)) {
                CLI::error(self::ARROW_SYMBOL . 'Invalid test engine. Please use "phpunit" or "pestphp".', 'light_gray', 'red');
                CLI::newLine();
                return;
            }
        }

        $basePath = VENDOR_PATH;
        $command = "php $basePath/bin/$testEngine";
        passthru($command);
    }
}
