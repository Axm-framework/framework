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
     */
    public function __construct($key = null)
    {
        $this->init();
        $this->encrypter = new Encrypter($key);
    }

    /**
     * Initializes the session.
     */
    public function init(): void
    {
        $this->open();
    }

    /**
     * Opens the session.
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
     */
    public function read(string $sessionId): string
    {
        return (string) $this->get($sessionId);
    }

    /**
     * Deletes the session data.
     */
    public function delete(string $sessionId): bool
    {
        return $this->remove($sessionId);
    }

    /**
     * Writes session data.
     */
    public function write(string $sessionId, string $data): bool
    {
        return $this->set($sessionId, $data);
    }

    /**
     * Closes the session.
     */
    public function close(): bool
    {
        return session_write_close();
    }

    /**
     * Destroys a session.
     */
    public function destroy(string $sessionId): bool
    {
        $id = $this->read($sessionId);
        return $this->remove($id);
    }

    /**
     * Performs garbage collection on the session.
     */
    public function gc(int $maxLifeTime): int
    {
        $config = config('session.expiration');
        return $config;
    }

    /**
     * Creates the $_SESSION flash messages.
     */
    public function sessionFlashMessage()
    {
        $this->removeExpiredFlashMessages();
    }

    /**
     * Destroys the current session.
     */
    public function clear()
    {
        session_destroy();
    }

    /**
     * Returns the session ID.
     */
    public function sessionId()
    {
        return session_id();
    }

    /**
     * Regenerates the session ID.
     */
    public function regenerate(bool $destroy = false)
    {
        $_SESSION['__last_regenerate'] = time();
        session_regenerate_id($destroy);
    }

    /**
     * Modifies a message in the $_SESSION flash_messages.
     */
    public function setFlash(string $key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value'  => $message
        ];
    }

    /**
     * Gets the value of a flash message.
     */
    public function getFlashValue(string $key): ?bool
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    /**
     * Gets a flash message.
     */
    public function getFlash($key): ?bool
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? false;
    }

    /**
     * Modifies a session variable.
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
     */
    public function has(?string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Gets the value of a session variable and removes it from the session.
     */
    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    /**
     * Gets all session variables.
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Removes the specified session or all sessions if $key is empty.
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
     */
    public function __destruct()
    {
        $this->removeExpiredFlashMessages();
    }

    /**
     * Gets the current time as a Unix timestamp.
     */
    private function getCurrentTime(): int
    {
        return time();
    }

    /**
     * Removes expired flash messages after displaying them.
     */
    private function removeExpiredFlashMessages()
    {
        $_SESSION[self::FLASH_KEY] = array_filter($_SESSION[self::FLASH_KEY] ?? [], function ($flashMessage) {
            return !$flashMessage['remove'];
        });
    }

    /**
     * Frees all session variables.
     */
    public function flush()
    {
        session_unset();
    }

    /**
     * Monitors session inactivity time and redirects to the specified URL
     * if it exceeds the given session expiration time.
     */
    public function police(string $key, string $url = '', int $sessionExpiration = 300): bool
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
     */
    private function handleExpiration(?string $url = null): void
    {
        (!$url) ? app()->logout() : redirect(go($url));
    }

    /**
     * Checks if the countdown session time has reached the specified limit.
     */
    public function countDownSession(string $key, int $time): bool
    {
        return !empty($this->get($key)) && (time() - $this->get('timeSession')) > $time;
    }
}
