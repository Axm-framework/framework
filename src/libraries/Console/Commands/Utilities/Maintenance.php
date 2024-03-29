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

use Axm;
use Console\BaseCommand;
use Console\CLI;

/**
 * Command to display the current environment,
 * or set a new one in the `.env` file.
 */
final class Maintenance extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected string $group = 'Axm';

    /**
     * The Command's name
     */
    protected string $name = 'maintenance';

    /**
     * The Command's short description
     */
    protected string $description = 'Activate or desactivate the app maintenance.';

    /**
     * The Command's usage
     */
    protected string $usage = 'maintenance [options]';

    /**
     * The Command's arguments
     */
    protected array $arguments = [];

    /**
     * The Command's options
     */
    protected array $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $mode = $params[1] ?? [];
        if (!$mode) {
            $mode = CLI::prompt(self::ARROW_SYMBOL . 'Add the operation to follow.', ['true', 'false']);
        }

        if (!$this->writeNewEnvironmentToConfigFile($mode)) {
            CLI::error(self::ARROW_SYMBOL . 'Error in writing new environment to .env file.', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        CLI::write(sprintf(self::ARROW_SYMBOL . 'Maintenance mode: "%s".', $mode), 'green');
        CLI::newLine();
    }

    /**
     * @see https://regex101.com/r/4sSORp/1 for the regex in action
     */
    function writeNewEnvironmentToConfigFile($mode)
    {
        // Ruta al archivo .env
        $envFilePath = ROOT_PATH . DIRECTORY_SEPARATOR . '.env';

        // Check if the .env file exists
        if (!is_file($envFilePath)) {
            CLI::write(self::ARROW_SYMBOL . 'The configuration file ".env" is not found.', 'yellow');
            CLI::newLine();

            return false;
        }

        // Read the contents of the .env file
        $envContents = file_get_contents($envFilePath);

        // Replace APP_DOWN value in .env file
        $envContents = preg_replace(
            '/(^APP_DOWN\s*=\s*).*/m',
            '${1}' . $mode,
            $envContents
        );

        // Write changes back to the .env file
        if (file_put_contents($envFilePath, $envContents) !== false) {
            return true;
        }

        return false;
    }
}
