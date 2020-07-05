<?php

namespace Storj\Uplink\Internal;

/**
 * @internal
 *
 * Helper to release resources after we're done with them.
 * This replaces more error-prone ways like try-finally and destructors.
 * This object must be kept alive as long as the associated resource is in use.
 * Inspired by the scope guard pattern by Andrei Alexandrescu
 */
class Scope
{
    /**
     * @var callable[]
     */
    private array $handlers = [];

    /**
     * Transfer handlers to a new scope
     */
    public static function merge(self ...$cleanups): self
    {
        $self = new self();
        foreach ($cleanups as $cleanup) {
            $self->handlers += array_merge($self->handlers, $cleanup->handlers);
            $cleanup->handlers = [];
        }
        return $self;
    }

    public static function exit(callable $handler): self
    {
        $self = new self();
        $self->onExit($handler);
        return $self;
    }

    public function onExit(callable $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Transfer handlers to another scope, relieving this object of its responsibilities
     */
    public function transfer(self $other): self
    {
        $other->handlers = array_merge($other->handlers, $this->handlers);
        $this->handlers = [];

        return $other;
    }

    public function __destruct()
    {
        foreach ($this->handlers as $handler) {
            $handler();
        }
    }
}
