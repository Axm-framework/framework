<?php

declare(strict_types=1);

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
     * @var string
     */
    protected $group = 'Axm';

    /**
     * Name
     * @var string
     */
    protected $name = 'serve';

    /**
     * Description
     * @var string
     */
    protected $description = 'Launches the Axm PHP Development Server';

    /**
     * Usage
     * @var string
     */
    protected $usage = 'serve [--host] [--port]';

    /**
     * Options
     * @var array
     */
    protected $options = [
        '--php'  => 'The PHP Binary [default: "PHP_BINARY"]',
        '--host' => 'The HTTP Host [default: "localhost"]',
        '--port' => 'The HTTP Host Port [default: "8080"]',
    ];

    /**
     * The current port offset.
     * @var int
     */
    protected $portOffset = 0;

    /**
     * The max number of ports to attempt to serve from
     * @var int
     */
    protected $maxTries = 10;

    /**
     * Default port number
     * @var int
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
        $php  = CLI::getOption('php', PHP_BINARY);
        $host = CLI::getOption('host', 'localhost');
        $port = (int) CLI::getOption('port', $this->defaultPort);

        $scheme = 'http'; // Assuming default scheme is http for simplicity

        // Attempt alternative ports
        if (!$port = $this->findAvailablePort($host, $port)) {
            CLI::error('Could not bind to any port');
            exit;
        }

        // Server up
        $this->printServerInfo($scheme, $host, $port);
        $this->startServer($php, $host, $port);
    }

    /**
     * Find an available port
     */
    protected function findAvailablePort(string $host, int $port): ?int
    {
        $maxTries = $this->maxTries;
        for ($i = 0; $i < $maxTries; $i++) {
            if ($this->checkPort($host, $port)) {
                return $port;
            }

            $port++;
        }

        return false;
    }

    /**
     * Check if a port is available
     */
    protected function checkPort(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port);
        if ($socket !== false) {
            fclose($socket);
            return false;
        }
        return true;
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
            }

            $code = proc_close($this->process);
            if ($code !== 0) {
                throw new RuntimeException("Unknown error (code: $code)", $code);
            }
        }
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




// declare(strict_types=1);

// namespace Console\Commands\Server;

// use Console\BaseCommand;
// use Console\CLI;
// use RuntimeException;

// /**
//  * Class Serve
//  *
//  * Launch the Axm PHP Development Server
//  * @package Console\Commands\Server
//  */
// class Serve extends BaseCommand
// {

//     /**
//      * Group
//      * @var string
//      */
//     protected $group = 'Axm';

//     /**
//      * Name
//      * @var string
//      */
//     protected $name = 'serve';

//     /**
//      * Description
//      * @var string
//      */
//     protected $description = 'Launches the Axm PHP Development Server';

//     /**
//      * Usage
//      * @var string
//      */
//     protected $usage = 'serve [--host] [--port]';

//     /**
//      * Options
//      * @var array
//      */
//     protected $options = [
//         '--php'  => 'The PHP Binary [default: "PHP_BINARY"]',
//         '--host' => 'The HTTP Host [default: "localhost"]',
//         '--port' => 'The HTTP Host Port [default: "8080"]',
//     ];

//     /**
//      * The current port offset.
//      * @var int
//      */
//     protected $portOffset = 0;

//     /**
//      * The max number of ports to attempt to serve from
//      * @var int
//      */
//     protected $maxTries = 10;

//     /**
//      * Default port number
//      * @var int
//      */
//     protected int $defaultPort = 8080;

//     private string $host;
//     private int $port;
//     private string $documentRoot;
//     private string $phpBinary;
//     private $process;

//     public function run(array $params)
//     {
//         $php  = CLI::getOption('php', PHP_BINARY);
//         $host = CLI::getOption('host', 'localhost');
//         $port = (int) CLI::getOption('port', $this->defaultPort);

//         $this->documentRoot = getcwd();
//         $this->validateOptions();

//         $command = "{$php} -S {$host}:{$port} -t {$this->documentRoot}";

//         $descriptors = [
//             0 => ['pipe', 'r'],  // stdin
//             1 => STDOUT,        // stdout
//             2 => STDERR        // stderr
//         ];

//         $this->process = proc_open($command, $descriptors, $pipes);

//         if (!is_resource($this->process)) {
//             throw new RuntimeException("Failed to start server process.");
//         }

//         register_shutdown_function([$this, 'shutdown']);

//         echo "Development server started at http://{$host}:{$port}\n";
//         echo "Document root: {$this->documentRoot}\n";
//         echo "Press Ctrl+C to stop the server.\n";

//         while (proc_get_status($this->process)['running']) {
//             usleep(100000); // Sleep for 100 milliseconds
//         }
//     }

//     protected function validateOptions(): void
//     {
//         if (!is_dir($this->documentRoot) || !is_readable($this->documentRoot)) {
//             throw new RuntimeException("Invalid document root: {$this->documentRoot}.");
//         }
//     }

//     public function shutdown(): void
//     {
//         if ($this->process !== null) {
//             echo "Shutting down the server...\n";
//             proc_terminate($this->process);
//         }
//     }
// }
