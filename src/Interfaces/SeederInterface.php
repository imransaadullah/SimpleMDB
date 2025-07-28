<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * Interface for database seeding operations
 */
interface SeederInterface
{
    /**
     * Create a new seeder instance
     */
    public function __construct(DatabaseInterface $db);

    /**
     * Run the seeder
     */
    public function run(): void;

    /**
     * Get seeder name
     */
    public function getName(): string;

    /**
     * Get table name this seeder populates
     */
    public function getTable(): string;

    /**
     * Get seeder dependencies
     */
    public function getDependencies(): array;

    /**
     * Whether to truncate table before seeding
     */
    public function shouldTruncateFirst(): bool;

    /**
     * Set dependencies
     */
    public function setDependencies(array $dependencies): self;

    /**
     * Set whether to truncate table first
     */
    public function setTruncateFirst(bool $truncate): self;

    /**
     * Set table name
     */
    public function setTable(string $table): self;

    /**
     * Check if seeder has been run
     */
    public function hasRun(): bool;

    /**
     * Mark seeder as run
     */
    public function markAsRun(): void;

    /**
     * Get seeder execution time
     */
    public function getExecutionTime(): float;

    /**
     * Get seeder status
     */
    public function getStatus(): string;

    /**
     * Validate seeder configuration
     */
    public function validate(): bool;
} 