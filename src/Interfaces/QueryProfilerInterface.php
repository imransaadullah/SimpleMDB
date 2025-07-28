<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SimpleQuery;

/**
 * Interface for query profiling operations
 */
interface QueryProfilerInterface
{
    /**
     * Create a new query profiler instance
     */
    public function __construct(DatabaseInterface $db);

    /**
     * Add query to profiler
     */
    public function addQuery(string $sql, array $params, float $executionTime, ?array $explainResult = null): void;

    /**
     * Explain query execution plan
     */
    public function explain(SimpleQuery $query): array;

    /**
     * Analyze query for performance issues
     */
    public function analyzeQuery(SimpleQuery $query): array;

    /**
     * Get query count
     */
    public function getQueryCount(): int;

    /**
     * Get total execution time
     */
    public function getTotalExecutionTime(): float;

    /**
     * Get average execution time
     */
    public function getAverageExecutionTime(): float;

    /**
     * Get maximum execution time
     */
    public function getMaxExecutionTime(): float;

    /**
     * Get peak memory usage
     */
    public function getPeakMemoryUsage(): int;

    /**
     * Get comprehensive profiling report
     */
    public function getReport(): array;

    /**
     * Check for full table scan
     */
    public function hasFullTableScan(array $explainResult): bool;

    /**
     * Check for temporary table usage
     */
    public function hasTemporaryTable(array $explainResult): bool;

    /**
     * Check for file sort usage
     */
    public function hasFileSort(array $explainResult): bool;

    /**
     * Generate optimization suggestions
     */
    public function generateOptimizationSuggestions(array $explainResult): array;

    /**
     * Get all queries
     */
    public function getQueries(): array;

    /**
     * Clear profiler data
     */
    public function clear(): void;

    /**
     * Get start time
     */
    public function getStartTime(): float;

    /**
     * Get end time
     */
    public function getEndTime(): float;

    /**
     * Get execution time range
     */
    public function getExecutionTimeRange(): array;
} 