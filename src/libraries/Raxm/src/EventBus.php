<?php

namespace Raxm;

/**
 * The EventBus class manages event listeners and triggers events in the Raxm framework.
 */
class EventBus
{
    /**
     * The array to store regular event listeners.
     * @var array
     */
    protected $listeners = [];

    /**
     * The array to store event listeners executed before the main event.
     * @var array
     */
    protected $listenersBefore = [];

    /**
     * The array to store event listeners executed after the main event.
     * @var array
     */
    protected $listenersAfter = [];

    /**
     * Bootstraps the EventBus as a singleton instance in the application.
     */
    public function boot()
    {
        app()->singleton($this::class);
    }

    /**
     * Registers a callback for a specific event.
     *
     * @param string $name     The name of the event.
     * @param callable $callback The callback to be executed when the event occurs.
     * @return callable A closure that can be used to unregister the callback.
     */
    public function on($name, $callback)
    {
        if (!isset($this->listeners[$name])) $this->listeners[$name] = [];

        $this->listeners[$name][] = $callback;

        return fn () => $this->off($name, $callback);
    }

    /**
     * Registers a callback to be executed before the main event.
     *
     * @param string $name     The name of the event.
     * @param callable $callback The callback to be executed before the main event.
     * @return callable A closure that can be used to unregister the callback.
     */
    public function before($name, $callback)
    {
        if (!isset($this->listenersBefore[$name])) $this->listenersBefore[$name] = [];

        $this->listenersBefore[$name][] = $callback;

        return fn () => $this->off($name, $callback);
    }

    /**
     * Registers a callback to be executed after the main event.
     *
     * @param string $name     The name of the event.
     * @param callable $callback The callback to be executed after the main event.
     * @return callable A closure that can be used to unregister the callback.
     */
    public function after($name, $callback)
    {
        if (!isset($this->listenersAfter[$name])) $this->listenersAfter[$name] = [];

        $this->listenersAfter[$name][] = $callback;

        return fn () => $this->off($name, $callback);
    }

    /**
     * Unregisters a callback for a specific event.
     *
     * @param string $name     The name of the event.
     * @param callable $callback The callback to be unregistered.
     */
    public function off($name, $callback)
    {
        $index = array_search($callback, $this->listeners[$name] ?? []);
        $indexAfter = array_search($callback, $this->listenersAfter[$name] ?? []);
        $indexBefore = array_search($callback, $this->listenersBefore[$name] ?? []);

        if ($index !== false) unset($this->listeners[$name][$index]);
        elseif ($indexAfter !== false) unset($this->listenersAfter[$name][$indexAfter]);
        elseif ($indexBefore !== false) unset($this->listenersBefore[$name][$indexBefore]);
    }

    /**
     * Triggers a specific event, invoking registered callbacks and returning a middleware closure.
     *
     * @param string $name     The name of the event.
     * @param mixed  $params   The parameters to be passed to the event callbacks.
     * @return callable A middleware closure to be used in subsequent operations.
     */
    public function trigger($name, ...$params)
    {
        $middlewares = [];
        $listeners = array_merge(
            ($this->listenersBefore[$name] ?? []),
            ($this->listeners[$name] ?? []),
            ($this->listenersAfter[$name] ?? [])
        );

        foreach ($listeners as $callback) {
            $result = $callback(...$params);

            if ($result) {
                $middlewares[] = $result;
            }
        }

        return function (&$forward = null, ...$extras) use ($middlewares) {
            foreach ($middlewares as $finisher) {
                if ($finisher === null) continue;

                $finisher = is_array($finisher) ? last($finisher) : $finisher;

                $result = $finisher($forward, ...$extras);

                // Only overwrite previous "forward" if something is returned from the callback.
                $forward = $result ?? $forward;
            }

            return $forward;
        };
    }
}
