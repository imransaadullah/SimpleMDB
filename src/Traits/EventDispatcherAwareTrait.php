<?php

namespace SimpleMDB\Traits;

use Psr\EventDispatcher\EventDispatcherInterface;

trait EventDispatcherAwareTrait
{
    private ?EventDispatcherInterface $dispatcher = null;

    public function setEventDispatcher(EventDispatcherInterface $dispatcher): static
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    protected function dispatchEvent(object $event): void
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch($event);
        }
    }
} 