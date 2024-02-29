<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console;

use Console\CLI;

/**
 * GeneratorTrait contains a collection of methods
 * to build the commands that generates a file.
 */
trait GeneratorTrait
{
    /**
     * Component Name
     * @var string
     */
    protected $component;

    /**
     * File directory
     * @var string
     */
    protected $directory;

    /**
     * View template name
     * @var string
     */
    protected $template;

    /**
     * namespace the class
     */
    protected $namespace;

    /**
     * Language string key for required class names.
     * @var string
     */
    protected $classNameLang = '';

    /**
     *  class names.
     * @var string
     */
    protected $className;

    /**
     * Whether to require class name.
     *
     * @internal
     * @var bool
     */
    private $hasClassName = true;

    /**
     * Whether to sort class imports.
     *
     * @internal
     * @var bool
     */
    private $sortImports = true;

    /**
     * Whether the `--suffix` option has any effect.
     *
     * @internal
     * @var bool
     */
    private $enabledSuffixing = true;

    /**
     * The params array for easy access by other methods.
     *
     * @internal
     * @var array
     */
    private $params = [];

    /**
     * Data passed to templates
     */
    protected $data = [];

    /**
     * Data passed to templates
     */
    protected $replace = [];

    protected bool $phpOutputOnly = true;

    protected const ARROW_SYMBOL = '➜ ';

