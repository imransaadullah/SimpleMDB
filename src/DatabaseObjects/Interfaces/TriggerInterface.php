<?php

namespace SimpleMDB\DatabaseObjects\Interfaces;

/**
 * Interface for database triggers
 */
interface TriggerInterface extends DatabaseObjectInterface
{
    /**
     * Set trigger timing
     */
    public function before(): self;
    public function after(): self;

    /**
     * Set trigger event
     */
    public function insert(): self;
    public function update(): self;
    public function delete(): self;

    /**
     * Set the table name
     */
    public function on(string $table): self;

    /**
     * Set the trigger body
     */
    public function body(string $body): self;

    /**
     * Set the definer
     */
    public function definer(string $definer): self;

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
     * Get trigger definition
     */
    public function getDefinition(): ?string;

    /**
     * Get trigger information
     */
    public function getInfo(): ?array;

    /**
     * Get all triggers for a table
     */
    public static function getTableTriggers(\SimpleMDB\DatabaseInterface $db, string $tableName): array;

    /**
     * Enable the trigger
     */
    public function enable(): bool;

    /**
     * Disable the trigger
     */
    public function disable(): bool;

    /**
     * Check if trigger is enabled
     */
    public function isEnabled(): bool;

    /**
     * Get trigger timing (BEFORE/AFTER)
     */
    public function getTiming(): ?string;

    /**
     * Get trigger event (INSERT/UPDATE/DELETE)
     */
    public function getEvent(): ?string;

    /**
     * Get trigger table
     */
    public function getTable(): ?string;

    /**
     * Rename the trigger
     */
    public function rename(string $newName): bool;

    /**
     * Alter trigger definer
     */
    public function alterDefiner(string $definer): bool;

    /**
     * Alter trigger comment
     */
    public function alterComment(string $comment): bool;

    /**
     * Test the trigger (simulate the event)
     */
    public function test(array $testData = []): bool;
} 