<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * Interface for batch operations
 */
interface BatchOperationsInterface
{
    /**
     * Create a new batch operations instance
     */
    public function __construct(DatabaseInterface $db, int $batchSize = 1000);

    /**
     * Batch insert operations
     */
    public function batchInsert(string $table, array $columns, array $records): array;

    /**
     * Batch update operations
     */
    public function batchUpdate(string $table, array $data, array $conditions, array $records): array;

    /**
     * Batch delete operations
     */
    public function batchDelete(string $table, array $conditions): array;

    /**
     * Upsert operations (INSERT ... ON DUPLICATE KEY UPDATE)
     */
    public function upsert(string $table, array $columns, array $records, array $uniqueColumns): array;

    /**
     * Configuration methods
     */
    public function setBatchSize(int $size): self;

    /**
     * Transaction support
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get current batch size
     */
    public function getBatchSize(): int;

    /**
     * Get database connection
     */
    public function getDatabase(): DatabaseInterface;
} 