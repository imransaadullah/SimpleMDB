<?php

namespace SimpleMDB\Events;

class BeforeQueryEvent
{
    public function __construct(
        public string $sql,
        public array $params
    ) {}
} 