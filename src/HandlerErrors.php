<?php

namespace Axm;

use Axm;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Axm\Console\CLIException;
use ErrorException;


class HandlerErrors
{
    private static $instance = null;

    private function __construct()
    {
    }

    /**
     * Gets the single instance of HandlerErrors.
     * @return self
     */
    public static function get(): self
    {
        if (null !== self::$instance) {
            return self::$instance;
        }

        self::$instance = new HandlerErrors;
        return self::$instance;
    }

    /**
     * Initiates error handling.
     * @return void
     */
    public function run()
    {
        if (true === env('APP_ENABLE_EXCEPTION_HANDLER')) {
            if (Axm::is_cli()) {
                return $this->getCLIHandler();
            }

            return $this->getHandler();
        }
    }

    /**
     * Gets the error handler according to the configuration.
     * @return void
     */
    protected function getHandler()
    {
        $handler = env('APP_ERROR_HANDLER');
        match (strtolower($handler)) {
            'whoops' => $this->buildWhoopsHandler(new PrettyPageHandler, new Run),
            default  => $this->createHandler($handler),
        };
    }

    /**
     * Gets the error handler for the CLI according to configuration.
     * @return void
     */
    protected function getCLIHandler()
    {
        $handlerCLI = env('APP_ERROR_HANDLER_CLI');
        if (strtolower($handlerCLI) == 'axmexceptioncli') {
            $this->displayCliErrors();
            return;
        }

        return $this->handlerCLI($handlerCLI);
    }

    /**
     * Set up error handling for CLI mode.
     *
     * This method sets the exception handler, error handler, and shutdown handler
     * to handle errors and exceptions in a CLI (Command Line Interface) environment.
     * It ensures that errors and exceptions are properly managed and logged.
     */
    public function displayCliErrors()
    {
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    /**
     * Custom exception handler method.
     *
     * This method is responsible for handling exceptions. It logs the exception details,
     * including the message and trace, and then exits the script with a non-zero status code.
     * @param \Throwable $e The exception to be handled.
     */
    public function exceptionHandler(\Throwable $e)
    {
        $log = config('app.initLogReportings');

        if ($log === true) {
            error_log((string) $this->theme($e), 3, $this->getDirectory());
        }

        CLIException::handleCLIException($e);
        exit(1);
    }

    /**
     * theme
     *
     * @param  mixed $e
     * @return void
     */
    public function theme(\Throwable $e): string
    {
        $title = '##Error: ' . $e->getMessage();
        $t_trace = "\ntrace: \n\n";
        $trace = $e->getTraceAsString();

        $date = date('d-m-Y H:i:s');
        $axm_v = Axm::version() ?? '';
        $php_v = PHP_VERSION;
        $info = sprintf('Date: %s    Axm Framework version: %s    PHP version: %s', $date, $axm_v, $php_v);
        $output = $title
            . $t_trace
            . $trace . PHP_EOL
            . $info  . PHP_EOL
            . str_repeat('••', strlen($info)) . "\n\n\n";

        return $output;
    }

    /**
     * Get the directory path for the error log file.
     *
     * This method constructs and returns the full directory path for the error log file.
     * @return string The full directory path for the error log file.
     */
    public function getDirectory()
    {
        // Construct the full directory path for the error log file.
        $dir = config('paths.logsPath') . DIRECTORY_SEPARATOR . 'axm-errors.log';

        // Return the directory path.
        return $dir;
    }

    /**
     * Custom error handler method.
     *
     * @param int         $severity The severity level of the error.
     * @param string      $message  The error message.
     * @param string|null $file     The filename where the error occurred (optional).
     * @param int|null    $line     The line number where the error occurred (optional).
     * @throws ErrorException Throws an ErrorException for the specified error.
     */
    public function errorHandler(int $severity, string $message, ?string $file = null, ?int $line = null)
    {
        // Check if the error should be reported based on error reporting settings.
        if (!(error_reporting() & $severity)) {
            return;
        }

        // Throw an ErrorException for the specified error.
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Custom shutdown handler method.
     *
     * This method is called automatically when PHP execution is about to terminate.
     * It captures the last error that occurred during the script execution and,
     * if it's a fatal error (E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE),
     * it invokes the exceptionHandler method to handle the error as an exception.
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        if ($error === null) return;

        ['type' => $type, 'message' => $message, 'file' => $file, 'line' => $line] = $error;

        if (in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $this->exceptionHandler(new ErrorException($message, $type, 0, $file, $line));
        }
    }

    /**
     * Builds the Whoops handler.
     *
     * @param PrettyPageHandler $handler
     * @param Run $run
     * @return void
     */
    protected function buildWhoopsHandler(PrettyPageHandler $handler, Run $run)
    {
        $handler->setEditor(env('DEBUGBAR_EDITOR'));
        $run->pushHandler($handler);
        $run->register();
    }

    /**
     * Creates the error handler according to the provided name.
     *
     * @param string $handler
     * @return object
     */
    protected function createHandler(string $handler)
    {
        return new $handler;
    }

    /**
     * @param string $handlerCLI
     * @return new $handlerCLI
     */
    protected function handlerCLI(string $handler)
    {
        return new $handler;
    }
}
