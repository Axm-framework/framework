<?php

namespace Axm\Exception;

use Axm;
use Throwable;
use Axm\Console\CLI;

class AxmCLIException
{
    /**
     * Handle a CLI exception.
     *
     * @param Throwable $e The exception to handle.
     */
    public static function handleCLIException(Throwable $e): void
    {
        self::printExceptionInfo($e);

        if (!Axm::isProduction()) {
            self::printBacktrace($e->getTrace());
        }

        exit(1);
    }

    /**
     * Print exception information (type, message, file, line).
     *
     * @param Throwable $e The exception to print.
     */
    private static function printExceptionInfo(Throwable $e): void
    {
        $title = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();

        static::showHeaderBox($title, $message);
        CLI::newLine();
        CLI::write("at " . CLI::color("$file:$line", 'green'));
        CLI::newLine();
    }


    private static function showHeaderBox(string $title, $message)
    {
        // Ancho de la ventana
        $windowWidth = CLI::getWidth() - 4;

        // Construye la parte superior de la ventana
        $top = str_repeat('-', $windowWidth);
        CLI::write("+" . $top . "+");

        // Calcula la cantidad de espacios en blanco para centrar el título
        $titlePadding = str_repeat(' ', ($windowWidth - strlen($title)) / 2);
        CLI::write("|" . $titlePadding . $title . $titlePadding . "|", 'light_red');

        // Muestra el mensaje
        CLI::write("|" . str_pad(" Message: $message ", $windowWidth, ' ') . "|", 'light_red');

        // Construye la parte inferior de la ventana
        CLI::write("+" . $top . "+");
    }


    /**
     * Print the backtrace of the exception.
     *
     * @param array $backtrace The backtrace to print.
     */
    private static function printBacktrace(array $backtrace): void
    {
        if (!empty($backtrace)) {
            CLI::write('Backtrace:', 'blue');
        }

        foreach ($backtrace as $i => $error) {
            self::printBacktraceEntry($i, $error);
        }
    }

    /**
     * Print a single backtrace entry (file, function, and arguments).
     *
     * @param int $i The entry index.
     * @param array $error The backtrace entry to print.
     */
    private static function printBacktraceEntry(int $i, array $error): void
    {
        CLI::newLine();
        $c = str_pad($i + 1, 3, ' ', STR_PAD_LEFT);
        $file = $error['file'] ?? '[internal function]';
        $filepath = $file . ':' . ($error['line'] ?? '');

        CLI::write("$c    " . CLI::color($filepath, 'yellow'));

        $function = self::formatFunctionInfo($error);
        CLI::write($function);

        CLI::write(str_repeat('-', CLI::getWidth() - 4), 'green');

        CLI::newLine();
    }

    /**
     * Format the information for a function call.
     *
     * @param array $error The backtrace entry for the function.
     * @return string The formatted function information.
     */
    private static function formatFunctionInfo(array $error): string
    {
        $function = '';

        if (isset($error['class'])) {
            $type = ($error['type'] === '->') ? '()' . $error['type'] : $error['type'];
            $function .= '       ' . $error['class'] . $type . $error['function'];
        } elseif (!isset($error['class']) && isset($error['function'])) {
            $function .= '       ' . $error['function'];
        }

        if (isset($error['args'])) {
            $args = array_map(function ($arg) {
                return self::formatArgument($arg);
            }, $error['args']);

            $function .= '(' . implode(', ', $args) . ')';
        } else {
            $function .= '()';
        }

        return $function;
    }

    /**
     * Format an argument for function call display.
     *
     * @param mixed $arg The argument to format.
     * @return string The formatted argument.
     */
    private static function formatArgument($arg): string
    {
        if (is_object($arg)) {
            return 'Object(' . get_class($arg) . ')';
        }

        if (is_array($arg)) {
            return count($arg) ? self::formatArray($arg) : '[]';
        }

        if (is_string($arg)) {
            return "'" . $arg . "'";
        }

        if (is_bool($arg)) {
            return $arg ? 'true' : 'false';
        }

        if (is_null($arg)) {
            return 'null';
        }

        // For other types, you can convert them to a string representation.
        return (string) $arg;
    }


    /**
     * Format an array for display.
     *
     * @param array $array The array to format.
     * @return string The formatted array.
     */
    private static function formatArray(array $array): string
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($key)) {
                $key = self::formatArray($key);
            }

            $keyValue = $key . '=>' . (is_object($value) ? get_class($value) : $value);
            $result[] = $keyValue;
        }

        return '[' . implode(", ", $result) . ']';
    }
}
