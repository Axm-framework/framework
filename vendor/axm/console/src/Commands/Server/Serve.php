<?php

namespace Axm\Console\Commands\Server;

use Axm\Console\BaseCommand;
use Axm\Console\CLI;


/**
 * Launch the PHP development server
 */
class Serve extends BaseCommand
{
    /**
     * Group
     *
     * @var string
     */
    protected $group = 'Axm';

    /**
     * Name
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * Description
     *
     * @var string
     */
    protected $description = 'Launches the Axm PHP Development Server';

    /**
     * Usage
     *
     * @var string
     */
    protected $usage = 'serve [--host] [--port]';

    /**
     * Options
     *
     * @var array
     */
    protected $options = [
        '--php'  => 'The PHP Binary [default: "PHP_BINARY"]',
        '--host' => 'The HTTP Host [default: "localhost"]',
        '--port' => 'The HTTP Host Port [default: "8080"]',
    ];

    /**
     * The current port offset.
     *
     * @var int
     */
    protected $portOffset = 0;

    /**
     * The max number of ports to attempt to serve from
     *
     * @var int
     */
    protected $maxTries = 10;


    /**
     * Run the server
     */
    public function run(array $params)
    {
        // Collect any user-supplied options and apply them.
        $php  = CLI::getOption('php', PHP_BINARY);
        $host = CLI::getOption('host', 'localhost');
        $port = (int) CLI::getOption('port', 8080);

        // Path Root.
        $fcroot = getcwd();

        if (is_dir($fcroot)) {
            // Get the party started.
            CLI::write("Axm development server started on http://{$host}:{$port}", 'green');
            CLI::newLine();
            CLI::write('Press Control-C to stop.', 'yellow');
        }

        $status = 1;
        while ($status !== 0 && $this->portOffset < $this->maxTries) {
            $command = "{$php} -S {$host}:{$port} -t {$fcroot}";
            passthru($command, $status);

            $this->portOffset++;
        }

        if ($status !== 0) {
            CLI::write('Unable to start the server. Exceeded maximum number of tries.', 'red');
        }
    }
}
