<?php

namespace SimpleMDB\DatabaseObjects\Interfaces;

/**
 * Interface for database events
 */
interface EventInterface extends DatabaseObjectInterface
{
    /**
     * Set the event schedule
     */
    public function schedule(string $schedule): self;

    /**
     * Set one-time schedule
     */
    public function at(string $datetime): self;

    /**
     * Set recurring schedule
     */
    public function every(string $interval): self;
    public function everyStarting(string $interval, string $startTime): self;
    public function everyEnding(string $interval, string $endTime): self;
    public function everyBetween(string $interval, string $startTime, string $endTime): self;

    /**
     * Set the event body
     */
    public function body(string $body): self;

    /**
     * Set event status
     */
    public function enable(): self;
    public function disable(): self;
    public function disableOnSlave(): self;

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
     * Execute the event manually
     */
    public function execute(): bool;

    /**
     * Get event definition
     */
    public function getDefinition(): ?string;

    /**
     * Get event schedule
     */
    public function getSchedule(): ?string;

    /**
     * Get event status
     */
    public function getStatus(): ?string;

    /**
     * Alter the event status
     */
    public function alterStatus(string $status): bool;
    public function alterEnable(): bool;
    public function alterDisable(): bool;

    /**
     * Alter event schedule
     */
    public function alterSchedule(string $schedule): bool;

    /**
     * Alter event body
     */
    public function alterBody(string $body): bool;

    /**
     * Rename the event
     */
    public function rename(string $newName): bool;

    /**
     * Get event information
     */
    public function getInfo(): ?array;

    /**
     * Get next execution time
     */
    public function getNextExecution(): ?string;

    /**
     * Get last execution time
     */
    public function getLastExecution(): ?string;
} 