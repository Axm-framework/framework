<?php

namespace Axm\Exception;

use Axm;
use Axm\Services\OpenAIChatbot;

/**
 * AxmPHP web & app Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category   Axm
 *
 * @copyright  Copyright (c) 2005 - 2020 AxmPHP Team (http://www.Axmphp.com)
 * @license    https://github.com/AxmPHP/AxmPHP/blob/master/LICENSE   New BSD License
 */

/**
 * Clase principal para el manejo de excepciones.
 *
 * @category   Axm
 */
class AxmException extends \Exception
{
    /**
     * View de error de la Excepción.
     *
     * @var string|null
     */
    protected static $data;

    /**
     * Error 404 para los siguientes views.
     *
     * @var array
     */
    protected static $view404 = ['no_controller', 'no_action', 'num_params', 'no_view', 'no_route'];


    /** Cantidad de linea de codigo a mostrar  
     * @var int
     */
    protected static $maxSourceLines = 15;

    /** Cantida de linea de codigo de rastro a mostrar
     * @var int
     */
    protected static $maxTraceSourceLines = 5;


    /** Cantidad de linea de codigo de los argumentos en el 
     * rastro file dentro de los paréntesis
     * @var int
     */
    protected static $maxStrlengTraceFile = 200;

    public static bool $IA = false;

    /**
     * Constructor de la clase;.
     *
     * @param string $message mensaje
     * @param string $view    vista que se mostrara
     */
    public function __construct($message, $data = null)
    {
        self::$data = $data;
        parent::__construct($message);
    }

    /**
     * Handles uncaught exceptions.
     *
     * @param \Throwable $e The uncaught exception.
     */
    public static function handleException(\Throwable $e)
    {
        self::cleanBuffer();
        self::endBuffer();

        if (!Axm::isProduction()) {
            $editor = env('DEBUGBAR_EDITOR'); // Change this to your preferred code editor
            $message = $e->getMessage() ?? '(null)';
            $file = $e->getFile();
            $line = $e->getLine();

            $data = [
                'type' => get_class($e),
                'code' => self::setHeader($e),
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'traces' => $e->getTrace(),
                'linkEditor' => self::generateEditorLink($editor, $file, $line),
                'urlGoogle'  => self::googleSearchUrl($message, $file, $line),
            ];

            // $data['bot'] = self::chatGpt($data);
            $output = static::render(AXM_PATH . '/exception/src/views/exception', $data);
            return Axm::app()->response->send($output, 500);
        }
    }


    private static function convertToString($data, string $unset = 'traces'): string
    {
        // Si el argumento es un arreglo, lo recorremos y llamamos de nuevo a esta función para sus valores.
        if (is_array($data)) {

            unset($data[$unset]);  // Eliminamos la clave "traces" del arreglo

            $result = [];
            foreach ($data as $key => $value) {
                $result[] = self::convertToString($value);
            }

            return implode(', ', $result);
        }
        // Si el argumento es un objeto, lo codificamos como JSON
        elseif (is_object($data)) {
            return json_encode($data);
        }
        // En cualquier otro caso, convertimos el argumento a una cadena
        else {
            return strval($data);
        }
    }

    /**
     * Generate a link to open a file in a code editor.
     *
     * @param string $editor The name of the code editor (e.g., 'vscode', 'sublime', 'atom', 'phpstorm').
     * @param string $file The path to the file to be opened.
     * @param int $line The line number to navigate to within the file.
     * @return string The HTML link to open the file in the specified code editor, or an error message if the editor is not supported.
     */
    protected static function generateEditorLink($editor, $file, $line)
    {
        // Define supported code editors and their URL schemes.
        $supportedEditors = [
            'vscode'   => 'vscode://file/',
            'sublime'  => 'sublime://open?url=file://',
            'atom'     => 'atom://core/open/file?filename=',
            'phpstorm' => 'phpstorm://open?file=',
            'intellij' => 'idea://open?file=',
            'vim'      => 'vim://open?file=',
            'netbeans' => 'netbeans://open?file=',
            'aptana '  => 'aptana://open?file=',
            'zend'     => 'zendstudio://open?file=',
            'eclipse'  => 'eclipse-pdt://open?file=',
            'idx'      => 'idx://open?file=',
        ];

        // Check if the specified editor is supported.
        if (isset($supportedEditors[$editor])) {
            $urlBase = $supportedEditors[$editor];
            $url = $urlBase . urlencode($file) . ':' . $line;

            // Generate and return the HTML link.
            return sprintf('<a href="%s">%s</a>', htmlspecialchars($url), htmlspecialchars($file));
        } else {
            // If the editor is not supported, provide an error message.
            return 'Unsupported editor';
        }
    }

