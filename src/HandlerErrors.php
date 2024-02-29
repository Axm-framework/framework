<?php

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

// Error handler
set_error_handler(static function ($errno, $errstr, $errfile, $errline) {
    handlerErrors($errno, $errstr, $errfile, $errline);
});

// Exception handler
set_exception_handler(static function (Throwable $e) {
    handlerException($e);
});


function handlerErrors($errno, $errstr, $errfile, $errline)
{
    if (php_sapi_name() === 'cli') {
        handleCliError($errno, $errstr, $errfile, $errline);
    }

    handleWebError($errno, $errstr, $errfile, $errline);
}

function handlerException(Throwable $e)
{
    $log = Config::get('app.initLogReportings');

    if ($log === true) {
        error_log((string) theme($e), 3, getDirectory());
    }

    if (php_sapi_name() === 'cli') {
        handleCliException($e);
    }

    handleWebException($e);
}

/**
 * theme
 */
function theme(\Throwable $e): string
{
    $title = '##Error: ' . $e->getMessage();
    $t_trace = "\ntrace: \n\n";
    $trace = $e->getTraceAsString();

    $date = date('d-m-Y H:i:s');
    $axm_v = 1.0 ?? '';
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
 */
function getDirectory()
{
    // Construct the full directory path for the error log file.
    $dir = Config::get('paths.logsPath') . DIRECTORY_SEPARATOR . 'axm-errors.log';
    return $dir;
}

function handleCliError($errno, $errstr, $errfile, $errline)
{
    $e = new ErrorException($errstr, $errno, 1, $errfile, $errline);
    Console\CLIException::handleCLIException($e);
    
    $log = Config::get('app.initLogReportings');

    if ($log === true) {
        error_log((string) theme($e), 3, getDirectory());
    }
    exit;
}

function handleWebError($errno, $errstr, $errfile, $errline)
{
    $whoops = new Run();
    $handler = new PrettyPageHandler();
    $handler->setPageTitle("¡Oops! Ha ocurrido un error");
    $whoops->pushHandler($handler);
    $whoops->handleError($errno, $errstr, $errfile, $errline);
}

function handleCliException(Throwable $e)
{
    Console\CLIException::handleCLIException($e);
    exit;
}

function handleWebException(Throwable $e)
{
    $whoops = new Run();
    $handler = new PrettyPageHandler();
    $handler->setPageTitle("¡Oops! Ha ocurrido un error");
    $whoops->pushHandler($handler);
    $whoops->handleException($e);
}
