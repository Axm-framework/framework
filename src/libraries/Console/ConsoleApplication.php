<?php

declare(strict_types=1);

namespace Console;

use Exception;
use Console\CLI;
use Console\Commands;

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */
final class ConsoleApplication
{
    private string $version;
    private static $instance;
    private string $axmRaw;
    private $commands;
    private static bool $isRunning = false;
    private $colors = [
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'purple'       => '0;35',
        'light_purple' => '1;35',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        if (false === self::$isRunning) {
            \Axm::makeApplication();
            self::$isRunning = true;
        }

        $this->commands = new Commands();
        $this->version ??= $this->getVersion();
    }

    /**
     * Get the singleton instance of the Application class
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Runs the current command discovered on the CLI.
     */
    public function run()
    {
        try {

            $this->processRequest();

            // Obtain commands and segments
            $command = CLI::getSegment(1) ?? 'list';
            $params  = CLI::getSegments();

            return $this->commands->run($command, $params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Returns the AxmCli logo.
     */
    public function rawLogo(): string
    {
        $this->axmRaw = $this->buildLogo();
        return $this->axmRaw;
    }

    /**
     * Build Axm's logo in ASCII art format.
     */
    private function buildLogo(): string
    {
        return <<<LOGO
   _____                  
  /  _  \\ ___  ___ _____     ___ _    ___                                                              
 /  /_\  \\\\  \\/  //     \\   / __| |  |_ v{$this->version}                                            
/    |    \\>    <|  Y Y  \\ | |__| |__ | |                                                      
\\____|__  /__/\\_ \\__|_|  /  \\___|____|___|                                                           
        \\/      \\/     \\/
LOGO;
    }

    /**
     * Displays basic console information 
     */
    public function showprocessRequest(bool $suppress = false)
    {
        if ($suppress) return;

        $color      = array_rand($this->colors);
        $logo       = $this->rawLogo();
        $serverTime = date('Y-m-d H:i:s');
        $timeZone   = date('P');

        CLI::write($logo, $color);
        CLI::write(CLI::color("Axm Command Line Tool - Server Time: {$serverTime} UTC{$timeZone}", $color, null, 'bold'));
        CLI::newLine();
    }

    /**
     * Process Request
     */
    public function processRequest()
    {
        // Option to suppress the processRequest information
        if (is_int($suppress = array_search('--no-header', $_SERVER['argv'], true))) {
            unset($_SERVER['argv'][$suppress]);
            $suppress = true;
        }

        self::showprocessRequest($suppress);
    }

    /**
     * Get the version of a library from the composer.json file and remove the '^' character 
     */
    function getVersion(): string
    {
        return app()->version();
    }
}
