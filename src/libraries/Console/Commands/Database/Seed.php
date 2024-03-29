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
use Database\Seeder;
use Throwable;

/**
 * Runs the specified Seeder file to populate the database
 * with some data.
 */
class Seed extends BaseCommand
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
     * the Command's short description
     */
    protected string $description = 'Runs the specified seeder to populate known data into the database.';

    /**
     * the Command's usage
     */
    protected string $usage = 'db:seed <seeder_name>';

    /**
     * the Command's Arguments
     */
    protected array $arguments = [
        'seeder_name' => 'The seeder name to run',
    ];

    /**
     * Passes to Seeder to populate the database.
     */
    public function run(array $params)
    {
        $seeder   = new Seeder(config('database'));
        $seedName = array_shift($params);

        if (empty($seedName)) {
            $seedName = CLI::prompt(self::ARROW_SYMBOL . 'Migrations migSeeder', null, 'required'); // @codeCoverageIgnore
        }

        try {
            $seeder->call($seedName);
        } catch (Throwable $e) {
            $this->showError($e);
        }
    }
}
