<?php

namespace Raxm\Commands;

use Console\BaseCommand;
use Console\CLI;
use Console\GeneratorTrait;


class MakeRaxm extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     * @var string
     */
    protected $group = 'Raxm';

    /**
     * The Command's Name
     * @var string
     */
    protected $name = 'make:raxm';

    /**
     * The Command's Description
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     * @var string
     */
    protected $usage = 'make:raxm [name]';

    /**
     * The Command's Arguments
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     * @param array $params
     */
    public function run(array $params)
    {
        if (empty($params[1])) {
            CLI::error('You must add the component name.');
            system('php axm help make:raxm --no-header');
            CLI::newLine(3);
            exit;
        }

        try {
            $options = [
                'alias' => 'raxm',
                'class' => 'Axm\Raxm\Raxm',
                'description' => "Raxm is a livewire-based library for providing a development experience similar to that of single-page applications (SPA), but without the need to write complex JavaScript code. It allows you to create interactive user interfaces by updating user interface components in response to user actions.",
                'paths' => config('paths.providersPath') . DIRECTORY_SEPARATOR . 'providers.php'
            ];

            $this->call('add:provider', $options);

            $this->createTemplate('component', null, 'App', 'Raxm', 'raxm.component.tpl.php', $params);
            $this->createTemplate('view', null, 'resources', 'views/raxm', 'raxm.view.tpl.php', $params, false);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Create a template for a specific component type and execute it if necessary.
     *
     * @param string $templateType The type of template to create (e.g., 'services', 'component', 'view').
     * @param string|null $className The class name for the template (null for 'component' and 'view' types).
     * @param string $namespace The namespace for the template.
     * @param string $directory The directory for the template.
     * @param string $templateFile The name of the template file.
     * @param array $params Additional parameters for the template rendering.
     * @param bool $phpOutputOnly Whether to output PHP code only (default is true).
     */
    private function createTemplate(string $templateType, string|null $className, string $namespace, string $directory, string $templateFile, array $params, bool $phpOutputOnly = true)
    {
        $this->hasClassName = !empty($className);
        $this->className = $className;
        $this->component = 'Raxm';
        $this->namespace = $namespace;
        $this->directory = $directory;
        $this->template = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateFile;
        $this->phpOutputOnly = $phpOutputOnly;

        if ($templateType === 'providers') {
            $dir = config('paths.providersPath') . DIRECTORY_SEPARATOR . "$className.php";
            if (is_file($dir)) {
                return;
            }
        }

        $this->execute($params);
    }

    /**
     * Prepare a class name by parsing the template and replacing placeholders.
     *
     * @param string $class The fully qualified class name to prepare.
     * @return string The prepared class name.
     */
    protected function prepare(string $class): string
    {
        return $this->parseTemplate(
            $class,
            [
                '{view}' => 'raxm.' . strtolower($this->classBasename($class))
            ]
        );
    }

    /**
     * Get the base name of a class, stripping its namespace.
     *
     * @param string $class The fully qualified class name.
     * @return string The base name of the class without its namespace.
     */
    public function classBasename($class)
    {
        $parts = explode('\\', $class);
        $className = end($parts);
        return $className;
    }

    /**
     * Add a new service to the configuration file if it doesn't already exist.
     *
     * @param string $serviceName   The name of the service to add.
     * @param string $serviceClass  The class of the service to add.
     * @param string $configFile    The path to the configuration file.
     */
    function addService($serviceName, $serviceClass, $configFile)
    {
        // Check if the service already exists in the file
        if ($this->serviceExists($serviceName, $configFile)) {
            // echo "The service '{$serviceName}' already exists in the file.\n";
            return;
        }

        // Read the current content of the file
        $currentConfig = file_get_contents($configFile);

        // Define the new service
        $newService = "\n  '{$serviceName}' => {$serviceClass}::class,\n];";

        // Replace the last closing bracket with the new service and the original closing bracket
        $modifiedConfig = preg_replace('/\];/', $newService, $currentConfig, 1);

        // Write the modified configuration back to the file
        file_put_contents($configFile, $modifiedConfig);

        echo "Service '{$serviceName}' added successfully.\n";
    }

    /**
     * Check if a service already exists in the configuration file.
     *
     * @param string $serviceName   The name of the service to check.
     * @param string $configFile    The path to the configuration file.
     *
     * @return bool True if the service already exists, false otherwise.
     */
    function serviceExists($serviceName, $configFile)
    {
        // Read the current content of the file
        $currentConfig = file_get_contents($configFile);

        // Check if the service is already present in the file
        return strpos($currentConfig, "'{$serviceName}'") !== false;
    }
}
