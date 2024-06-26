<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Database;

use Console\BaseCommand;
use Console\CLI;

class SeedDatabase extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'db:seed';

    /**
     * The Command's short description
     */
    protected string $description = 'Seed the database with records';

    /**
     * The Command's usage
     */
    protected string $usage = 'db:seed [options]';

    /**
     * The Command's arguments
     */
    protected array $arguments = [];

    /**
     * The Command's options
     */
    protected array $options = [];


    /**
     * Actually execute the command.
     */
    public function run(array $params)
    {
        $ext = '.php';
        $seedsPath = config('paths.seedsPath') . DIRECTORY_SEPARATOR;
        $seeds = glob($seedsPath . "*$ext");

        if (!file_exists($seedsPath . 'DatabaseSeeder.php')) {
            CLI::error(self::ARROW_SYMBOL . 'DatabaseSeeder not found! Refer to the docs.');
            return 1;
        }

        if (count($seeds) === 1) {
            CLI::error(self::ARROW_SYMBOL . 'No seeds found! Create one with the g:seed command.');
            return 1;
        }

        $seeder = new Seeder;

        if (count($seeder->run()) === 0) {
            CLI::error(self::ARROW_SYMBOL . 'No seeds registered. Add your seeds in DatabaseSeeder.php');
            return 1;
        }

        foreach ($seeder->run() as $seed) {
            $seeder->call($seed);
            CLI::write(self::ARROW_SYMBOL . "\"{$seed}\" seeded successfully!", 'green');
            CLI::newLine();
        }

        CLI::info(self::ARROW_SYMBOL . 'Database seed complete');
        CLI::newLine();

        return 0;
    }
}
