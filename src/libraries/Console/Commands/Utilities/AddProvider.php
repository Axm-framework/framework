<?php

/**
 * Axm Framework PHP.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Console
 */

namespace Console\Commands\Utilities;

use Console\BaseCommand;
use Console\CLI;


class AddProvider extends BaseCommand
{
    /**
     * The Command's Group
     */
    protected string $group = 'Axm';

    /**
     * The Command's Name
     */
    protected string $name = 'add:provider';

    /**
     * The Command's Description
     */
    protected string $description = '';

    /**
     * The Command's Usage
     */
    protected string $usage = 'add:provider [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [
        '--alias' => 'service alias',
        '--class' => 'FQN of the service provider class.',
        '--description' => 'Library description. (Opcional)',
        '--paths' => 'Address of the framework service provider file. (Optional)'
    ];

    /**
     * The Command's Options
     */
    protected array $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $serviceName = $params[1] ?? CLI::getOption('alias') ?? $params['alias'] ?? CLI::prompt(self::ARROW_SYMBOL . 'Add alias: ', null, 'required|text');
        $serviceClass = $params[2] ?? CLI::getOption('class') ?? $params['class'] ?? CLI::prompt(self::ARROW_SYMBOL . 'Class Name (note: with namespace): ', null, 'required|text');
        $description = $params[3] ?? CLI::getOption('description') ?? $params['description'] ?? CLI::prompt(self::ARROW_SYMBOL . 'Description (optional): ', null, 'text') ?? [];
        $configFile = config('paths.providersPath') . DIRECTORY_SEPARATOR . 'providers.php';

        $this->addService($serviceName, $serviceClass, $description, $configFile);
    }

    /**
     * Add a new service to the configuration file if it doesn't already exist.
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
     */
    public function serviceExists(string $serviceName, string $configFile): bool
    {
        $currentConfig = file_get_contents($configFile);
        return strpos($currentConfig, "'{$serviceName}'") !== false;
    }

    /**
     * Render a service provider array for the given service name and class.
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
