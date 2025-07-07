<?php

// Auto-created polyfill for tooling environments where psr/log isn't installed yet.
// Safely skipped at runtime if the real interfaces already exist.

namespace Psr\Log;

if (!interface_exists(LoggerInterface::class)) {
    /**
     * Minimal PSR-3 LoggerInterface stub (level and context are mixed for simplicity).
     */
    interface LoggerInterface {
        public function log($level, string $message, array $context = []): void;
    }
}

if (!class_exists(NullLogger::class)) {
    /**
     * Drop-in replacement that discards everything – matches semantics of real NullLogger.
     */
    class NullLogger implements LoggerInterface {
        public function log($level, string $message, array $context = []): void {}
    }
} 