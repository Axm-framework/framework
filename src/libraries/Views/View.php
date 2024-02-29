<?php

declare(strict_types=1);

namespace Views;

use Cache\Cache;
use Debug\Debug;
use RuntimeException;

/**
 * Class View
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package View
 */
class View
{
    protected $view;
    protected $layout = 'main';
    public $viewPath;
    protected $data = [];
    public static $tempData  = [];
    protected bool $viewSaveCache;
    public $layoutPath;
    protected $withLayout = true;
    protected string $filename;
    private string $contents = '';
    private $resolved = false;
    protected static $sections = [];
    protected static $sectionStack = [];


    public function __construct()
    {
        $config = config('paths');
        $this->viewPath = $config['viewsPath'];
        $this->layoutPath = $config['layoutsPath'];
        $this->viewSaveCache = config('view.saveViewCache');
    }

    /**
     * 
     */
    public function render(string $view, string $ext = '.php')
    {
        $view  = str_replace('.', DIRECTORY_SEPARATOR, trim($view)) . $ext;

        $this->filename = $filename = static::handleFile($view);
        $this->contents = $this->file($filename);

        return $this;
    }

    /**
     * Resolves the file path for a given view,
     *
     * @param string $view The name or path of the view file.
     * @return string The resolved file path for the view file.
     * @throws RuntimeException If the resolved view file does not exist.
     */
    public function handleFile(string $file)
    {
        $filename = $this->viewPath . DIRECTORY_SEPARATOR . $file;
        $filename = is_file($filename) ? $filename : $file;
        if (!is_file($filename))
            throw new RuntimeException(sprintf('View file [ %s ] does not exist.', $filename));

        return $filename;
    }

    public function layout(string $layout = 'main')
    {
        $this->withLayout = true;
        $this->layout = $layout;
        return $this;
    }

    public function withData(string|array|null $keyOrArray, $value = null): self
    {
        if (is_array($keyOrArray)) {
            $this->data = array_merge($this->data, $keyOrArray);
        } else {
            $this->data[$keyOrArray] = $value;
        }

        return $this;
    }

    public function withGlobal($key, $value)
    {
        self::$tempData[$key] = $value;
        return $this;
    }

    function withLayout(bool $value = true): self
    {
        $this->withLayout = $value;
        return $this;
    }

    /**
     * Convert a string to a node selector. 
     */
    public function esc(string $string): self
    {
        $this->contents = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return $this;
    }

    public function resolver(): self
    {
        $data = $this->data;
        $view = $this->contents;
        $filename = $this->filename;

        $_content = app()->isProduction()
            ? $view : "<!--VIEW START . $filename -->\n $view \n<!-- VIEW END $filename -->\n\n";

        if ($this->withLayout) {
            $output = $this->file($this->layoutPath . DIRECTORY_SEPARATOR . $this->layout . '.php', ['_content' => $_content]);     // include layout
        } else {
            $output = $view;
        }

        // $this->saveCache($filename, $output, $data);
        $this->contents = $output;
        unset($data, $view, $output);
        $this->resolved = true;

        return $this;
    }

    function get(): string
    {
        if (!$this->resolved) {
            $this->resolver();
        }

        //If it is stored in cache
        if (($output = $this->getFromCache($this->filename, $this->data)) !== false) {
            $this->contents = (string) $output;
        } else {
            $this->saveCache($this->filename, $this->contents, $this->data);
        }

        return $this->contents;
    }

    /**
     * Get the view cache.
     * @return bool
     */
    protected function getFromCache(string $filename, array $data = []): false|string
    {
        if (!$this->viewSaveCache) return false;

        if (isset($data['cache']) && $data['cache'] !== true) return false;

        // Creates an instance of the FileCache class
        $cache = Cache::driver()->get($filename);
        return $cache;
    }

    /**
     * Save the view in the cache.
     * @return bool
     */
    private function saveCache(?string $filename, ?string $output, array $data = [])
    {
        if (!$this->viewSaveCache) return false;

        if (isset($data['cache']) && $data['cache'] !== true) return;

        // Creates an instance of the FileCache class
        Cache::driver()->set($filename, $output);
    }

    /**
     * Renderiza un archivo de plantilla y devuelve la salida como cadena.
     *
     * @param string $templatePath Ruta al archivo de plantilla
     * @param array $data Datos para extraer en el ámbito de la plantilla
     * @return string|null Contenido de la plantilla renderizada, o null si ocurre una excepción
     * @throws \Throwable Si ocurre una excepción durante el renderizado de la plantilla
     */
    public function file(string $templatePath, array $data = []): ?string
    {
        extract(array_merge(self::$tempData, $this->data, $data), EXTR_OVERWRITE);

        ob_start();
        $_outputLevel = ob_get_level();

        try {
            ob_implicit_flush(false);
            require $templatePath;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() >= $_outputLevel) {
                ob_end_clean();
            }

            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function styles($styles): self
    {
        $headPattern = '/<\s*\/\s*head\s*>/i';
        $html = preg_replace($headPattern, $styles . '$0', $this->contents);

        $this->contents = $html;
        return $this;
    }

    public function scripts($scripts): self
    {
        $bodyPattern = '/<\s*\/\s*body\s*>/i';
        $html = preg_replace($bodyPattern, $scripts . '$0', $this->contents);

        $this->contents = $html;
        return $this;
    }

    /**
     * Injects styles and scripts into the specified HTML contents, 
     * placing styles in the head and scripts at the end of the body.
     */
    public function assets($styles, $scripts): self
    {
        $this->styles($styles);
        $this->scripts($scripts);

        return $this;
    }

    /**
     * Specifies that the current view should extend an existing layout.
     */
    public static function extend(string $layout): void
    {
        self::$layout = $layout;
    }

    /**
     * Starts holds contents for a section within the layout.
     * @param string $name Section name
     */
    public static function section(string $name)
    {
        // Save $name to static variables.
        static::$sectionStack[] = $name;

        // Start output buffering.
        ob_start();
    }

    /**
     * Captures the last section
     * @throws AxmException
     */
    public static function endSection()
    {
        $contentss = ob_get_clean();
        if (static::$sectionStack === []) {
            throw new RuntimeException('View themes, no current section.');
        }

        $section = array_pop(static::$sectionStack);

        // Ensure an array exists so we can store multiple entries for this.
        if (!array_key_exists($section, static::$sections)) {
            static::$sections[$section] = [];
        }

        static::$sections[$section][] = $contentss;
    }

    public function setView(?string $view = ''): self
    {
        $this->withLayout = false;
        $this->contents = $view;

        return $this;
    }

    function dd()
    {
        dd($this->contents);
        return  $this;
    }
}
