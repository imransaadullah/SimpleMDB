<?php

namespace SimpleMDB\DatabaseObjects\Interfaces;

/**
 * Interface for database procedures
 */
interface ProcedureInterface extends DatabaseObjectInterface
{
    /**
     * Add a parameter to the procedure
     */
    public function parameter(string $name, string $type, string $direction = 'IN'): self;

    /**
     * Add an IN parameter
     */
    public function inParameter(string $name, string $type): self;

    /**
     * Add an OUT parameter
     */
    public function outParameter(string $name, string $type): self;

    /**
     * Add an INOUT parameter
     */
    public function inoutParameter(string $name, string $type): self;

    /**
     * Set the procedure body
     */
    public function body(string $body): self;

    /**
     * Set procedure characteristics
     */
    public function deterministic(): self;
    public function notDeterministic(): self;

    /**
     * Set SQL data access
     */
    public function containsSql(): self;
    public function noSql(): self;
    public function readsSqlData(): self;
    public function modifiesSqlData(): self;

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
     * Call the procedure
     */
    public function call(array $parameters = []): mixed;

    /**
     * Execute the procedure (alias for call)
     */
    public function execute(array $parameters = []): mixed;

    /**
     * Get procedure definition
     */
    public function getDefinition(): ?string;

    /**
     * Get procedure parameters
     */
    public function getParameters(): array;

    /**
     * Modify procedure characteristics
     */
    public function alterDeterministic(): bool;
    public function alterNotDeterministic(): bool;
    public function alterComment(string $comment): bool;

    /**
     * Rename the procedure
     */
    public function rename(string $newName): bool;

    /**
     * Get procedure information
     */
    public function getInfo(): ?array;

    /**
     * Get procedure results (for procedures with OUT parameters)
     */
    public function getResults(): array;
} 