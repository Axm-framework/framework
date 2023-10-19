<?php

namespace Axm\Views;

use Axm;
use Axm\Cache\Cache;
use Axm\Exception\AxmException;
use Axm\Debug\Debug;
use Axm\Raxm\RaxmManager;

/**
 * Class View
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package View
 */

class View
{
    private static $data = [];

    /**
     * Merge savedData and userData
     */
    public static $tempData = [];

    /**
     * The base directory to look in for our Views.
     *
     * @var string
     */
    public static $viewPath = APP_PATH . '/Views/';

    /**
     * The base directory to look in for our Views.
     *
     * @var string
     */
    public static $layoutPath = APP_PATH . '/Views/layouts/';

    /**
     * The render variables
     *
     * @var array
     */
    protected static $dataVars = [];

    /**
     */
    public static array $fileDirCache;

    /**
     */
    public static bool $viewSaveCache = false;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    public static $logger;

    /**
     * Should we store performance info?
     *
     * @var bool
     */
    public static $debug = false;

    /**
     * Especifica si se van agregar a la vista los assets de Raxm
     */
    public static $raxmAssets = false;

    /**
     * Cache stats about our performance here,
     * when DEBUG = true
     *
     * @var array
     */
    public static $performanceData = [];

    /**
     * @var ViewConfig
     */
    public static $config;

    /**
     * Whether data should be saved between renders.
     *
     * @var bool
     */
    public static $saveData;

    /**
     * Number of loaded views
     *
     * @var int
     */
    public static $viewsCount = 0;

    /**
     * The name of the layout being used, if any.
     * Set by the `extend` method used within views.
     *
     * @var string|null
     */
    public static $layout;


    /**
     * 
     */
    public static $nameLayoutByDefault = 'main';

    /**
     * the name of the view used
     */
    protected static $name;

    /**
     * 
     */
    public static bool $addLayout = true;


    /**
     * Holds the sections and their data.
     *
     * @var array
     */
    public static $sections = [];

    /**
     * The name of the current section being rendered,
     * if any.
     *
     * @var string|null
     *
     * @deprecated
     */
    public static $currentSection;

    /**
     * The name of the current section being rendered,
     * if any.
     *
     * @var array<string>
     */
    public static $sectionStack = [];

    protected static $_cache = ['type' => 'view', 'time' => false, 'group' => false];

    private static $instance = null;

