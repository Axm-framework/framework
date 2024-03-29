<?php

declare(strict_types=1);

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Server;

use Console\BaseCommand;
use Console\CLI;
use RuntimeException;

/**
 * Class Serve
 *
 * Launch the Axm PHP Development Server
 * @package Console\Commands\Server
 */
class Serve extends BaseCommand
{
    /**
     * Group
     */
    protected string $group = 'Axm';

    /**
     * Name
     */
    protected string $name = 'serve';

    /**
     * Description
     */
    protected string $description = 'Launches the Axm PHP Development Server';

    /**
     * Usage
     */
    protected string $usage = 'serve [--host] [--port]';

    /**
     * Options
     */
    protected array $options = [
        '--php' => 'The PHP Binary [default: "PHP_BINARY"]',
        '--host' => 'The HTTP Host [default: "localhost"]',
        '--port' => 'The HTTP Host Port [default: "8080"]',
    ];

    /**
     * The current port offset.
     */
    protected int $portOffset = 0;

    /**
     * The max number of ports to attempt to serve from
     */
    protected int $maxTries = 10;

    /**
     * Default port number
     */
    protected int $defaultPort = 8080;

    /**
     * 
     */
    protected $process;

    /**
     * Run the server
     */
    public function run(array $params)
    {
        // Collect any user-supplied options and apply them.
        $php = CLI::getOption('php', PHP_BINARY);
        $host = CLI::getOption('host', 'localhost');
        $port = (int) CLI::getOption('port', $this->defaultPort);

        // Attempt alternative ports
        // if (!$port = $this->findAvailablePort($host, $port)) {
        //     CLI::error('Could not bind to any port');
        //     exit;
        // }

        CLI::loading(1);

        // Server up
        $this->startServer($php, $host, $port);
    }

    /**
     * Find an available port
     */
    protected function findAvailablePort(string $host, int $startPort): ?int
    {
        $maxTries = $this->maxTries;
        for ($port = $startPort; $port < $startPort + $maxTries; $port++) {
            if ($this->checkPort($host, $port)) {
                return $port;
            }
        }

        return null;
    }

    /**
     * Check if a port is available by attempting to connect to it.
     */
    protected function checkPort(string $host, int $port): bool
    {
        try {
            $url = "http://$host:$port";
            $headers = @get_headers($url);
            return !empty($headers);
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Start the server
     */
    protected function startServer(string $php, string $host, int $port)
    {
        // Path Root.
        $fcroot = getcwd();
        if (is_dir($fcroot)) {
            $descriptors = [
                0 => ['pipe', 'r'],  // stdin
                1 => STDOUT,        // stdout
                2 => STDERR        // stderr
            ];

            $command = "{$php} -S {$host}:{$port} -t {$fcroot}";
            $this->process = proc_open($command, $descriptors, $pipes);

            if (is_resource($this->process)) {
                while ($output = fgets($pipes[0])) {
                    if (strpos($output, 'SIGINT') !== false) {
                        $this->shutdown();
                    }
                }

                $this->printServerInfo('http', $host, $port);
            }

            $code = proc_close($this->process);
            if ($code !== 0) {
                throw new RuntimeException("Unknown error (code: $code)", $code);
            }
        }
    }

    /**
     * Shutdown the server
     */
    protected function shutdown()
    {
        CLI::info('Shutting down the server...');
        proc_terminate($this->process);
    }

    /**
     * Print server information
     */
    protected function printServerInfo(string $scheme, string $host, int $port)
    {
        CLI::info(self::ARROW_SYMBOL . 'Axm development server started on: ' . CLI::color("{$scheme}://{$host}:{$port}", 'green'));

        CLI::newLine();
        CLI::write(self::ARROW_SYMBOL . 'Press Control-C to stop.', 'yellow');
        CLI::newLine(2);
    }
}
