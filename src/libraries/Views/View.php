<?php

declare(strict_types=1);

namespace Views;

use Cache\Cache;
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
    public static $tempData = [];
    protected bool $viewSaveCache;
    public $layoutPath;
    protected $withLayout = true;
    protected string $filename = '';
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
     * Renders the specified view file.
     */
    public function render(string $view, string $ext = '.php'): self
    {
        $view = str_replace('.', DIRECTORY_SEPARATOR, trim($view)) . $ext;

        $this->filename = $filename = static::handleFile($view);
        $this->contents = $this->file($filename);

        return $this;
    }

    /**
     * Resolves the file path for a given view,
     */
    public function handleFile(string $file): string
    {
        $filename = $this->viewPath . DIRECTORY_SEPARATOR . $file;
        $filename = is_file($filename) ? $filename : $file;
        if (!is_file($filename))
            throw new RuntimeException(sprintf('View file [ %s ] does not exist.', $filename));

        return $filename;
    }

    /**
     * Sets the layout for the rendered view.
     *
     * This method specifies the layout to be used when rendering the view.
     * If no layout is provided, it defaults to 'main'.
     */
    public function layout(string $layout = 'main'): self
    {
        $this->withLayout = true;
        $this->layout = $layout;
        return $this;
    }

    /**
     * Adds data to be passed to the view.
     */
    public function withData(string|array|null $keyOrArray, $value = null): self
    {
        if (is_array($keyOrArray)) {
            $this->data = array_merge($this->data, $keyOrArray);
        } else {
            $this->data[$keyOrArray] = $value;
        }

        return $this;
    }

    /**
     * Adds global data to be passed to all views.
     */
    public function withGlobal(string $key, $value): self
    {
        self::$tempData[$key] = $value;
        return $this;
    }

    /**
     * Specifies whether to render the view with a layout.
     */
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

    /**
     * Resolves the view rendering process.
     *
     * This method finalizes the view rendering process by incorporating data, layout, and caching.
     * It prepares the view content for rendering, including layout integration if specified.
     * Additionally, it handles caching of the rendered output.
     */
    public function resolver(): self
    {
        $content = $this->renderView();
        $output = $this->includeLayout($content);
        $this->saveCache($this->filename, $output, $this->data);
        $this->contents = $output;
        $this->resolved = true;

        return $this;
    }

    /**
     * Renders the view content based on the current environment.
     */
    private function renderView(): string
    {
        $content = $this->isProduction()
            ? $this->contents
            : "<!--VIEW START . {$this->filename} -->\n {$this->contents} \n<!-- VIEW END {$this->filename} -->\n\n";

        return $content;
    }

    /**
     * Includes the layout file if the layout is enabled.
     */
    private function includeLayout(string $content): string
    {
        if ($this->withLayout) {
            return $this->file($this->layoutPath . DIRECTORY_SEPARATOR . $this->layout . '.php', ['_content' => $content]);
        }

        return $content;
    }

    /**
     * Determines if the application is running in production mode.
     */
    private function isProduction(): bool
    {
        return app()->isProduction();
    }

    /**
     * Retrieves the rendered view content.
     *
     * This method retrieves the rendered view content. If the view has not been resolved yet,
     * it resolves the view rendering process. It also checks if the rendered output is stored in the cache
     * and retrieves it from the cache if available. If not, it saves the rendered output to the cache.
     */
    function get(): string
    {
        if (!$this->resolved)
            $this->resolver();

        // If it is stored in cache
        if (($output = $this->getFromCache($this->filename, $this->data)) !== false) {
            $this->contents = (string) $output;
        } else {
            $this->saveCache($this->filename, $this->contents, $this->data);
        }

        return $this->contents;
    }

    /**
     * Get the view cache.
     */
    protected function getFromCache(string $filename, array $data = []): false|string
    {
        if (!$this->viewSaveCache)
            return false;

        if (isset($data['cache']) && $data['cache'] !== true)
            return false;

        // Creates an instance of the FileCache class
        $cache = Cache::driver()->get($filename);
        return $cache;
    }

    /**
     * Save the view in the cache.
     */
    private function saveCache(?string $filename, ?string $output, array $data = []): ?bool
    {
        if (!$this->viewSaveCache)
            return false;

        if (isset($data['cache']) && $data['cache'] !== true)
            return false;

        // Creates an instance of the FileCache class
        return Cache::driver()->set($filename, $output);
    }

    /**
     * Renders a template file and returns the output as a string.
     */
    public function file(string $templatePath, array $data = []): ?string
    {
        $mergedData = array_merge(self::$tempData, $this->data, $data);
        extract($mergedData, EXTR_OVERWRITE);
        ob_start();
        $outputLevel = ob_get_level();

        try {
            ob_implicit_flush(false);
            require $templatePath;
            return ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() >= $outputLevel) {
                ob_end_clean();
            }

            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Adds CSS styles to the <head> section of the HTML content.
     */
    public function styles(string $styles): self
    {
        $headPattern = '/<\s*\/\s*head\s*>/i';
        $html = preg_replace($headPattern, $styles . '$0', $this->contents);

        $this->contents = $html;
        return $this;
    }

    /**
     * Adds JavaScript code to the end of the <body> section of the HTML content.
     */
    public function scripts(string $scripts): self
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

    /**
     * Sets the HTML content of the view.
     */
    public function setView(?string $view = ''): self
    {
        $this->withLayout = false;
        $this->contents = $view;

        return $this;
    }

    /**
     * Dump and die function.
     * It is commonly used for debugging purposes to inspect the current state of an object.
     */
    function dd()
    {
        dd($this->contents);
        return $this;
    }

}
