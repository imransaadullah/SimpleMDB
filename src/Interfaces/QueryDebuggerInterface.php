<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SimpleQuery;

/**
 * Interface for query debugging operations
 */
interface QueryDebuggerInterface
{
    /**
     * Create a new query debugger instance
     */
    public function __construct(DatabaseInterface $db, ?string $logFile = null);

    /**
     * Enable/disable debugging
     */
    public function enable(): self;
    public function disable(): self;

    /**
     * Set custom formatter
     */
    public function setFormatter(callable $formatter): self;

    /**
     * Add query to debug log
     */
    public function addQuery(string $sql, array $params, float $executionTime, ?array $backtrace = null): void;

    /**
     * Get all logged queries
     */
    public function getQueries(): array;

    /**
     * Clear debug log
     */
    public function clear(): void;

    /**
     * Explain query execution plan
     */
    public function explainQuery(SimpleQuery $query): array;

    /**
     * Get query statistics
     */
    public function getQueryStats(): array;

    /**
     * Get slowest queries
     */
    public function getSlowestQueries(int $limit = 10): array;

    /**
     * Get duplicate queries
     */
    public function getDuplicateQueries(): array;

    /**
     * Get default formatter
     */
    public function getDefaultFormatter(): callable;

    /**
     * Check if debugging is enabled
     */
    public function isEnabled(): bool;

    /**
     * Get log file path
     */
    public function getLogFile(): ?string;
} 