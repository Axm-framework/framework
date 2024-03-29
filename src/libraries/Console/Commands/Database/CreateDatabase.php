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
use function mysqli_query;
use function mysqli_connect;

/**
 * Creates a new database.
 */
class CreateDatabase extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Database';

    /**
     * The Command's name
     */
    protected string $name = 'db:create';

    /**
     * The Command's short description
     */
    protected string $description = 'Create new database.';

    /**
     * The Command's usage
     */
    protected string $usage = 'db:create <db_name> [options]';

    /**
     * The Command's arguments
     */
    protected array $arguments = [
        'db_name' => 'The database name to use',
    ];

    /**
     * The Command's options
     */
    protected array $options = [];

    protected ?string $driver = null;

    /**
     * Creates a new database.
     */
    public function run(array $params)
    {
        $this->driver = $name = $params[1] ?? [];
        if (empty($name)) {
            $this->driver = $name = CLI::prompt(self::ARROW_SYMBOL . 'Database name', null, 'required|text');
        }

        try {
            $this->createDatabase($name);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new database with the given name.
     */
    protected function createDatabase(string $name)
    {
        $env = $this->getData();
        $connection = mysqli_connect($env['host'], $env['user'], $env['password'], '', (int) $env['port']);

        if (mysqli_query($connection, "CREATE DATABASE `$name`")) {
            CLI::write(self::ARROW_SYMBOL . "Database \"{$name}\" successfully created.", 'green');
            CLI::newLine();
        }
    }

    /**
     * Get the database connection information from the environment.
     */
    public function getData(): array
    {
        return [
            'host' => env('DB_HOST'),
            'user' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'database' => env('DB_DATABASE'),
            'port' => empty(env('DB_PORT'))
                ? 3306
                : env('DB_PORT')
        ];
    }
}
