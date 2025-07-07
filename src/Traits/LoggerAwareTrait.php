<?php

namespace SimpleMDB\Traits;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerAwareTrait
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    protected function getLogger(): LoggerInterface
    {
        if (!isset($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $this->getLogger()->log($level, $message, $context);
    }
} 