    /**
     * Get instance
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 
     **/
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }


    /**
     * Returns the current data that will be displayed in the view.
     */
    public static function getData(): array
    {
        return self::$tempData ?? self::$data;
    }


    public static function render(string $view, ?array $data = null, bool $buffer = true, string $ext = '.php'): ?string
    {
        $start = hrtime(true);
        $view  = str_replace('.', '/', trim($view)) . $ext;

        //si esta guardado en cache
        if (($output = static::getCache($view, $data)) !== false) {

            $end = hrtime(true) - $start;
            static::logPerformance($start, $end, $view, 20);

            return (string) $output;
        }

        $file = static::$viewPath . $view;
        $file = is_file($file) ? $file : $view;
        if (!is_file($file)) {
            throw new AxmException(Axm::t('axm', 'El archivo vista "%s" no existe.', [$file]));
        }

        static::$name = $file;

        $mergedData = array_merge($data ?? [], self::$tempData);

        $output  = self::captureFile($file, $mergedData, $buffer);
        $content = Axm::isProduction()
            ? $output
            : "<!--VIEW START . $view -->\n $output \n<!-- VIEW END $view -->\n";

        //si tiene una layout
        if (null !== static::$layout && static::$sectionStack === []) {
            ob_start();
            require static::$layoutPath . static::$layout . '.php';
            static::$layout = null;

            $output = (string) ob_get_clean();
        }

        #for Debugbar assets
        if (false !== static::$debug) {
            $output = (new Debug)->injectAssets($output);
        }

        #for Raxm assets
        if (false !== static::$raxmAssets) {
            $output = RaxmManager::injectAssets($output);
        }

        $end = hrtime(true) - $start;
        static::logPerformance($start, $end, $view);

        static::saveCache($view, $output, $data);

        return $output;
    }


    /**
     * 
     */
    public function layout(string $layout = null): string
    {
        return static::$layout = $layout ?? static::$nameLayoutByDefault;
    }

    /**
     * 
     */
    public static function getView(string $view, ?array $data = null, bool $buffer = true, string $ext = '.php')
    {
        $path = trim(str_replace('.', '/', self::$viewPath . $view) . $ext);
        if (!is_file($path))
            throw new AxmException(Axm::t('axm', 'El archivo "%s" no existe.', [$path]));

        $output = self::captureFile($path,  array_merge($data ?? [], self::$tempData), $buffer);
        return $output;
    }

    /**
     * Obtiene la cache de view.
     *
     * @return bool
     */
    protected static function getCache(string $view, $data)
    {
        if (static::$viewSaveCache === false) {
            return false;
        }

        if (isset($data['cache']) && $data['cache'] !== true) {
            return false;
        }

        // Crea una instancia de la clase FileCache
        $cache = Cache::driver()->get($view);
        return $cache;
    }

    /**
     * guarda en la cache la view.
     *
     * @return bool
     */
    protected static function saveCache(string $view, string $output, $data)
    {
        if (static::$viewSaveCache === false) {
            return false;
        }

        if (isset($data['cache']) && $data['cache'] !== true) {
            return;
        }

        // Crea una instancia de la clase FileCache
        $cache = Cache::driver()->set($view, $output);
        return $cache;
    }

    /**
     * Captura un archivo en el buffer o lo incluye
     * 
     * @param string $file
     * @param array $data
     * @param bool $buffer 
     */
    public static function captureFile(string $file, array $data = [], bool $buffer = true): string
    {
        extract($data);

        if ($buffer) {
            ob_start();
            ob_implicit_flush(false);
            require $file;

            return (string) ob_get_clean();
        }

        return (string) require $file;
    }


    /**
     * Registra la vista.
     * para collertor de vista del debug
     */
    public static function registerView(string $file)
    {
        return Axm::app()->views[] = $file;
    }

    /** */
    public static function getName(): string
    {
        return static::separateNameDirectoryAndExtension()['name'];
    }

    /** */
    public static function getExt(): string
    {
        return static::separateNameDirectoryAndExtension()['ext'];
    }

    /** */
    public static function getPath(): string
    {
        return static::separateNameDirectoryAndExtension()['path'];
    }

    /**
     * Specifies that the current view should extend an existing layout.
     */
    public static function setPath(string $path)
    {
        return static::$viewPath = $path;
    }

    /**
     * 
     */
    private static function separateNameDirectoryAndExtension()
    {
        $nameExtension = basename(static::$name);                 // Gets the name of the file with the extension
        $name = pathinfo($nameExtension, PATHINFO_FILENAME);     // Gets the name of the file without the extension
        $ext  = pathinfo($nameExtension, PATHINFO_EXTENSION);   // Gets the extension of the file
        $path = dirname(static::$name);                        // Gets the directory where the file is located

        return [
            'path' => $path,
            'name' => $name,
            'ext'  => $ext
        ];
    }


    public static function prepareTemplateData(bool $saveData): void
    {
        self::$tempData = self::$tempData ?? self::$data;

        if ($saveData) {
            self::$data = self::$tempData;
        }
    }


    /**
     * Specifies that the current view should extend an existing layout.
     */
    public static function extend(string $layout)
    {
        self::$layout = $layout;
    }


    /**
     * Starts holds content for a section within the layout.
     *
     * @param string $name Section name
     */
    public static function section(string $name)
    {
        // Save $name to static variables.
        static::$currentSection = $name;
        static::$sectionStack[] = $name;

        // Start output buffering.
        ob_start();
    }


    /**
     * Captures the last section
     *
     * @throws AxmException
     */
    public static function endSection()
    {
        $contents = ob_get_clean();

        if (static::$sectionStack === []) {
            throw new AxmException('View themes, no current section.');
        }

        $section = array_pop(static::$sectionStack);

        // Ensure an array exists so we can store multiple entries for this.
        if (!array_key_exists($section, static::$sections)) {
            static::$sections[$section] = [];
        }

        static::$sections[$section][] = $contents;
    }


    /**
     * Renders a section's contents.
     */
    public static function renderSection(string $sectionName)
    {
        if (!isset(static::$sections[$sectionName])) {
            echo '';
            return;
        }

        foreach (static::$sections[$sectionName] as $key => $contents) {
            echo $contents;
            unset(static::$sections[$sectionName][$key]);
        }
    }


    /**
     * Logs performance data for rendering a view.
     */
    protected static function logPerformance(float $start, float $end, string $view)
    {
        if (static::$debug) {
            static::$performanceData[] = [
                'start' => $start,
                'end'   => $end,
                'view'  => $view,
            ];
        }
    }


    /**
     * Returns the performance data that might have been collected
     * during the execution. Used primarily in the Debug Toolbar.
     */
    public static function getPerformanceData(): array
    {
        return static::$performanceData;
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


    /**
     * cleanBuffer
     * termina los buffers abiertos.
     */
    private static function endBuffer()
    {
        if (ob_get_length() > 0)
            ob_end_flush();

        return;
    }


    public function __toString()
    {
        return self::getInstance();
    }
}
