<?php

namespace Console;

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

use Exception;
use Console\CLI;
use Console\Commands;
use \Composer\InstalledVersions;

/**
 * Clas ConsoleApplication
 */
final class ConsoleApplication
{
    private string $version;
    private static $instance;
    private string $axmRaw;
    private $commands;
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
        $this->commands = new Commands();
        $this->version ??= 1.0 ?? $this->getVersion();
    }

    /**
     * Get the singleton instance of the Application class
     */
    public static function getInstance($config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Runs the current command discovered on the CLI.
     *
     * @throws Exception
     * @param bool $useSafeOutput Indicates whether to use safe output 
     * @return mixed The result of command execution 
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
     * Returns the Ax logo.
     * @return string 
     */
    public function rawLogo()
    {
        $this->axmRaw = $this->buildLogo();
        return $this->axmRaw;
    }

    /**
     * Build Axm's logo in ASCII art format.
     * @return string
     */
    private function buildLogo()
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
     * @param bool $suppress If set to true, the output is suppressed.
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
     * processRequest
     * @return void
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
     * 
     * @param string $libraryName The name of the library for which you want to get the version 
     * @return string|null
     */
    function getVersion()
    {
        return app()->version();
    }
}
