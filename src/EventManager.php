<?php

namespace Axm;

use Axm\Exception\AxmException;

/**
 *  Class EventManager 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class EventManager
{
    private $events;


    public function __construct()
    {
        $this->events = [];
    }


    public function onEvent(string $name, callable $callback): void
    {
        if (!isset($this->events[$name])) {
            $this->events[$name] = [];
        }

        $this->events[$name][] = $callback;
    }


    public function offEvent(string $name, callable $callback = null): void
    {
        if (!isset($this->events[$name])) {
            return;
        }

        if ($callback === null) {
            unset($this->events[$name]);
        } else {
            $key = array_search($callback, $this->events[$name], true);
            if ($key !== false) {
                unset($this->events[$name][$key]);
            }
        }
    }


    public function triggerEvent(string $name): void
    {
        if (!isset($this->events[$name])) return;

        $callbacks = $this->events[$name] ?? [];
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }


    public function getEvent(string $name): array|false
    {
        if (empty($name)) {
            throw new AxmException('The name of the event cannot be null or empty.');
        }

        if (array_key_exists($name, $this->events)) {
            return $this->events[$name];
        }

        return false;
    }



    /**
     * Checks if an event is registered in the event manager.
     *
     * @param string $eventName The name of the event.
     * @return bool Whether the event is registered or not.
     */
    public function hasEvent(string $eventName): bool
    {
        return isset($this->events[$eventName]);
    }
}
