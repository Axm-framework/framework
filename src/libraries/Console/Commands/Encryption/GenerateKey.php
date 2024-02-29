<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console\Commands\Encryption;

use Console\BaseCommand;
use Console\CLI;
use Encryption\Encrypter;

/**
 * Generates a new encryption key.
 */
class GenerateKey extends BaseCommand
{
    /**
     * The Command's group.
     *
     * @var string
     */
    protected $group = 'Encryption';

    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'key:generate';

    /**
     * The Command's usage.
     *
     * @var string
     */
    protected $usage = 'key:generate [options]';

    /**
     * The Command's short description.
     *
     * @var string
     */
    protected $description = 'Generates a new encryption key and writes it in an `.env` file.';

    /**
     * The command's options
     *
     * @var array
     */
    protected $options = [
        '--force'  => 'Force overwrite existing key in `.env` file.',
        '--length' => 'The length of the random string that should be returned in bytes. Defaults to 32.',
        '--prefix' => 'Prefix to prepend to encoded key (either hex2bin or base64). Defaults to hex2bin.',
        '--show'   => 'Shows the generated key in the terminal instead of storing in the `.env` file.',
    ];

    /**
     * Actually execute the command.
     */
    public function run(array $params)
    {
        $prefix = $params['prefix'] ?? CLI::getOption('prefix');
        if (in_array($prefix, [null, true], true)) {
            $prefix = 'hex2bin';
        } elseif (!in_array($prefix, ['hex2bin', 'base64'], true)) {
            $prefix = CLI::prompt(self::ARROW_SYMBOL . 'Please provide a valid prefix to use.', ['hex2bin', 'base64'], 'required'); // @codeCoverageIgnore
        }

        $length = $params['length'] ?? CLI::getOption('length');
        if (in_array($length, [null, true], true)) {
            $length = 32;
        }

        $encodedKey = $this->generateRandomKey($prefix, $length);

        if (array_key_exists('show', $params) || (bool) CLI::getOption('show')) {
            CLI::write($encodedKey, 'yellow');
            CLI::newLine();
            return;
        }

        if (!$this->setNewEncryptionKey($encodedKey, $params)) {
            // CLI::write(self::ARROW_SYMBOL . 'Error in setting new encryption key to .env file.', 'light_gray', 'red');
            CLI::newLine();
            return;
        }

        // force DotEnv to reload the new env vars
        putenv('APP_KEY');
        unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);
        CLI::write(self::ARROW_SYMBOL . 'Application\'s new encryption key was successfully set.', 'green');
        CLI::newLine();
    }

    /**
     * Generates a key and encodes it.
     */
    protected function generateRandomKey(string $prefix, int $length): string
    {
        $key = (new Encrypter())->getKey();
        if ($prefix === 'hex2bin') {
            return 'hex2bin:' . bin2hex($key);
        }

        return 'base64:' . base64_encode($key);
    }

    /**
     * Sets the new encryption key in your .env file.
     */
    protected function setNewEncryptionKey(string $key, array $params): bool
    {
        $currentKey = env('APP_KEY', '');

        if ($currentKey !== '' && !$this->confirmOverwrite($params)) {
            return false;
        }

        return $this->writeNewEncryptionKeyToFile($currentKey, $key);
    }

    /**
     * Checks whether to overwrite existing encryption key.
     */
    protected function confirmOverwrite(array $params): bool
    {
        return (array_key_exists('force', $params)
            || CLI::getOption('force'))
            || CLI::prompt(self::ARROW_SYMBOL . 'Overwrite existing key?', ['n', 'y']) === 'y';
    }

    /**
     * Writes the new encryption key to .env file.
     */
    protected function writeNewEncryptionKeyToFile(string $oldKey, string $newKey): bool
    {
        $baseEnv = ROOT_PATH . DIRECTORY_SEPARATOR . 'env';
        $envFile = ROOT_PATH . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envFile)) {
            if (!file_exists($baseEnv)) {
                CLI::write(self::ARROW_SYMBOL . 'Both default shipped `env` file and custom `.env` are missing.', 'yellow');
                CLI::write(self::ARROW_SYMBOL . 'Here\'s your new key instead: ' . CLI::color($newKey, 'yellow'));
                CLI::newLine();
                return false;
            }

            copy($baseEnv, $envFile);
        }

        $ret = file_put_contents($envFile, preg_replace(
            $this->keyPattern($oldKey),
            "\nAPP_KEY={$newKey}",
            file_get_contents($envFile)
        ));

        return $ret !== false;
    }

    /**
     * Get the regex of the current encryption key.
     */
    protected function keyPattern(string $oldKey): string
    {
        $escaped = preg_quote($oldKey, '/');

        if ($escaped !== '') {
            $escaped = "[{$escaped}]*";
        }

        return "/^[#\\s]*APP_KEY[=\\s]*{$escaped}$/m";
    }
}
