<?php

namespace SimpleMDB\Interfaces;

/**
 * CaseBuilderInterface
 * 
 * Defines the contract for CASE statement builders across different database engines.
 * Each database engine can implement this interface with its specific CASE syntax.
 */
interface CaseBuilderInterface
{
    /**
     * Start a CASE expression
     */
    public function case(string $column = null): self;

    /**
     * Add a WHEN condition
     */
    public function when(string $condition, $value): self;

    /**
     * Expressive condition builders
     */
    public function whenEquals(string $column, $value, $result): self;
    public function whenNotEquals(string $column, $value, $result): self;
    public function whenGreaterThan(string $column, $value, $result): self;
    public function whenLessThan(string $column, $value, $result): self;
    public function whenBetween(string $column, $min, $max, $result): self;
    public function whenIn(string $column, array $values, $result): self;
    public function whenLike(string $column, string $pattern, $result): self;
    public function whenNull(string $column, $result): self;
    public function whenNotNull(string $column, $result): self;
    public function whenValue($value, $result): self;
    public function whenAny(array $conditions, $result): self;
    public function whenAll(array $conditions, $result): self;

    /**
     * Add an ELSE clause
     */
    public function else($value): self;

    /**
     * Expressive else methods
     */
    public function elseNull(): self;
    public function elseValue($value): self;
    public function elseColumn(string $column): self;

    /**
     * End the CASE expression
     */
    public function end(string $alias = null): string;

    /**
     * Create a simple CASE expression
     */
    public static function simple(string $column): self;

    /**
     * Create a searched CASE expression
     */
    public static function searched(): self;

    /**
     * Reset the case builder
     */
    public function reset(): self;

    /**
     * Get the generated CASE SQL
     */
    public function toSql(): string;

    /**
     * Get the bindings for the CASE statement
     */
    public function getBindings(): array;
}