    /**
     * Generates a Google search URL based on the provided message, file, and line.
     *
     * @param string $message The error or message to be searched on Google.
     * @param string $file The file where the error occurred.
     * @param int $line The line number in the file where the error occurred.
     * @return string The URL for a Google search with the specified query.
     */
    protected static function googleSearchUrl($message, $file, $line)
    {
        // Build the search query
        $searchQuery = '/Axm Framework: ' . strip_tags($message) . ' file: ' . $file . ' line: ' . $line;

        // URL-encode the search query to make it safe for a URL
        $searchQueryUrl = urlencode($searchQuery);

        // Google search URL
        $googleSearchUrl = 'https://www.google.com/search?q=' . $searchQueryUrl;

        return $googleSearchUrl;
    }



    public static function chatGpt($data = null)
    {
        if (!self::$IA) {
            return;
        }

        $config = Axm::app()->config()->load(APP_PATH . '/Config/ChatGPT.php');

        $response = null;
        if (OpenAIChatbot::$activate) {
            $prompt   = self::convertToString($data);
            $chatbot  = OpenAIChatbot::getInstance($config);
            $response = $chatbot->getResponse($prompt);

            return $response;
        }
    }


    public static function throwDisplay(\Throwable $e): void
    {
        echo '<h1>' . get_class($e) . ': ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')' . "</h1>\n";
        echo '<h6>' . $e->getMessage() . '</h6>';

