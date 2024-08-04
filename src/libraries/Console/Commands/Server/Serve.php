<?php

declare(strict_types=1);

namespace Console\Commands\Server;

use Console\BaseCommand;
use Console\CLI;
use RuntimeException;

class Serve extends BaseCommand
{
    protected string $group = 'Axm';
    protected string $name = 'serve';
    protected string $description = 'Launches the Axm PHP Development Server';
    protected string $usage = 'serve [--host] [--port]';
    protected array $options = [
        '--php' => 'The PHP Binary [default: "PHP_BINARY"]',
        '--host' => 'The HTTP Host [default: "localhost"]',
        '--port' => 'The HTTP Host Port [default: "8080"]',
    ];

    protected int $portOffset = 0;
    protected int $maxTries = 10;
    protected int $defaultPort = 8080;
    protected $process;
    protected float $startTime;
    protected bool $shouldShutdown = false;
    protected int $serverPid;

    public function run(array $params)
    {
        $php = CLI::getOption('php', PHP_BINARY);
        $host = CLI::getOption('host', 'localhost');
        $port = (int) CLI::getOption('port', $this->defaultPort);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'signalHandler']);
            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        }

        $this->startServer($php, $host, $port);
    }

    protected function startServer(string $php, string $host, int $port, bool $forceKill = false)
    {
        $fcroot = ROOT_PATH;
        if (!is_dir($fcroot)) throw new RuntimeException("Invalid root directory: $fcroot");
        
        if ($forceKill) $this->killExistingProcess($host, $port);

        $command = sprintf('%s -S %s:%d -t %s', escapeshellarg($php), $host, $port, escapeshellarg($fcroot));

        $this->printServerHeader();
        CLI::write("  Command: " . CLI::color($command, 'cyan'), 'dark_gray');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'signalHandler']);
            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        }

        $this->process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);

        if (!is_resource($this->process)) throw new RuntimeException("Failed to start the server process.");

        $status = proc_get_status($this->process);
        $this->serverPid = $status['pid'];

        $this->printServerInfo('http', $host, $port);

        // Simplemente espera hasta que el proceso termine
        while (proc_get_status($this->process)['running']) {
            sleep(1);
            if (function_exists('pcntl_signal_dispatch'))
                pcntl_signal_dispatch();
        }

        $this->shutdown(true, true);
    }

    protected function printServerHeader()
    {
        CLI::newLine();
        $header = "  AXM DEVELOPMENT SERVER  ";
        $padding = str_repeat('=', strlen($header));
        CLI::write($padding, 'green');
        CLI::write($header, 'green');
        CLI::write($padding, 'green');
        CLI::newLine();
    }

    protected function printServerInfo(string $scheme, string $host, int $port)
    {
        $url = "{$scheme}://{$host}:{$port}";
        CLI::write("  " . CLI::color('Server running at:', 'green'));
        CLI::write("  " . CLI::color($url, 'yellow'));
        CLI::newLine();
        CLI::write("  " . CLI::color('Document root:', 'green') . " " . CLI::color(ROOT_PATH, 'dark_gray'));
        CLI::write("  " . CLI::color('Environment:', 'green') . "   " . CLI::color(getenv('AXM_ENV') ?: 'production', 'dark_gray'));
        CLI::newLine();
        CLI::write("  " . CLI::color('Press Ctrl+C to stop the server', 'cyan'));
        CLI::newLine();
        $this->printServerReadyMessage();
    }

    protected function printServerReadyMessage()
    {
        CLI::write(str_repeat('-', 50), 'dark_gray');
        CLI::write("  " . CLI::color('Server is ready to handle requests!', 'green'));
        CLI::write(str_repeat('-', 50), 'dark_gray');
        CLI::newLine();
    }

    public function signalHandler($signo)
    {
        switch ($signo) {
            case SIGINT:
            case SIGTERM:
                $this->shutdown(true, true);
                exit;
        }
    }

    public function shutdown(bool $exit = false, bool $message = true)
    {
        if ($message) {
            CLI::newLine();
            CLI::write("  " . CLI::color('Shutting down the server...', 'yellow'));
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process, SIGINT);
            proc_close($this->process);
        }

        if ($message) {
            CLI::write("  " . CLI::color('Server stopped successfully.', 'green'));
            CLI::newLine();
        }

        if ($exit) exit(0);
    }

    protected function killExistingProcess(string $host, int $port)
    {
        if (PHP_OS_FAMILY === 'Windows')
            exec("FOR /F \"usebackq tokens=5\" %a in (`netstat -ano ^| findstr :$port`) do taskkill /F /PID %a");
        else 
            exec("lsof -ti tcp:$port | xargs kill -9");

        sleep(1); // Dar tiempo para que el proceso se cierre completamente
    }

    protected function formatAndPrintOutput($output)
    {
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (preg_match('/^\[(.*?)\] (\[.*?\] )?(.*?)$/', $line, $matches)) {
                $timestamp = $matches[1];
                $clientInfo = $matches[2] ?? '';
                $content = $matches[3];

                $formattedLine = $this->formatTimestampAndClientInfo($timestamp, $clientInfo);
                $formattedLine .= $this->formatHttpRequest($content);

                CLI::write($formattedLine, 'light_gray');
            } else 
                CLI::write(CLI::color($line, 'light_gray'));
        }
    }

    protected function formatTimestampAndClientInfo($timestamp, $clientInfo)
    {
        $formattedTimestamp = CLI::color("[$timestamp]", 'light_gray');
        $formattedClientInfo = CLI::color(" $clientInfo", 'light_gray');

        return $formattedTimestamp . $formattedClientInfo;
    }

    protected function formatHttpRequest($content)
    {
        if (preg_match('/(\[.*?\]) (\[(\d+)\]): ([A-Z]+) (.*)/', $content, $requestMatches)) {
            $statusCode = $requestMatches[3];
            $method = $requestMatches[4];
            $path = $requestMatches[5];

            $coloredMethod = $this->colorizeMethod($method);
            $coloredPath = CLI::color($path, 'light_gray');
            $coloredStatus = $this->colorizeStatusCode($statusCode);

            return "{$coloredStatus}: {$coloredMethod} {$coloredPath}";
        }

        return CLI::color($content, 'light_gray');
    }

    protected function formatAndPrintError($error)
    {
        $lines = explode("\n", trim($error));
        foreach ($lines as $line) 
            CLI::write(CLI::color('ERROR: ', 'red') . CLI::color($line, 'light_red'));
    }

    protected function colorizeStatusCode($statusCode): string
    {
        $color = match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'green',
            $statusCode >= 300 && $statusCode < 400 => 'yellow',
            $statusCode >= 400 && $statusCode < 500 => 'light_red',
            default => 'red',
        };

        return CLI::color("[$statusCode]", $color);
    }

    protected function colorizeMethod($method)
    {
        $colors = [
            'GET' => 'green',
            'POST' => 'yellow',
            'PUT' => 'blue',
            'DELETE' => 'red',
            'PATCH' => 'purple',
            'HEAD' => 'cyan',
            'OPTIONS' => 'white'
        ];

        return CLI::color($method, $colors[$method] ?? 'white');
    }
}
