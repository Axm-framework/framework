<?php

namespace Session;

use SessionHandlerInterface;
use Encryption\Encrypter;

/**
 * Class Session
 *
 * Represents a session management class with features for handling flash messages,
 * session initialization, manipulation, and expiration monitoring.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @package System
 */
class Session implements SessionHandlerInterface
{
    protected const FLASH_KEY = 'flash_messages';
    private $encrypter;

    /**
     * Constructor.
     * @param mixed $key The session key.
     */
    public function __construct($key = null)
    {
        $this->init();
        $this->encrypter = new Encrypter($key);
    }

    /**
     * Initializes the session.
     * @return void
     */
    public function init(): void
    {
        $this->open();
    }

    /**
     * Opens the session.
     *
     * @param string|null $savePath The path where session data is stored. If null, the default storage path is used.
     * @param string $sessionName The name of the session (default: 'axmSesionApp').
     * @return bool True if the session is successfully opened, false otherwise.
     */
    public function open(string $savePath = null, string $sessionName = 'axmSesionApp'): bool
    {
        $config = $savePath ?? config('paths.storagePath') . DIRECTORY_SEPARATOR;
        ini_set('session_save_path', realpath($config));

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_name($sessionName);
        return session_start();
    }

    /**
     * Reads the session data.
     *
     * @param string $sessionId The session ID.
     * @return string The session data.
     */
    public function read(string $sessionId): string
    {
        return (string) $this->get($sessionId);
    }

    /**
     * Deletes the session data.
     *
     * @param string $sessionId The session ID.
     * @return bool True if the session data is successfully deleted, false otherwise.
     */
    public function delete(string $sessionId)
    {
        return $this->remove($sessionId);
    }

    /**
     * Writes session data.
     *
     * @param string $sessionId The session ID.
     * @param string $data The session data.
     * @return bool True if the session data is successfully written, false otherwise.
     */
    public function write(string $sessionId, string $data): bool
    {
        return $this->set($sessionId, $data);
    }

    /**
     * Closes the session.
     * @return bool True if the session is successfully closed, false otherwise.
     */
    public function close(): bool
    {
        return session_write_close();
    }

    /**
     * Destroys a session.
     *
     * @param string $sessionId The session ID.
     * @return bool True if the session is successfully destroyed, false otherwise.
     */
    public function destroy(string $sessionId): bool
    {
        $id = $this->read($sessionId);
        return $this->remove($id);
    }

    /**
     * Performs garbage collection on the session.
     *
     * @param int $maxLifeTime The maximum lifetime of a session.
     * @return int The session expiration configuration.
     */
    public function gc(int $maxLifeTime): int
    {
        $config = config('session.expiration');
        return $config;
    }

    /**
     * Creates the $_SESSION flash messages.
     * @return void
     */
    public function sessionFlashMessage()
    {
        $this->removeExpiredFlashMessages();
    }

    /**
     * Destroys the current session.
     * @return void
     */
    public function clear()
    {
        session_destroy();
    }

    /**
     * Returns the session ID.
     * @return string
     */
    public function sessionId()
    {
        return session_id();
    }

    /**
     * Regenerates the session ID.
     *
     * @param bool $destroy Should old session data be destroyed?
     * @return void
     */
    public function regenerate(bool $destroy = false)
    {
        $_SESSION['__last_regenerate'] = time();
        session_regenerate_id($destroy);
    }

    /**
     * Modifies a message in the $_SESSION flash_messages.
     *
     * @param string $key
     * @param mixed  $message
     * @return void
     */
    public function setFlash($key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value'  => $message
        ];
    }

    /**
     * Gets the value of a flash message.
     *
     * @param string $key
     * @return mixed|false The value of the flash message, or false if not set.
     */
    public function getFlashValue($key)
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    /**
     * Gets a flash message.
     *
     * @param string $key
     * @return mixed|false The flash message, or false if not set.
     */
    public function getFlash($key)
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? false;
    }

    /**
     * Modifies a session variable.
     *
     * @param string $key
     * @param mixed  $value
     * @param bool   $encrypt Whether to encrypt the value.
     * @return bool True if the session variable is successfully set, false otherwise.
     */
    public function set(string $key, $value, bool $encrypt = false): bool
    {
        if ($encrypt) {
            $value = $this->encrypter->encrypt($value);
        }

        $_SESSION[$key] = $value;
        return isset($_SESSION[$key]) && $_SESSION[$key] === $value;
    }

    /**
     * Gets the value of a session variable.
     *
     * @param string $key
     * @param bool   $decrypt Whether to decrypt the value.
     * @return mixed|null The value of the session variable, or null if not set.
     */
    public function get(string $key, bool $decrypt = false)
    {
        if (!isset($_SESSION[$key])) {
            return null;
        }

        $result = $_SESSION[$key];
        return $decrypt ? $this->encrypter->decrypt($result) : $result;
    }

    /**
     * Checks if a session variable is set.
     *
     * @param string $key
     * @return bool True if the session variable is set, false otherwise.
     */
    public function has($key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Gets the value of a session variable and removes it from the session.
     *
     * @param string $key
     * @param mixed  $default The default value if the key is not set.
     * @return mixed The value of the session variable.
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    /**
     * Gets all session variables.
     * @return array All session variables.
     */
    public function all()
    {
        return $_SESSION;
    }

    /**
     * Removes the specified session or all sessions if $key is empty.
     *
     * @param string $key
     * @return bool
     */
    public function remove(string $key = ''): bool
    {
        try {
            if (empty($key)) unset($_SESSION);
            else unset($_SESSION[$key]);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Destructor method called when the object is no longer referenced.
     * Removes expired flash messages after displaying them.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->removeExpiredFlashMessages();
    }

    /**
     * Gets the current time as a Unix timestamp.
     * @return int The current time as a Unix timestamp.
     */
    private function getCurrentTime(): int
    {
        return time();
    }

    /**
     * Removes expired flash messages after displaying them.
     * @return void
     */
    private function removeExpiredFlashMessages()
    {
        $_SESSION[self::FLASH_KEY] = array_filter($_SESSION[self::FLASH_KEY] ?? [], function ($flashMessage) {
            return !$flashMessage['remove'];
        });
    }

    /**
     * Frees all session variables.
     * @return void
     */
    public function flush()
    {
        session_unset();
    }

    /**
     * Monitors session inactivity time and redirects to the specified URL
     * if it exceeds the given session expiration time.
     *
     * @param string $key
     * @param string $url
     * @param int    $sessionExpiration
     * @return bool True if the session expired, false otherwise.
     */
    public function police(string $key, string $url = '', int $sessionExpiration = 300)
    {
        $currentTime = $this->getCurrentTime();

        if (!empty($this->get($key))) {
            $lifeSession = $currentTime - $this->get('time');
            $this->set('time', $currentTime);

            if ($lifeSession > $sessionExpiration) {
                $this->handleExpiration($url);

                return true;
            }
        } else {
            $this->set('time', $currentTime);
        }

        return false;
    }

    /**
     * Handles the expiration by logging out if the URL is empty 
     * or redirecting to the specified URL.
     *
     * @param string|null $url The URL to redirect to in case it is not empty.
     */
    private function handleExpiration(?string $url = null): void
    {
        (!$url) ? app()->logout() : redirect(go($url));
    }

    /**
     * Checks if the countdown session time has reached the specified limit.
     *
     * @param string $key
     * @param int    $time
     * @return bool True if the countdown session time has reached the limit, false otherwise.
     */
    public function countDownSession(string $key, int $time): bool
    {
        return !empty($this->get($key)) && (time() - $this->get('timeSession')) > $time;
    }
}