        if (!Axm::isProduction()) {
            echo '<h6>(' . $e->getFile() . ': ' . $e->getLine() . ')</h6>';
            echo '<h6>' . $e->getTraceAsString() . '</h6>';
        }
    }


    /**
     * Renderisa la vista
     */
    public static function render(string $path, array $data = [])
    {
        extract($data);

        ob_start();
        require "$path.php";
        return ob_get_clean();
    }


    /**
     * cleanBuffer
     * termina los buffers abiertos.
     */
    private static function cleanBuffer()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }


    private static function endBuffer()
    {
        // Verifica si hay template lo limpia
        if (ob_get_length() > 0)
            ob_end_flush();
    }


    /**
     * Añade la cabezera de error http.
     *
     * @param Exception $e
     * */
    private static function setHeader(\Throwable $e)
    {
        if ($e instanceof Axm) {
            Axm::app()->registerEvent('afterRequest', Axm::app()->request->setHeader('X-Axm', 'true'));
            return http_response_code(404);
        }

        return http_response_code(500);
    }


    /**
     * Renders the source code by highlighting the error line and displaying a range of lines.
     *
     * @param string $file Source code file.
     * @param int $errorLine Error line number.
     * @param int $maxLines (optional) Maximum number of lines to display. Default value: 10.
     * @return string HTML code with highlighted source code.
     */
    protected static function renderSourceCode($file, $errorLine, $maxLines = 10)
    {
        $errorLine--; // Adjust line number to 0-based from 1-based
        if ($errorLine < 0 || ($lines = @file($file)) === false || ($lineCount = count($lines)) <= $errorLine) {
            return '';
        }

        self::configureHighlightColors();

        $lineRange = self::calculateLineRange($errorLine, $lineCount, $maxLines);
        $highlightedLines = self::highlightLines($lines, $lineRange, $errorLine);

        return self::generateHTML($highlightedLines, $lineRange['begin'], $lineRange['end'], $errorLine);
    }

    /**
     * Calculates the line range to display.
     *
     * @param int $errorLine Error line number.
     * @param int $lineCount Total number of lines.
     * @param int $maxLines Maximum number of lines to display.
     * @return array Line range to display (start and end).
     */
    protected static function calculateLineRange($errorLine, $lineCount, $maxLines)
    {
        $halfLines = (int) ($maxLines / 2);
        $beginLine = max($errorLine - $halfLines, 0);
        $endLine   = min($beginLine + $maxLines - 1, $lineCount - 1);

        return [
            'begin' => $beginLine,
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
        foreach ($lines as $i => $line) {
            if ($i < $lineRange['begin'] || $i > $lineRange['end']) {
                continue;
            }

            $highlightedLines[] = static::highlight_code(htmlspecialchars_decode(str_replace(["\r", "\n", "\t"], [''], $line)), $i === $errorLine);
        }

        return $highlightedLines;
    }

    /**
     * Generates the HTML code with the highlighted source code.
     *
     * @param array $highlightedLines Array of highlighted lines.
     * @param int $beginLine Start line.
     * @param int $endLine End line.
     * @param int $errorLine Error line number.
     * @return string HTML code with the highlighted source code.
     */
    protected static function generateHTML($highlightedLines, $beginLine, $endLine, $errorLine)
    {
        $output = '';
        $lineNumberWidth = strlen($endLine + 1);
        foreach ($highlightedLines as $i => $lineI) {
            $lineNumber  = $i + $beginLine + 1;
            $isErrorLine = $lineNumber - 1 === $errorLine;
            $code = sprintf(
                '<span class="ln%s">%0' . $lineNumberWidth . 'd</span> %s',
                ($isErrorLine ? ' error-ln' : ''),
                $lineNumber,
                str_replace(["\n", "\t", "\v"], [''], ($lineI == '') ? $lineI : $lineI . '</br>')
            );

            $output .= $isErrorLine ? '<span class="error">' . $code . '</span>' : $code;
        }

        return '<div class="code"><pre>' . $output . '</pre></div>';
    }


    /**
     * Configures the highlight colors for the source code.
     */
    protected static function configureHighlightColors()
    {
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#008000; font-style: italic');
            ini_set('highlight.html', '#808080');
            ini_set('highlight.keyword', '#9406adc0; font-weight: bold'); // cc66cc
            ini_set('highlight.string', '#DD0000');
        }
    }

    /**
     * Returns a value indicating whether the call stack is from application code.
     * @param array $trace the trace data
     * @return boolean whether the call stack is from application code.
     */
    protected static function isCoreCode($trace): bool
    {
        if (isset($trace['file'])) {
            $systemPath = realpath(dirname(__FILE__) . '/..');
            return $trace['file'] === 'unknown' || strpos(realpath($trace['file']), $systemPath . DIRECTORY_SEPARATOR) === 0;
        }

        return false;
    }


    /**
     * Converts an array of arguments to a string representation.
     *
     * @param array $args Array of arguments.
     * @return string String representation of the arguments.
     */
    protected static function argumentsToString($args)
    {
        $output = '';

        foreach ($args as $key => $value) {
            if (strlen($output) > self::$maxStrlengTraceFile) {
                $output .= '...';
                break;
            }

            $arg = '';

            if (is_object($value)) {
                $arg = get_class($value);
            } elseif (is_bool($value)) {
                $arg = $value ? 'true' : 'false';
            } elseif (is_string($value)) {
                $arg = self::truncateString($value, self::$maxStrlengTraceFile);
            } elseif (is_array($value)) {
                $arg = 'array(' . self::argumentsToString($value) . ')';
            } elseif ($value === null) {
                $arg = 'null';
            } elseif (is_resource($value)) {
                $arg = 'resource';
            }

            if (is_string($key)) {
                $arg = '"' . $key . '" => ' . $arg;
            } elseif (is_int($key)) {
                $arg = $arg;
            }

            $output .= $arg;

            if (next($args)) {
                $output .= ', ';
            }
        }

        return $output;
    }

    /**
     * Truncates a string to a specified length.
     *
     * @param string $string The string to truncate.
     * @param int $length The maximum length of the truncated string.
     * @return string The truncated string.
     */
    protected static function truncateString($string, $length)
    {
        if (strlen($string) > $length) {
            $string = substr($string, 0, $length) . '...';
        }
        return '"' . $string . '"';
    }



    /** The highlight string function encodes and highlights
     * brackets so we need them to start raw.
     *
     * Also replace any existing PHP tags to temporary markers
     * so they don't accidentally break the string out of PHP,
     * and thus, thwart the highlighting.
     */
    protected static function highlight_code(string $str): string
    {
        $search  = ['&lt;', '&gt;', '<?', '?>', '<%', '%>', '\\', '</script>'];
        $replace = ['<', '>', 'phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'];
        $str     = strtr($str, array_combine($search, $replace));

        // The highlight_string function requires that the text be surrounded
        // by PHP tags, which we will remove later
        $str = highlight_string('<?php ' . $str . ' ?>', true);

        $str = preg_replace(
            [
                '/<span style="color: #[A-Z0-9]+">&lt;\?php(?:&nbsp;| )/i',
                '/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is',
                '/<span style="color: #[A-Z0-9]+"><\/span>/i',
            ],
            [
                '<span style="color: #$1">',
                "$1</span>\n</span>\n</code>",
                '',
            ],
            $str
        );

        $search  = ['phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'];
        $replace = ['&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'];
        $str     = strtr($str, array_combine($search, $replace));

        return $str;
    }
}
