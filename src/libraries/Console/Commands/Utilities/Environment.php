<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console\Commands\Utilities;

use Axm;
use Console\BaseCommand;
use Console\CLI;

/**
 * Command to display the current environment,
 * or set a new one in the `.env` file.
 */
final class Environment extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'Axm';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'env';

    /**
     * The Command's short description
     *
     * @var string
     */
    protected $description = 'Retrieves the current environment, or set a new one.';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'env [<environment>]';

    /**
     * The Command's arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'environment' => '[Optional] The new environment to set. If none is provided, 
        this will print the current environment.',
    ];

    /**
     * The Command's options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Allowed values for environment. `testing` is excluded
     * since spark won't work on it.
     *
     * @var array<int, string>
     */
    private static $knownTypes = [
        'production',
        'debug',
    ];

    /**
     * {@inheritDoc}
     */
    public function run(array $params)
    {
        array_shift($params);

        $environment = app()->getEnvironment();
        if ($params === []) {
            CLI::write(sprintf(self::ARROW_SYMBOL . 'Your environment is currently set as %s.', CLI::color($environment, 'green')));
            CLI::newLine();
            return;
        }

        $env = strtolower(array_shift($params));

        if ($env === 'testing') {
            CLI::error(self::ARROW_SYMBOL . 'The "testing" environment is reserved for PHPUnit testing.', 'light_gray', 'red');
            CLI::error(self::ARROW_SYMBOL . 'You will not be able to run axm under a "testing" environment.', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        if (!in_array($env, self::$knownTypes, true)) {
            CLI::error(sprintf(self::ARROW_SYMBOL . 'Invalid environment type "%s". Expected one of "%s".', $env, implode('" and "', self::$knownTypes)), 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        if (!$this->writeNewEnvironmentToConfigFile($env)) {
            CLI::error(self::ARROW_SYMBOL . 'Error in writing new environment to .env file.', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        CLI::write(sprintf(self::ARROW_SYMBOL . 'Environment is successfully changed to "%s".', $env), 'green');
        CLI::write(self::ARROW_SYMBOL . 'The ENVIRONMENT constant will be changed in the next script execution.');
        CLI::newLine();
    }

    /**
     * @see https://regex101.com/r/4sSORp/1 for the regex in action
     */
    function writeNewEnvironmentToConfigFile($mode)
    {
        // Define the allowed values for the mode
        $modeValues = [
            'production' => 'production',
            'debug' => 'debug',
        ];

        // Check if the mode is valid
        if (!array_key_exists($mode, $modeValues)) {
            return false;
        }

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

        // Replace APP_ENVIRONMENT value in .env file
        $envContents = preg_replace(
            '/(^APP_ENVIRONMENT\s*=\s*).*/m',
            '${1}' . $modeValues[$mode],
            $envContents
        );

        // Write changes back to the .env file
        if (file_put_contents($envFilePath, $envContents) !== false) {
            return true;
        }

        return false;
    }
}
