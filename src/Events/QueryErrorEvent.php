<?php

namespace SimpleMDB\Events;

class QueryErrorEvent
{
    public function __construct(
        public string $sql,
        public array $params,
        public \Throwable $error
    ) {}
} 