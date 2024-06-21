<?php

declare(strict_types=1);

namespace Console;

use Console\CLI;

/**
 * Axm Framework PHP.
 *
 * GeneratorTrait contains a collection of methods
 * to build the commands that generates a file.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

trait GeneratorTrait
{
    /**
     * Component Name
     */
    protected string $component;

    /**
     * File directory
     */
    protected string $directory;

    /**
     * View template name
     */
    protected string $template;

    /**
     * namespace the class
     */
    protected string $namespace;

    /**
     * Language string key for required class names.
     */
    protected string $classNameLang = '';

    /**
     *  class names.
     */
    protected string $className;

    /**
     * Whether to require class name.
     * @internal
     */
    private bool $hasClassName = true;

    /**
     * Whether to sort class imports.
     * @internal
     */
    private bool $sortImports = true;

    /**
     * Whether the `--suffix` option has any effect.
     * @internal
     */
    private bool $enabledSuffixing = true;

    /**
     * The params array for easy access by other methods.
     * @internal
     */
    private array $params = [];

    /**
     * Data passed to templates
     */
    protected array $data = [];

    /**
     * Data passed to templates
     */
    protected array $replace = [];

    protected bool $phpOutputOnly = true;

    protected const ARROW_SYMBOL = '➜ ';

    /**
     * Execute the command.
     */
    protected function execute(array $params): void
    {
        $this->params = $params;
        $namespace = $this->getOption('namespace');
        $isAxmNamespace = $namespace === 'Axm';

        if ($isAxmNamespace) {
            $this->promptUserToContinue($namespace);
        }

        $class = $this->qualifyClassName();
        $path = $this->buildPath($class);

        if (empty($path)) {
            return;
        }

        $isFile = file_exists($path);

        if (!$this->getOption('force') && $isFile) {
            CLI::error(self::ARROW_SYMBOL . 'File exist: ' . realpath($path) . ' ❌ ', 'light_gray');
            return;
        }

        $this->createDirectory(dirname($path));

        $content = $this->buildContent($class, $this->replace ?? [], $this->data ?? []);
        if (!$this->writeFile($path, $content)) {
            CLI::error(self::ARROW_SYMBOL . 'File Error: ' . realpath($path) . ' ❌ ', 'light_gray');
            return;
        }

        if ($this->getOption('force') && $isFile) {
            CLI::write(self::ARROW_SYMBOL . 'File Overwrite: ' . realpath($path) . ' 🤙', 'yellow');
            return;
        }

        CLI::write(self::ARROW_SYMBOL . 'File Created: ' . realpath($path) . ' 🤙', 'green');
    }

    private function promptUserToContinue(string $namespace): void
    {
        CLI::write(self::ARROW_SYMBOL . "CLI generator using [ $namespace ] Namespace. ⚠ ", 'yellow');
        CLI::newLine();

        if (CLI::prompt(self::ARROW_SYMBOL . 'Are you sure you want to continue? ❓ ', ['y', 'n'], 'required') === 'n') {
            CLI::newLine();
            CLI::write(self::ARROW_SYMBOL . 'CLI generator cancelled', 'yellow');
            CLI::newLine();
            exit;
        }

        CLI::newLine();
    }

    private function createDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function writeFile(string $path, string $content): bool
    {
        helpers('filesystem');
        return writeFile($path, $content);
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
        $className = CLI::getSegment(2) ?? $this->className;

        if ($className === null && $this->hasClassName) {
            $className = CLI::prompt('Class name ❓', null, 'required');
        }

        helpers('inflector');
        $component = singular($this->component);
        $classPattern = sprintf('/([a-z][a-z0-9_\/\\\\]+)(%s)/i', $component);

        if (preg_match($classPattern, $className, $matches) === 1) {
            $className = $matches[1] . ucfirst($matches[2]);
        }

        if ($this->enabledSuffixing && $this->getOption('suffix') && !strripos($className, $component)) {
            $className .= ucfirst($component);
        }

        $className = ltrim(implode('\\', array_map('pascalize', explode('\\', str_replace(['/', '\\'], '\\', trim($className))))), '\\');
        $namespace = trim(str_replace(['/', '\\'], '\\', $this->getOption('namespace') ?? $this->namespace ?? APP_NAMESPACE), '\\') . '\\';

        return $namespace . $this->directory . '\\' . $className;
    }

    /**
     * Gets the generator view as defined in the `Config\Generators::$views`,
     * with fallback to `$template` when the defined view does not exist.
     */
    protected function renderTemplate(array $data = []): string
    {
        $templatePath = (is_file($this->template)) ? $this->template :
            config('paths.consoleTemplatePath') . DIRECTORY_SEPARATOR . $this->template;

        $output = app()->view->file($templatePath, $data);

        return ($this->phpOutputOnly) ? "<?php\n\n$output" : $output;
    }

    /**
     * Perform replacements in a template using a single associative array of search and replace values.
     */
    protected function parseTemplate(string $class, array $replacements = [], array $data = []): string
    {
        // Get the namespace from the fully qualified class name.
        $namespace = $this->getNamespace($class);

        // Add namespace and class name replacements.
        $replacements['{namespace}'] = $namespace;
        $replacements['{class}'] = trim(str_replace($namespace . '\\', '', $class), '\\');

        // Perform the replacements on the template and return it.
        return str_replace(
            array_keys($replacements),
            $replacements,
            $this->renderTemplate($data ?? $this->data ?? [])
        );
    }

    /**
     * Get the full namespace for a given class, without the class name.
     */
    protected function getNamespace(string $name): string
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
        $template = $this->prepare($class, $replace, $data);

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
            CLI::error(self::ARROW_SYMBOL . 'Namespace not defined: ' . $namespace . ' ❌ ', 'light_gray');
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
     */
    protected function setHasClassName(bool $hasClassName)
    {
        $this->hasClassName = $hasClassName;
        return $this;
    }

    /**
     * Allows child generators to modify the internal `$sortImports` flag.
     */
    protected function setSortImports(bool $sortImports)
    {
        $this->sortImports = $sortImports;
        return $this;
    }

    /**
     * Allows child generators to modify the internal `$enabledSuffixing` flag.
     */
    protected function setEnabledSuffixing(bool $enabledSuffixing): self
    {
        $this->enabledSuffixing = $enabledSuffixing;
        return $this;
    }

    /**
     * Gets a single command-line option. Returns TRUE if the option exists,
     * but doesn't have a value, and is simply acting as a flag.
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
     */
    function formatClassName(?string $className): string
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
