<?php

namespace SimpleMDB\DatabaseObjects\Interfaces;

/**
 * Interface for database functions
 */
interface FunctionInterface extends DatabaseObjectInterface
{
    /**
     * Add a parameter to the function
     */
    public function parameter(string $name, string $type, bool $isOut = false): self;

    /**
     * Add an IN parameter
     */
    public function inParameter(string $name, string $type): self;

    /**
     * Add an OUT parameter
     */
    public function outParameter(string $name, string $type): self;

    /**
     * Set the return type
     */
    public function returns(string $type): self;

    /**
     * Set the function body
     */
    public function body(string $body): self;

    /**
     * Set function characteristics
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
     * Execute the function with parameters
     */
    public function execute(array $parameters = []): mixed;

    /**
     * Call the function (alias for execute)
     */
    public function call(array $parameters = []): mixed;

    /**
     * Get function definition
     */
    public function getDefinition(): ?string;

    /**
     * Get function parameters
     */
    public function getParameters(): array;

    /**
     * Get function return type
     */
    public function getReturnType(): ?string;

    /**
     * Modify function characteristics
     */
    public function alterDeterministic(): bool;
    public function alterNotDeterministic(): bool;
    public function alterComment(string $comment): bool;

    /**
     * Rename the function
     */
    public function rename(string $newName): bool;

    /**
     * Get function information
     */
    public function getInfo(): ?array;
} 