<?php

declare(strict_types=1);

namespace Console;

use Throwable;

/**
 * CLIException 
 * 
 * This class shows in a nice and detailed way the error in the console, 
 * this code has been inspired by the Laravel Colision library. 
 */
class CLIException
{
    private const DELIMITER = '|';
    private const DELIMITER_UTF8 = '▕';
    private const ARROW_SYMBOL_UTF8 = '➜ ';
    private const ARROW_SYMBOL = '>';
    protected static int $maxLinesToDisplay = 10;


    /**
     * Handles an exception and displays relevant information in the console.
     * @param Throwable $e The exception to handle.
     */
    public static function handleCLIException(Throwable $e): void
    {
        self::printExceptionInfo($e);
        self::snipCode($e);
        self::printBacktrace($e->getTrace());
    }

    /**
     * info
     *
     * @param  string $key
     * @return void
     */
    public static function info(string $key)
    {
        $data = [
            'protocolVersion' => isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '',
            'statusCode' => http_response_code(),
            'reasonPhrase' => isset($_SERVER['HTTP_REASON_PHRASE']) ? $_SERVER['HTTP_REASON_PHRASE'] : ''
        ];

        return $data[$key] ?? null;
    }

    /**
     * Prints information about the exception.
     * @param Throwable $e The exception.
     */
    protected static function printExceptionInfo(Throwable $e): void
    {
        $filepath = str_replace(ROOT_PATH, '', $e->getFile()) ?? '[internal function]';

        $errorLocation = ['file' => $filepath, 'line' => $e->getLine()];
        self::displayHeaderBox(get_class($e), $e->getMessage());
        CLI::write('at ' . CLI::color("$errorLocation[file]:$errorLocation[line]", 'green'));
    }

    /**
     * Displays a formatted header box with a title and message.
     *
     * @param string $title The title of the header box.
     * @param string $message The message to be displayed in the header box.
     * @return void
     */
    protected static function displayHeaderBox(string $title, string $message): void
    {
        CLI::newLine();
        CLI::write(sprintf('[  %s  ]', $title), 'light_gray', 'red');
        CLI::newLine();
        CLI::write(self::ARROW_SYMBOL_UTF8 . $message, 'white');
        CLI::newLine();
    }

    /**
     * Displays a snippet of relevant code for the exception.
     * @param Throwable $e The exception.
     */
    public static function snipCode(Throwable $e)
    {
        $code = self::getCode($e);
        CLI::write($code);
    }

    /**
     * Gets the relevant source code for the exception.
     *
     * @param Throwable $e The exception.
     * @return string The source code.
     */
    public static function getCode(Throwable $e): string
    {
        $code = self::renderSourceCode($e->getFile(), $e->getLine(), self::$maxLinesToDisplay);
        return $code;
    }

    /**
     * Gets the color associated with a PHP token ID.
     *
     * @param int $tokenId The PHP token ID.
     * @return string The color associated with the token ID.
     */
    protected static function getTokenColor(int $tokenId): string
    {
        $tokenColors = [
            T_OPEN_TAG => 'blue',
            T_OPEN_TAG_WITH_ECHO => 'light_yellow',
            T_CLOSE_TAG => 'blue',
            T_STRING => 'blue',
            T_VARIABLE => 'light_cyan',
            // Constants
            T_DIR => 'light_cyan',
            T_FILE => 'default',
            T_METHOD_C => 'light_yellow',
            T_DNUMBER => 'default',
            T_LNUMBER => 'default',
            T_NS_C => 'default',
            T_LINE => 'default',
            T_CLASS_C => 'light_cyan',
            T_FUNC_C => 'light_yellow',
            T_TRAIT_C => 'light_cyan',
            // Comment
            T_COMMENT => 'light_green',
            T_DOC_COMMENT => 'dark_gray',

            T_ENCAPSED_AND_WHITESPACE => 'light_red',
            T_CONSTANT_ENCAPSED_STRING => 'light_red',

            T_INLINE_HTML => 'blue',
            //
            T_FOREACH => 'light_purple',
            T_FOR => 'light_purple',
            T_WHILE => 'light_purple',
            T_DO => 'light_purple',
            T_CASE => 'light_purple',
            T_TRY => 'light_purple',
            T_ENUM => 'light_purple',
            T_EXIT => 'light_purple',
            T_BREAK => 'light_purple',
            T_THROW => 'light_purple',
            T_SWITCH => 'light_purple',
            T_IF => 'light_purple',
            T_RETURN => 'light_purple',
            T_CONTINUE => 'light_purple',
            T_YIELD => 'light_purple',
            T_ENDSWITCH => 'light_purple',
            T_ENDIF => 'light_purple',
            T_ENDFOR => 'light_purple',
            T_ENDFOREACH => 'light_purple',
            T_ENDWHILE => 'light_purple',
            T_THROW => 'light_purple',
            //
            T_DOLLAR_OPEN_CURLY_BRACES => 'light_purple',

            T_START_HEREDOC => 'light_cyan',
            T_END_HEREDOC => 'light_cyan',
            //
            T_FUNCTION => 'light_cyan',
            T_PRIVATE => 'light_cyan',
            T_PROTECTED => 'light_cyan',
            T_PUBLIC => 'light_cyan',

            T_NEW => 'blue',
            T_CLONE => 'blue',
            T_NAMESPACE => 'blue',
            T_INTERFACE => 'blue',
        ];

        return $tokenColors[$tokenId] ?? 'white';
    }

