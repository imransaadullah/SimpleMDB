<?php

namespace SimpleMDB\Events;

class AfterQueryEvent
{
    public function __construct(
        public string $sql,
        public array $params,
        public float $time
    ) {}
} 