<?php

namespace Axm;

use Axm\Exception\AxmException;

/**
 * Class EventManager
 * 
 * EventManager is a simple event management system that allows you to register,
 * trigger, and unregister events with associated callbacks.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
class EventManager
{
    /**
     * @var array Holds the registered events and their associated callbacks.
     */
    private $events;

    /**
     * Constructor initializes the events array.
     */
    public function __construct()
    {
        $this->events = [];
    }

    /**
     * Registers a callback for a specific event.
     *
     * @param string $name The name of the event.
     * @param callable $callback The callback function to be executed when the event is triggered.
     * @return void
     */
    public function onEvent(string $name, callable $callback): void
    {
        if (!isset($this->events[$name])) {
            $this->events[$name] = [];
        }

        $this->events[$name][] = $callback;
    }

    /**
     * Unregisters a callback for a specific event.
     *
     * @param string $name The name of the event.
     * @param callable|null $callback The callback function to unregister. If null, 
     * all callbacks for the event are removed.
     * @return void
     */
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

    /**
     * Triggers an event, executing all registered callbacks.
     *
     * @param string $name The name of the event to trigger.
     * @return void
     */
    public function triggerEvent(string $name): void
    {
        if (!isset($this->events[$name])) {
            return;
        }

        $callbacks = $this->events[$name] ?? [];
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Retrieves the callbacks registered for a specific event.
     *
     * @param string $name The name of the event.
     * @return array|false An array of callbacks if the event is registered, false otherwise.
     * @throws AxmException If the name of the event is null or empty.
     */
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
