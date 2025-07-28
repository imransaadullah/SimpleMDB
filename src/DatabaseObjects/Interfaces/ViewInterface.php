<?php

namespace SimpleMDB\DatabaseObjects\Interfaces;

/**
 * Interface for database views
 */
interface ViewInterface extends DatabaseObjectInterface
{
    /**
     * Set the SELECT query for the view
     */
    public function select(string $query): self;

    /**
     * Set specific columns for the view
     */
    public function columns(array $columns): self;

    /**
     * Set view algorithm
     */
    public function algorithm(string $algorithm): self;
    public function undefined(): self;
    public function merge(): self;
    public function temptable(): self;

    /**
     * Set security context
     */
    public function definer(string $definer = ''): self;
    public function invoker(): self;

    /**
     * Add a comment
     */
    public function comment(string $comment): self;

    /**
     * Use IF NOT EXISTS
     */
    public function ifNotExists(): self;

    /**
     * Use OR REPLACE
     */
    public function orReplace(): self;

    /**
     * Get view definition
     */
    public function getDefinition(): ?string;

    /**
     * Update the view (drop and recreate)
     */
    public function update(): bool;

    /**
     * Execute a query against the view
     */
    public function query(string $sql = '', array $parameters = []): mixed;

    /**
     * Select from the view
     */
    public function selectFrom(array $columns = ['*'], array $where = [], array $orderBy = [], int $limit = null): mixed;

    /**
     * Get view columns
     */
    public function getColumns(): array;

    /**
     * Check if view is updatable
     */
    public function isUpdatable(): bool;

    /**
     * Get view information
     */
    public function getInfo(): ?array;

    /**
     * Rename the view
     */
    public function rename(string $newName): bool;

    /**
     * Alter view algorithm
     */
    public function alterAlgorithm(string $algorithm): bool;

    /**
     * Alter view definer
     */
    public function alterDefiner(string $definer): bool;
} 