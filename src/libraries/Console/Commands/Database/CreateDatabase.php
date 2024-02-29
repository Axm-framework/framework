<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
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
     *
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's name
     * @var string
     */
    protected $name = 'db:create';

    /**
     * The Command's short description
     * @var string
     */
    protected $description = 'Create new database.';

    /**
     * The Command's usage
     * @var string
     */
    protected $usage = 'db:create <db_name> [options]';

    /**
     * The Command's arguments
     * @var array<string, string>
     */
    protected $arguments = [
        'db_name' => 'The database name to use',
    ];

    /**
     * The Command's options
     * @var array<string, string>
     */
    protected $options = [];

    /**
     * @var string
     */
    protected $driver = null;

    /**
     * Creates a new database.
     * @param array $params
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
     *
     * @param string $name The name of the database to create.
     * @throws Exception If there is an error creating the database.
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
     * @return array The database connection information.
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
