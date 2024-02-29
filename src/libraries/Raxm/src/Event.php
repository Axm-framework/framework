<?php

namespace Raxm;

/**
 * The Event class represents an event in the Raxm framework.
 */
class Event
{
    /**
     * The name of the event.
     * @var string
     */
    protected $name;

    /**
     * The parameters associated with the event.
     * @var array
     */
    protected $params;

    /**
     * Flag indicating if the event should propagate up to ancestors only.
     * @var bool
     */
    protected $up;

    /**
     * Flag indicating if the event should be limited to the current component.
     * @var bool
     */
    protected $self;

    /**
     * The target component for the event.
     * @var string
     */
    protected $component;

    /**
     * Create a new Event instance.
     *
     * @param string $name    The name of the event.
     * @param array  $params  The parameters associated with the event.
     */
    public function __construct($name, $params)
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * Set the event to propagate up to ancestors only.
     * @return $this
     */
    public function up()
    {
        $this->up = true;
        return $this;
    }

    /**
     * Set the event to be limited to the current component.
     * @return $this
     */
    public function self()
    {
        $this->self = true;
        return $this;
    }

    /**
     * Set the target component for the event.
     *
     * @param string $name The name of the target component.
     * @return $this
     */
    public function component($name)
    {
        $this->component = $name;
        return $this;
    }

    /**
     * Specify the target for the event (no actual functionality).
     * @return $this
     */
    public function to()
    {
        return $this;
    }

    /**
     * Serialize the event to an array.
     * @return array The serialized event data.
     */
    public function serialize()
    {
        $output = [
            'event'  => $this->name,
            'params' => $this->params,
        ];

        if ($this->up) $output['ancestorsOnly'] = true;
        if ($this->self) $output['selfOnly'] = true;
        if ($this->component) $output['to'] = is_subclass_of($this->component, Component::class)
            ? $this->component->getName()
            : $this->component;

        return $output;
    }
}
