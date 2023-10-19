<?php

namespace Axm\Session;

use Axm;
use Axm\Encryption\Encrypter;


/**
 * Class Session
 *
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package System
 */
class Session //implements SessionHandlerInterface
{
    private $key; // Clave secreta para firmar las cookies
    protected const FLASH_KEY = 'flash_messages';

    public function __construct($key = null)
    {
        $this->key = $key;

        $this->init();
        $this->sessionFlashMessage();
    }


    /**
     * initializa la Session
     * 
     */
    public function init(): void
    {
        ini_set('session_save_path', realpath(STORAGE_PATH . '/framework/session/'));

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_start();
    }



    /**
     * Crea la $_SESSION flash
     * y activa el $flashMessage['remove'] = true para que se muestre una sola ves.
     */
    public function sessionFlashMessage()
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }

        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    /**
     * Destroys the current session.
     */
    public function clear()
    {
        session_destroy();
    }

    /**
     * return the id session.
     * @return string 
     */
    public function sessionId()
    {
        return session_id();
    }

    /**
     * Regenerates the session ID.
     *
     * @param bool $destroy Should old session data be destroyed?   */
    public function regenerate(bool $destroy = false)
    {
        $_SESSION['__last_regenerate'] = time();
        session_regenerate_id($destroy);
    }


    /**Modificar un message en la session flash_messages */
    public function setFlash($key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    /**Obtener un message en la session flash_messages */
    public function getFlashValue($key)
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    /**Obtener un message en la session flash_messages */
    public function getFlash($key)
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? false;
    }

    /**Modificar una session indicada */
    public function set(string $key, $value, bool $encrypt = false): void
    {
        $_SESSION[$key] = $value;

        if ($encrypt) {
            $encryption = new Encrypter('40e5944789c2d09635a7f32efd32e437');

            $encrypted_session_data = $encryption->encrypt($value);
            $_SESSION[$key] = $encrypted_session_data;
            unset($encrypted_session_data);
        }
    }


    /**Obtener una session indicada */
    public function get(string $key, bool $decrypt = false)
    {

        if (!isset($_SESSION[$key])) {
            return null;
        }

        $result = $_SESSION[$key];

        if ($decrypt) {
            $decryption = new Encrypter('40e5944789c2d09635a7f32efd32e437');
            $result = $decryption->decrypt($result);
        }

        return $result;
    }


    /**checkar una session indicada */
    public function has($key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**Obtiene el valor de una variable de sesión y lo elimina de la sesión. */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }


    /**obtener Todas */
    public function all()
    {
        return $_SESSION;
    }

    /**Eliminar la session indicada */
    public function remove(string $key = '')
    {
        if (empty($key)) {
            unset($_SESSION);
        } else
            unset($_SESSION[$key]);
    }

    /**destruir todos los message*/
    public function __destruct()
    {
        $this->removeFlashMessages();
    }

    /**eliminar los mesages despues de mostrarlos */
    private function removeFlashMessages()
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => $flashMessage) {
            if ($flashMessage['remove']) {
                unset($flashMessages[$key]);
            }
        }

        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }


    /** Free all session variables*/
    public function flush()
    {
        session_unset();
    }

    /**
     * Vigila el tiempo de incatividad de una session.
     * Si esta es mayor que el tiempo designado en CONFIG['sessionExpiration']
     * redigirá a una url(por defecto logout).
     * @param string $key nombre de la session.
     * @param string $url direccion a redireccionar.
     */
    public function police(string $key, string $url = '')
    {
        if (!empty($this->get($key))) :
            if (!empty($this->get('time'))) {
                $inactivo = Axm::app()->config()->sessionExpiration;     //300 = 5min --->  120 = 2min ---> 1200 = 20 min
                $life_session = time() - $this->get('time');
                $this->set('time', time());

                if ($life_session > $inactivo) :
                    if (empty($url))
                        Axm::app()->logout();
                    else
                        redirect(go($url));
                    return true;
                endif;
            }
            $this->set('time', time());
            return false;
        else :
            $this->set('time', time());
            return false;
        endif;
    }


    /**
     * Vigila el tiempo de incatividad de una session.
     * Si esta es mayor que el tiempo designado en CONFIG['sessionExpiration']
     * redigirá a una url(por defecto logout).
     * @param string $key nombre de la session.
     * @param string $url direccion a redireccionar.
     */
    public function countDownSession(string $key, int $time)
    {
        if (empty($this->get($key)))
            return false;
        elseif (empty($this->get('timeSession')))
            return false;
        else {
            $life_session = time() - $this->get('timeSession');
            if ($life_session > $time) :       //300 = 5min --->  120 = 2min ---> 1200 = 20 min
                return true;
            endif;

            return false;
        }
    }
}
