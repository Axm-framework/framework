<?php

namespace Raxm;

use Application;
use Views\View;
use Raxm\Component;
use Exception\Exception;
use Raxm\Support\FileUploadController;


class Raxm
{
    protected static string $componentName;
    private static $instances;
    protected static $ucfirstComponentName;
    public $hasRenderedScripts = false;
    public $hasRenderedStyles  = false;
    private static $injectedAssets = false;
    private static App $app;

    public function __construct()
    {
        self::$app = app();
    }

    /**
     * Boot the application.
     *
     * This method registers the configuration, includes the Raxm utility helpers,
     * registers the routes, and loads the Raxm assets.
     * @return void
     */
    public static function boot()
    {
        self::registerConfig();
        self::includeHelpers();
        self::registerRoutes();
        // self::raxmAssets();
    }

    /**
     * Register configuration settings for Raxm.
     * @return void
     */
    public static function registerConfig()
    {
        $pathFile = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $file = 'raxm.php';
        config()->load($file, true, $pathFile);
    }

    /**
     * Include the Raxm utility helpers.
     *
     * This method includes the Raxm utility helpers from the `raxmUtils` file.
     * @return void
     */
    public static function includeHelpers()
    {
        helpers('raxmUtils', __DIR__);
    }

    /**
     * Set a flag to indicate that Raxm assets should be loaded.
     *
     * This method sets a flag on the `View` class that can be checked to determine
     * whether or not to load Raxm assets.
     * @return void
     */
    public static function raxmAssets()
    {
        View::make()->$raxmAssets = true;
    }

    /**
     * Register the Raxm application routes.
     *
     * This method registers the following routes:
     * - POST /raxm/update/{name}: Returns the Raxm component without a layout.
     * - POST /raxm/upload-file: Handles file uploads.
     * - GET /raxm/preview-file/{filename}: Previews a file.
     * - GET /vendor/axm/raxm/js/index.js: Returns the Raxm JavaScript assets.
     * - GET /raxmraxm.js.map: Returns the Raxm JavaScript source.
     * @return void
     */
    public static function registerRoutes()
    {
        $router = self::$app->router;
        $assetUrl = self::$app->config('raxm.asset_url');

        $router->addRoute('POST', '/raxm/update/{name}', function ($name) {
            return self::getComponentWithoutLayout($name);
        });

        $router->addRoute('POST', '/raxm/upload-file', [FileUploadController::class, 'handle']);
        $router->addRoute('GET', '/raxm/preview-file/{filename}', function ($filename) {
            return static::previewFile($filename);
        });
        $router->addRoute('GET', $assetUrl, fn () => static::returnJavaScriptAsFile());
    }

    /**
     * Outputs the RAXM script and style tags.
     * @param array $options An array of options to be used in generating the tags.
     */
    public static function returnJavaScriptAsFile()
    {
        $file = DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'raxm.js';
        return static::pretendResponseIsFile(dirname(__DIR__, 1) . $file);
    }

    public static function pretendResponseIsFile($file, $mimeType = 'application/javascript')
    {
        $lastModified = filemtime($file);
        $headers = static::pretendedResponseIsFileHeaders($file, $mimeType, $lastModified);

        return self::$app->response->file($file, $headers)->send();
    }

    static private function pretendedResponseIsFileHeaders($filename, $mimeType, $lastModified)
    {
        $expires = strtotime('+1 year');
        $cacheControl = 'public, max-age=31536000';

        if (static::matchesCache($lastModified)) {
            return app()->response->make('', [
                'Expires' => static::httpDate($expires),
                'Cache-Control' => $cacheControl,
            ], 304);
        }

        $headers = [
            'Content-Type'  => "$mimeType; charset=utf-8",
            'Expires'       => static::httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => static::httpDate($lastModified),
        ];

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'br') {
            $headers['Content-Encoding'] = 'br';
        }

