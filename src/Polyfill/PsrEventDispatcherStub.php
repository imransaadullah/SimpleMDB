<?php

namespace Psr\EventDispatcher;

if (!interface_exists(EventDispatcherInterface::class)) {
    interface EventDispatcherInterface {
        /**
         * @return object The passed event (or replacement)
         */
        public function dispatch(object $event): object;
    }
} 