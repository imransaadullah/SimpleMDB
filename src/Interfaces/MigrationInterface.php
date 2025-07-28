<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * Interface for database migration operations
 */
interface MigrationInterface
{
    /**
     * Create a new migration instance
     */
    public function __construct(DatabaseInterface $db);

    /**
     * Run the migration (up)
     */
    public function up(): void;

    /**
     * Reverse the migration (down)
     */
    public function down(): void;

    /**
     * Get migration name/identifier
     */
    public function getName(): string;

    /**
     * Get migration version (timestamp)
     */
    public function getVersion(): string;

    /**
     * Get migration description
     */
    public function getDescription(): string;

    /**
     * Check if migration can be reversed
     */
    public function isReversible(): bool;

    /**
     * Get database connection name
     */
    public function getConnection(): string;

    /**
     * Set database connection name
     */
    public function setConnection(string $connection): self;

    /**
     * Check if migration has been run
     */
    public function hasRun(): bool;

    /**
     * Mark migration as run
     */
    public function markAsRun(): void;

    /**
     * Mark migration as rolled back
     */
    public function markAsRolledBack(): void;

    /**
     * Get migration execution time
     */
    public function getExecutionTime(): float;

    /**
     * Get migration status
     */
    public function getStatus(): string;

    /**
     * Validate migration configuration
     */
    public function validate(): bool;

    /**
     * Get migration dependencies
     */
    public function getDependencies(): array;

    /**
     * Set migration dependencies
     */
    public function setDependencies(array $dependencies): self;

    /**
     * Check if migration has dependencies
     */
    public function hasDependencies(): bool;

    /**
     * Get migration batch number
     */
    public function getBatch(): int;

    /**
     * Set migration batch number
     */
    public function setBatch(int $batch): self;
} 