    /**
     * Renders the relevant source code.
     *
     * @param string $file The file name.
     * @param int $errorLine The line number with the error.
     * @param int $maxLines The maximum number of lines to display.
     * @return string The formatted source code.
     */
    protected static function renderSourceCode(string $file, int $errorLine, int $maxLines): string
    {
        --$errorLine; // Adjust line number to 0-based from 1-based
        $lines = @file($file);

        if ($lines === false || count($lines) <= $errorLine) {
            return '';
        }

        $lineRange = self::calculateLineRange($errorLine, count($lines), $maxLines);
        $highlightedLines = self::highlightLines($lines, $lineRange, $errorLine);

        return $highlightedLines;
    }

    /**
     * Calculates the line range to display.
     *
     * @param int $errorLine Error line number.
     * @param int $lineCount Total number of lines.
     * @param int $maxLines Maximum number of lines to display.
     * @return array Line range to display (start and end).
     */
    protected static function calculateLineRange(int $errorLine, int $lineCount, int $maxLines): array
    {
        $halfLines = (int) ($maxLines / 2);
        $beginLine = max($errorLine - $halfLines, 0);
        $endLine   = min($beginLine + $maxLines - 1, $lineCount - 1);
        return [
            'start' => $beginLine,
            'end'   => $endLine
        ];
    }

    /**
     * Highlights the relevant lines of the source code.
     *
     * @param array $lines Array of code lines.
     * @param array $lineRange Line range to display.
     * @param int $errorLine Error line number.
     * @return array Array of highlighted lines.
     */
    protected static function highlightLines($lines, $lineRange, $errorLine)
    {
        $highlightedLines = [];

        foreach ($lines as $index => $line) {
            if ($index < $lineRange['start'] || $index > $lineRange['end']) {
                continue;
            }

            // Delete newline characters
            $cleanLine = str_replace(["\r\n", "\r", "\n"], '', $line);

            // Preparing the code and highlighting the syntax
            $preparedCode = (string) self::prepareCode([$cleanLine]);
            $highlightedCode = self::highlightSyntax($preparedCode);

            // Add line numbers and handle error if needed
            $formattedCode = self::addLines(explode("\n", trim($highlightedCode)), $index, $errorLine);

            $highlightedLines[] = self::clearCodeOutput($formattedCode);
        }

        return implode('', $highlightedLines);
    }


    /**
     * Prepares the code for rendering by adding a PHP opening tag.
     *
     * @param array $code An array of code lines.
     * @return string The prepared code with a PHP opening tag.
     */
    protected static function prepareCode(array $code): string
    {
        $stringOut = implode("\n", $code);
        $output = "<?php$stringOut";
        return $output;
    }

    /**
     * Highlights the syntax of the provided code using ANSI colors.
     *
     * @param string $code The code to be highlighted.
     * @return string The highlighted code with ANSI color codes.
     */
    protected static function highlightSyntax(string $code): string
    {
        $highlightedCode = '';

        $tokens = token_get_all($code);
        foreach ($tokens as $token) {
            $text = is_array($token) ? CLI::color($token[1], self::getTokenColor($token[0])) : $token;
            $highlightedCode .= $text;
        }

        return $highlightedCode;
    }