    /**
     * Execute the command.
     */
    protected function execute(array $params): void
    {
        $this->params = $params;
        if ($this->getOption('namespace') === 'Axm') {
            CLI::write(self::ARROW_SYMBOL . 'CLI generator using [ Axm ] Namespace. ⚠ ', 'yellow');
            CLI::newLine();

            if (CLI::prompt(self::ARROW_SYMBOL . 'Are you sure you want to continue? ❓ ', ['y', 'n'], 'required') === 'n') {
                CLI::newLine();
                CLI::write(self::ARROW_SYMBOL . 'CLI generator cancel Operation ', 'yellow');
                CLI::newLine();
                return;
            }

            CLI::newLine();
        }

        // Get the fully qualified class name from the input.
        $class = $this->qualifyClassName();

        // Get the file path from class name.
        $path = $this->buildPath($this->className ?? $class);

        // Check if path is empty.
        if (empty($path)) {
            return;
        }

        $isFile = is_file($path);

        // Overwriting files unknowingly is a serious annoyance, So we'll check if
        // we are duplicating things, If 'force' option is not supplied, we bail.
        if (!$this->getOption('force') && $isFile) {
            CLI::error(self::ARROW_SYMBOL . 'File exist: ' . realpath($path) . ' ❌ ', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        // Check if the directory to save the file is existing.
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        helpers('filesystem');

        // Build the class based on the details we have, We'll be getting our file
        // contents from the template, and then we'll do the necessary replacements.
        if (!writeFile($path, $this->buildContent($class, $this->replace ?? [], $this->data ?? []))) {
            CLI::error(self::ARROW_SYMBOL . 'File Error: ' . realpath($path) . ' ❌ ', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        if ($this->getOption('force') && $isFile) {
            CLI::write(self::ARROW_SYMBOL . 'File Over write: ' . realpath($path) . ' 🤙', 'yellow');
            CLI::newLine();
            return;
        }

        CLI::write(self::ARROW_SYMBOL . 'File Create: ' . realpath($path) . ' 🤙', 'green');
        CLI::newLine();
    }

    /**
     * Prepare options and do the necessary replacements.
     */
    protected function prepare(string $class, array $replace = [], array $data = []): string
    {
        return $this->parseTemplate($class, $replace, $data ?? $this->data ?? []);
    }

    /**
     * Change file basename before saving.
     * Useful for components where the file name has a date.
     */
    protected function basename(string $filename): string
    {
        return basename($filename);
    }

    /**
     * Parses the class name and checks if it is already qualified.
     */
    protected function qualifyClassName(): string
    {
        // Gets the class name from input.
        $class = CLI::getSegment(2) ?? $this->className ?? null;

        if ($class === null && $this->hasClassName) {
            $nameLang = $this->classNameLang ?: ' Class name ❓';
            $class    = CLI::prompt($nameLang, null, 'required');
            CLI::newLine();
        }

        helpers('inflector');

        $component = singular($this->component);

        /**
         * @see https://regex101.com/r/a5KNCR/1
         */
        $pattern = sprintf('/([a-z][a-z0-9_\/\\\\]+)(%s)/i', $component);

        if (preg_match($pattern, $class, $matches) === 1) {
            $class = $matches[1] . ucfirst($matches[2]);
        }

        if ($this->enabledSuffixing && $this->getOption('suffix') && !strripos($class, $component)) {
            $class .= ucfirst($component);
        }

        // Trims input, normalize separators, and ensure that all paths are in Pascalcase.
        $class = ltrim(implode('\\', array_map('pascalize', explode('\\', str_replace('/', '\\', trim($class))))), '\\/');

        // Gets the namespace from input. Don't forget the ending backslash!
        $namespace = trim(str_replace('/', '\\', $this->getOption('namespace') ?? $this->namespace ?? APP_NAMESPACE), '\\') . '\\';

        if (strncmp($class, $namespace, strlen($namespace)) === 0) {
            return $class;
        }

        return $namespace . $this->directory . '\\' . str_replace('/', '\\', $class);
    }

    /**
     * Gets the generator view as defined in the `Config\Generators::$views`,
     * with fallback to `$template` when the defined view does not exist.
     */
    protected function renderTemplate(array $data = []): string
    {
        $templatePath = (is_file($this->template)) ?  $this->template :
            config('paths.consoleTemplatePath') . DIRECTORY_SEPARATOR . $this->template;

        $output = app()->view->file($templatePath, $data);

        return ($this->phpOutputOnly) ? "<?php\n\n$output" : $output;
    }

    /**
     * Perform replacements in a template using a single associative array of search and replace values.
     *
     * @param string $class The fully qualified class name.
     * @param array $replacements An associative array where the keys are the search patterns and the values are the replacement strings.
     * @param array $data Additional data for template rendering.
     * @return string The modified template.
     */
    protected function parseTemplate(string $class, array $replacements = [], array $data = []): string
    {
        // Get the namespace from the fully qualified class name.
        $namespace = $this->getNamespace($class);

        // Add namespace and class name replacements.
        $replacements['{namespace}'] = $namespace;
        $replacements['{class}'] = str_replace($namespace . '\\', '', $class);

        // Perform the replacements on the template and return it.
        return str_replace(
            array_keys($replacements),
            $replacements,
            $this->renderTemplate($data ?? $this->data ?? [])
        );
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Builds the contents for class being generated, doing all
     * the replacements necessary, and alphabetically sorts the
     * imports for a given template.
     */
    protected function buildContent(string $class, array $replace = [], array $data = []): string
    {
        $template = $this->prepare($class,  $replace, $data);

        if ($this->sortImports && preg_match('/(?P<imports>(?:^use [^;]+;$\n?)+)/m', $template, $match)) {
            $imports = explode(PHP_EOL, trim($match['imports']));
            sort($imports);

            return str_replace(trim($match['imports']), implode(PHP_EOL, $imports), $template);
        }

        return $template;
    }

    /**
     * Builds the file path from the class name.
     */
    protected function buildPath(string $class): string
    {
        $namespace = trim(str_replace('/', '\\', $this->getOption('namespace') ?? $this->namespace ?? APP_NAMESPACE), '\\');

        // Check if the namespace is actually defined and we are not just typing gibberish.
        $base = [$namespace];
        if (!$base = reset($base)) {
            CLI::error(self::ARROW_SYMBOL . 'Namespace not defined: ' . $namespace . ' ❌ ', 'light_gray', 'red');
            CLI::newLine();
            return '';
        }

        $base = realpath($base) ?: $base;
        $file = $base . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, trim(str_replace($namespace . '\\', '', $class), '\\'))
            . $this->component . '.php';

        return implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), 0, -1))
            . DIRECTORY_SEPARATOR . $this->basename($file);
    }

    /**
     * Allows child generators to modify the internal `$hasClassName` flag.
     * @return $this
     */
    protected function setHasClassName(bool $hasClassName)
    {
        $this->hasClassName = $hasClassName;
        return $this;
    }

    /**
     * Allows child generators to modify the internal `$sortImports` flag.
     * @return $this
     */
    protected function setSortImports(bool $sortImports)
    {
        $this->sortImports = $sortImports;
        return $this;
    }

    /**
     * Allows child generators to modify the internal `$enabledSuffixing` flag.
     * @return $this
     */
    protected function setEnabledSuffixing(bool $enabledSuffixing)
    {
        $this->enabledSuffixing = $enabledSuffixing;
        return $this;
    }

    /**
     * Gets a single command-line option. Returns TRUE if the option exists,
     * but doesn't have a value, and is simply acting as a flag.
     *
     * @return mixed
     */
    protected function getOption(string $name)
    {
        if (!array_key_exists($name, $this->params)) {
            return CLI::getOption($name);
        }

        return $this->params[$name] ?? true;
    }

    /**
     * Filters and formats a given string to a valid PHP class name.
     *
     * The function applies the following rules:
     * - Removes any characters that are not letters, numbers, or underscores.
     * - Ensures the first character is a letter or underscore.
     * - Converts the name to CamelCase format.
     * @param string $className The name of the class to be formatted.
     * @return string The formatted and valid PHP class name.
     */
    function formatClassName($className)
    {
        // Remove characters that are not letters, numbers, or underscores
        $filteredName = preg_replace('/[^\p{L}\p{N}_]/u', '', $className);

        // Ensure the first character is a letter or underscore
        $filteredName = preg_replace('/^[^a-zA-Z_]+/', '', $filteredName);

        // Convert to CamelCase format
        $filteredName = str_replace('_', '', ucwords($filteredName, '_'));

        return $filteredName;
    }
}
