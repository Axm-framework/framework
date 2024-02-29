<?php

namespace Console\Commands\Utilities;

use Console\BaseCommand;
use Console\CLI;


class AddProvider extends BaseCommand
{
    /**
     * The Command's Group
     * @var string
     */
    protected $group = 'Axm';

    /**
     * The Command's Name
     * @var string
     */
    protected $name = 'add:provider';

    /**
     * The Command's Description
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     * @var string
     */
    protected $usage = 'add:provider [options]';

    /**
     * The Command's Arguments
     * @var array
     */
    protected $arguments = [
        '--alias' => 'service alias',
        '--class' => 'FQN of the service provider class.',
        '--description' => 'Library description. (Opcional)',
        '--paths' => 'Address of the framework service provider file. (Optional)'
    ];

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
        $serviceName  = $params[1] ?? CLI::getOption('alias') ?? $params['alias'] ?? CLI::prompt(self::ARROW_SYMBOL . 'Add alias: ', null, 'required|text');
        $serviceClass = $params[2] ?? CLI::getOption('class') ?? $params['class'] ?? CLI::prompt(self::ARROW_SYMBOL . 'Class Name (note: with namespace): ', null, 'required|text');
        $description  = $params[3] ?? CLI::getOption('description') ?? $params['description'] ?? CLI::prompt(self::ARROW_SYMBOL . 'Description (optional): ', null, 'text') ?? [];
        $configFile   = config('paths.providersPath') . DIRECTORY_SEPARATOR . 'providers.php';

        $this->addService($serviceName, $serviceClass, $description, $configFile);
    }

    /**
     * Add a new service to the configuration file if it doesn't already exist.
     *
     * @param string $serviceName   The name of the service to add.
     * @param string $serviceClass  The class of the service to add.
     * @param string $configFile    The path to the configuration file.
     */
    public function addService(string $serviceName, string $serviceClass, string $description = null, string $configFile)
    {
        // Check if the service already exists in the file
        if ($this->serviceExists($serviceName, $configFile)) {
            return;
        }

        // Read the current content of the file
        $currentConfig = file_get_contents($configFile);

        // Define the new service
        $newService = $this->renderProvider($serviceName, $serviceClass, $description);

        // Replace the last closing bracket with the new service and the original closing bracket
        $modifiedConfig = preg_replace('/\];/', $newService, $currentConfig, 1);

        // Write the modified configuration back to the file
        file_put_contents($configFile, $modifiedConfig);

        CLI::write(self::ARROW_SYMBOL . "Service '{$serviceName}' added successfully.", 'green');
        CLI::newLine();
    }

    /**
     * Check if a service already exists in the configuration file.
     *
     * @param string $serviceName   The name of the service to check.
     * @param string $configFile    The path to the configuration file.
     * @return bool True if the service already exists, false otherwise.
     */
    public function serviceExists(string $serviceName, string $configFile)
    {
        $currentConfig = file_get_contents($configFile);
        return strpos($currentConfig, "'{$serviceName}'") !== false;
    }

    /**
     * Render a service provider array for the given service name and class.
     *
     * @param string $serviceName
     * @param string $serviceClass
     * @return string
     */
    public function renderProvider(string $serviceName, string $serviceClass, string $description = null): string
    {
        $service = strtoupper($serviceName);
        $descriptionClass = ucfirst($serviceName);
        $description = $this->addComment($description ?? "The $descriptionClass Class is a service provider.", 60);
        $newService = <<<EOT
        /**
         * ---------------------------------------------------------------
         *  $service
         * ---------------------------------------------------------------
         * 
         * $description
         */
        '{$serviceName}' => {$serviceClass}::class,
        EOT;

        return $newService . "\n\n];";
    }

    /**
     * addComment into lines with a specified line length, ensuring that words are not cut off.
     *
     * @param string $text The input text.
     * @param int $lineLength The desired length of each line.
     * @return string The formatted text with line breaks.
     */
    public function addComment(string $text, int $lineLength)
    {
        $words = explode(" ", $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            // Check if adding the word exceeds the line length
            if (strlen($currentLine . $word) <= $lineLength) {
                $currentLine .= $word . ' ';
            } else {
                // Add the current line to the lines array and reset the line
                $lines[] = rtrim($currentLine);
                $currentLine = $word . ' ';
            }
        }

        // Add the last line to the lines array
        $lines[] = rtrim($currentLine);

        // Join the lines with line breaks
        $formattedText = implode("\n * ", $lines);

        return $formattedText;
    }
}
