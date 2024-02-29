<?php

namespace Raxm;

use Raxm\Event;

/**
 * The ReceivesEvents trait provides functionality for emitting,
 * dispatching, and listening to events in a component.
 */
trait ReceivesEvents
{
    /**
     * The queue of events to be emitted.
     * @var array
     */
    protected $eventQueue = [];

    /**
     * The queue of browser events to be dispatched.
     * @var array
     */
    protected $dispatchQueue = [];

    /**
     * The array of event listeners.
     * @var array
     */
    protected $listeners = [];

    /**
     * Get the array of event listeners.
     * @return array
     */
    protected function getListeners()
    {
        return $this->listeners;
    }

    /**
     * Emit a new event to be added to the event queue.
     *
     * @param string $event  The name of the event.
     * @param mixed  ...$params The parameters to be passed to the event.
     * @return Event The created Event instance.
     */
    public function emit($event, ...$params)
    {
        return $this->eventQueue[] = new Event($event, $params);
    }

    /**
     * Emit an event with the "up" modifier to propagate the event to ancestor components only.
     *
     * @param string $event  The name of the event.
     * @param mixed  ...$params The parameters to be passed to the event.
     * @return void
     */
    public function emitUp($event, ...$params)
    {
        $this->emit($event, ...$params)->up();
    }

    /**
     * Emit an event with the "self" modifier to handle the event only within the current component.
     *
     * @param string $event  The name of the event.
     * @param mixed  ...$params The parameters to be passed to the event.
     * @return void
     */
    public function emitSelf($event, ...$params)
    {
        $this->emit($event, ...$params)->self();
    }

    /**
     * Emit an event to a specific component identified by name.
     *
     * @param string $name   The name of the target component.
     * @param string $event  The name of the event.
     * @param mixed  ...$params The parameters to be passed to the event.
     * @return void
     */
    public function emitTo($name, $event, ...$params)
    {
        $this->emit($event, ...$params)->component($name);
    }

    /**
     * Queue a browser event for dispatching.
     *
     * @param string $event The name of the browser event.
     * @param mixed  $data  The data to be dispatched with the event.
     * @return void
     */
    public function dispatchBrowserEvent($event, $data = null)
    {
        $this->dispatchQueue[] = [
            'event' => $event,
            'data'  => $data,
        ];
    }

    /**
     * Get the array of events in the event queue.
     * @return array The array of serialized events.
     */
    public function getEventQueue()
    {
        $serializedEvents = array_map(function ($event) {
            return $event->serialize();
        }, $this->eventQueue);

        return array_values($serializedEvents);
    }

    /**
     * Get the array of browser events in the dispatch queue.
     * @return array The array of browser events to be dispatched.
     */
    public function getDispatchQueue()
    {
        return $this->dispatchQueue;
    }

    /**
     * Get the array of events and their associated handlers.
     * @return array The array of events and handlers.
     */
    protected function getEventsAndHandlers()
    {
        $listeners = $this->getListeners();
        $eventsAndHandlers = [];

        foreach ($listeners as $key => $value) {
            $key = is_numeric($key) ? $value : $key;
            $eventsAndHandlers[$key] = $value;
        }

        return $eventsAndHandlers;
    }

    /**
     * Get the array of events being listened for.
     * @return array The array of events being listened for.
     */
    public function getEventsBeingListenedFor()
    {
        return array_keys($this->getEventsAndHandlers());
    }

    /**
     * Fire a specified event with parameters and an identifier.
     *
     * @param string $event  The name of the event.
     * @param mixed  $params The parameters to be passed to the event.
     * @param int    $id     The identifier for the event.
     * @return void
     */
    public function fireEvent($event, $params, $id)
    {
        $method = $this->getEventsAndHandlers()[$event];

        $this->callMethod($method, $params, function ($returned) use ($event, $id) {
            $this->dispatch('action.returned', $this, $event, $returned, $id);
        });
    }

    /**
     * Dispatch a specified event with parameters.
     *
     * @param string $event  The name of the event.
     * @param mixed  ...$params The parameters to be passed to the event.
     * @return void
     */
    public function dispatch($event, ...$params)
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$params);
        }
    }

    /**
     * Listen for a specified event with a callback.
     *
     * @param string $event    The name of the event.
     * @param mixed  $callback The callback to be executed when the event occurs.
     * @return void
     */
    public function listen($event, $callback)
    {
        $this->listeners[$event][] = $callback;
    }
}