        return $headers;
    }

    /**
     * / Returns a formatted HTTP date string
     * @param int $timestamp The Unix timestamp
     * @return string The formatted HTTP date string
     */
    static function httpDate($timestamp)
    {
        return sprintf('%s GMT', gmdate('D, d M Y H:i:s', $timestamp));
    }

    /**
     * Returns a Raxm component without a layout.
     *
     * This method takes a component name as a string, parses it to determine the
     * component class name, and then mounts the component without a layout.
     * @param string $component The name of the component to mount.
     * @return \Axm\Views\View The mounted component.
     */
    public static function getComponentWithoutLayout(string $component): string
    {
        $names = self::parserComponent($component);
        return self::mountComponent(new $names, true);
    }

    /**
     * Parses a component name into a fully-qualified class name.
     *
     * This method takes a component name as a string, removes the "raxm" prefix (if any),
     * appends the "Raxm" suffix, and then returns the fully-qualified class name by
     * concatenating the component name with the namespace specified in the Raxm
     * configuration.
     * @param string $component The name of the component to parse.
     * @return string The fully-qualified class name of the component.
     */
    public static function parserComponent(string $component)
    {
        $component = str_ireplace('raxm', '', $component);
        $componentName = $component . 'Raxm';

        $nameSpace = config('raxm.class_namespace');
        return $nameSpace . ucfirst($componentName);
    }

    /**
     * Get an instance of a specified component.
     * 
     * @param string $componentName The name of the component to retrieve.
     * @return Component An instance of the specified component.
     * @throws Exception if the specified component class does not exist.
     */
    public static function getInstance(string $className): Component
    {
        self::$componentName = $className;
        return self::$instances[$className] ??= new $className;
    }

    /**
     * Get the current component name.
     * @return string|null The current component name.
     */
    public static function componentName()
    {
        $className = class_basename(self::$componentName);
        return $className ?? null;
    }

    /**
     * Get the instance of the currently specified component.
     * @return Component An instance of the currently specified component.
     */
    public static function getInstanceNowComponent(): Component
    {
        return self::$instances[self::$componentName];
    }

    /**
     * Initialize a specified component and display its HTML.
     * 
     * @param string $componentName The name of the component to initialize.
     * @throws Exception if the specified component class does not exist.
     */
    public static function initializeComponent(string $componentName): string
    {
        $_instance = self::getInstance($componentName);
        $id = bin2hex(random_bytes(10));
        $html = $_instance->initialInstance($id);

        return $html;
    }

    /**
     * Run a specified component and display its HTML.
     * 
     * @param string $componentName The name of the component to run.
     * @throws Exception if the specified component class does not exist.
     */
    public static function runComponent(string $componentName)
    {
        $_instance = self::getInstance($componentName);
        return $_instance->run();
    }

    /**
     * Mounts a component instance and returns the resulting HTML.
     *
     * This method takes a component class object and an optional "withoutLayout" flag,
     * and returns the resulting HTML. If the "withoutLayout" flag is false (which is
     * the default), the component will be rendered within the layout specified in
     * the Raxm configuration. If the "withoutLayout" flag is true, the component will
     * be rendered without a layout.
     *
     * @param object $class The component class object to mount.
     * @param bool   $withoutLayout Whether to render the component without a layout.
     * @return string The resulting HTML of the mounted component.
     */
    public static function mountComponent(Object $class, bool $withoutLayout = false)
    {
        $instance = self::$app->controller->view();
        $config = config();

        $layoutName = $config->raxm->layout;
        $view = self::runOrInitializeComponent($class);
        if (!$withoutLayout) {
            $html = $instance->setView($view)
                ->layout($layoutName)
                ->resolver()
                ->assets(static::styles(), static::scripts())
                ->get();

            self::$injectedAssets = true;
        } else {
            $html = $view;
        }

        echo $html . PHP_EOL;
        unset($html, $view);
        exit;
    }

    /**
     * Runs the component or initializes it if it hasn't been run before.
     *
     * @param Object $component The component to be compiled.
     * @return string The HTML code generated by the component.
     */
    public static function runOrInitializeComponent(Object $component)
    {

        $componentName = $component::class;
        $html = self::$app->request->isPost()
            ? self::runComponent($componentName)
            : self::initializeComponent($componentName);
        return $html;
    }

    public static function matchesCache($lastModified)
    {
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        return @strtotime($ifModifiedSince) === $lastModified;
    }

    /**
     * Outputs the RAXM script and style tags.
     * @param array $options An array of options to be used in generating the tags.
     */
    public static function raxmScripts(array $options = [])
    {
        // Merge the provided options with default values.
        $options = array_merge(['nonce' => 'nonce-value'], $options);

        // Generate the styles and script tags.
        $stylesTag = static::styles($options);
        $scriptTag = static::scripts($options);

        // Output the tags.
        echo $stylesTag . PHP_EOL . $scriptTag . PHP_EOL;
    }

    /**
     * Generate JavaScript assets.
     * 
     * @param array $options Additional options for JavaScript assets.
     * @return string The generated JavaScript assets as HTML script tags.
     */
    protected static function js(array $options = [])
    {
        $app = self::$app;
        $assetUrl = $app->config('raxm.asset_url');
        $appUrl   = $app->config('raxm.app_url');

        $csrfToken = "'" . $app->getCsrfToken() . "'"  ??  'null';

        // Added nonce variable to store the nonce value if it is set in the options array. 
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        $windowRaxmCheck = "if (window.Raxm) { delete window.Raxm }";

        $progressBar = $app->config('raxm.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';

        // Added randomId variable to generate a random id for the asset path url using crc32 and rand functions. 
        $randomId = crc32(rand(1000000, 99999999));

        // Added fullAssetPath variable to store the full asset path url with the random id generated in the previous step. 
        $fullAssetPath = "{$assetUrl}?id={$randomId}";

        $script = <<<HTML
            <script src="{$fullAssetPath}" {$nonce} {$progressBar} data-csrf="{$csrfToken}" data-baseUrl="{$appUrl}"></script>
        HTML;

        return $script . PHP_EOL;
    }

    /**
     * Generates the RAXM script tags.
     *
     * @param array $options An array of options to be used in generating the tags.
     * @return string The generated script tags.
     */
    public static function scripts(array $options = [])
    {
        self::$app->raxm->hasRenderedScripts = true;

        $debug = config('app.debug');
        $scripts = static::js($options);

        // HTML Label.
        $html = $debug ? ['<!-- Raxm Scripts -->'] : [];
        $html[] = $scripts;

        return implode(PHP_EOL, $html);
    }

    /**
     * Generate and return Raxm styles.
     * 
     * @param array $options Additional options for Raxm styles.
     * @return string The generated Raxm styles as HTML style tags.
     */
    public static function styles($options = [])
    {
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';
        $progressBarColor = config('raxm.navigate.progress_bar_color', '#2299dd');

        $html = <<<HTML
        <!-- Raxm Styles -->
        <style {$nonce}>
            [axm\:loading], [axm\:loading\.delay], [axm\:loading\.inline-block], [axm\:loading\.inline], [axm\:loading\.block], [axm\:loading\.flex], [axm\:loading\.table], [axm\:loading\.grid], [axm\:loading\.inline-flex] {
                display: none;
            }
            [axm\:loading\.delay\.shortest], [axm\:loading\.delay\.shorter], [axm\:loading\.delay\.short], [axm\:loading\.delay\.long], [axm\:loading\.delay\.longer], [axm\:loading\.delay\.longest] {
                display:none;
            }
            [axm\:offline][axm\:offline] {
                display: none;
            }
            [axm\:dirty]:not(textarea):not(input):not(select) {
                display: none;
            }
            [x-cloak] {
                display: none;
            }
            :root {
                --raxm-progress-bar-color: {$progressBarColor};
            }
            input:-webkit-autofill, select:-webkit-autofill, textarea:-webkit-autofill {
                animation-duration: 50000s;
                animation-name: raxmautofill;
            }
            @keyframes raxmautofill { from {} }
        </style>
        <!-- END Raxm Styles -->
        HTML;

        return static::minify($html);
    }

    /**
     * Minify the given HTML content by removing unnecessary whitespace.
     * 
     * @param string $subject The HTML content to be minified.
     * @return string The minified HTML content.
     */
    protected static function minify($subject)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }
}