    /**
     * Formats and adds highlighted lines to create the final output.
     *
     * @param array $highlightedLines An array of highlighted code lines.
     * @param int $lineBegin The starting line number.
     * @param int $errorLine The line number with the error.
     * @return string The formatted output with line numbers and ANSI colors.
     */
    protected static function addLines(array $highlightedLines, int $lineBegin, int $errorLine): string
    {
        $outputLines = [];
        foreach ($highlightedLines as $i => $lineI) {
            $lineNumber = $i + $lineBegin + 1;
            $isErrorLine = $lineNumber - 1 === $errorLine;

            $linePrefix = ($isErrorLine ? CLI::color(self::ARROW_SYMBOL . ' ', 'red') : '  ')
                . CLI::color($lineNumber . self::DELIMITER, 'dark_gray');

            // Use sprintf for better readability
            $formattedLine = sprintf('%s%s', $linePrefix, ($lineI === '') ? $lineI : $lineI . PHP_EOL);
            $outputLines[] = $formattedLine;
        }

        return implode('', $outputLines);
    }

    /**
     * Clears unnecessary code elements from the provided code.
     *
     * Removes PHP opening tags and other unwanted code elements.
     * @param string $code The code to be cleaned.
     * @return string The code with unnecessary elements removed.
     */
    protected static function clearCodeOutput(string $code): string
    {
        $output = str_replace(['<?php', '<?', '?>', '<%', '%>'], '', $code);
        return $output;
    }

    /**
     * Prints a formatted backtrace for display.
     *
     * @param array $backtrace An array containing the backtrace information.
     * @return void
     */
    protected static function printBacktrace(array $backtrace): void
    {
        if (!empty($backtrace)) {
            CLI::write('Backtrace:', 'blue');
        }

        foreach ($backtrace as $i => $error) {
            self::printStackTraceEntryInfo($i, $error);
        }
    }

    /**
     * Prints a formatted backtrace entry for display.
     *
     * @param int $i The index of the backtrace entry.
     * @param array $error An array containing information about the backtrace entry.
     * @return void
     */
    protected static function printStackTraceEntryInfo(int $i, array $error): void
    {
        $c = str_pad(strval($i + 1), 3, ' ', STR_PAD_LEFT);

        $filepath = str_replace(ROOT_PATH, '', $error['file'] ?? '[internal function]');
        $line = $error['line'] ?? 'unknown';
        CLI::write($c . self::DELIMITER_UTF8 . ' ' . CLI::color("$filepath:{$line}", 'white'), 'dark_gray');
        CLI::write('   ' . self::DELIMITER_UTF8 . ' ' . CLI::color(self::formatCallableInfo($error), 'dark_gray'), 'dark_gray');
        CLI::write(str_repeat('-', CLI::getWidth() - 4), 'dark_gray');
    }

    /**
     * Formats information about a function or method for display.
     *
     * @param array $error An array containing information about the error.
     * @return string The formatted string representation of the function or method information.
     */
    protected static function formatCallableInfo(array $error): string
    {
        $function  = $error['class'] ?? '';
        $function .= $error['type'] ?? '';
        $function .= $error['function'] ?? '';

        if (isset($error['args'])) {
            $args = array_map(fn ($arg) => self::formatArgument($arg), $error['args']);
            $function .= '(' . implode(', ', $args) . ')';
        } else {
            $function .= '()';
        }

        return $function;
    }

    /**
     * Formats a function argument for display.
     *
     * @param mixed $arg The argument to be formatted.
     * @return string The formatted string representation of the argument.
     */
    protected static function formatArgument($arg): string
    {
        return match (true) {
            is_object($arg)  =>  'Object(' . get_class($arg) . ')',
            is_array($arg)   =>   count($arg) ? self::formatArray($arg) : '[]',
            is_string($arg)  =>   "'" . $arg . "'",
            is_bool($arg)    =>   $arg ? 'true' : 'false',
            is_null($arg)    =>   'null',

            default => (string) $arg,
        };
    }

    /**
     * Formats an associative array for display.
     *
     * @param array $array The associative array to be formatted.
     * @return string The formatted string representation of the array.
     */
    protected static function formatArray(array $array): string
    {
        $result = [];
        foreach ($array as $key => $value) {
            $keyValue = self::formatArgument($key) . '=>' . self::formatArgument($value);
            $result[] = $keyValue;
        }

        return '[' . implode(', ', $result) . ']';
    }
}
