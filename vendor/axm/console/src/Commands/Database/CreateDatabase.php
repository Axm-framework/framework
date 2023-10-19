<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Axm\Console\Commands\Database;

use Axm\Console\BaseCommand;
use Axm\Console\CLI;
use Axm\Database;
use RuntimeException;
use Throwable;

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
     *
     * @var string
     */
    protected $name = 'db:create';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Create a new database schema.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'db:create <db_name> [options]';

    /**
     * The Command's arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'db_name' => 'The database name to use',
    ];

    /**
     * The Command's options
     *
     * @var array<string, string>
     */
    protected $options = [];

    protected $driver = null;

    /**
     * Creates a new database.
     */
    public function run(array $params)
    {
        $this->driver
            = $name
            = $params[1] ?? [];

        if (empty($name)) {
            $this->driver = $name = CLI::prompt('Database type', null, 'required'); // @codeCoverageIgnore
        }

        try {

            $this->getConnection();

            CLI::write("Database \"{$name}\" successfully created.", 'green');
            CLI::newLine();
        } catch (Throwable $e) {
            $this->showError($e);
        } finally {
            // close and release the connection
            Database::get()->getConnection()->disconnect();
        }
    }

    public function getConnection()
    {
        $connection = Database::connect($this->driver);

        if (empty($connection)) {
            $db = $this->driver ?? config('db.default');
            throw new RuntimeException("Invalid database connection [{$db}].");
        }

        return $connection;
    }
}
