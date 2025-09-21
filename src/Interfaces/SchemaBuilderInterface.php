<?php

namespace SimpleMDB\Interfaces;

/**
 * SchemaBuilderInterface
 * 
 * Defines the contract for database schema builders across different database engines.
 * Each database engine can implement this interface with its specific syntax and features.
 */
interface SchemaBuilderInterface
{
    /**
     * Create a new integer column
     */
    public function integer(string $name, bool $unsigned = false, bool $autoIncrement = false): self;

    /**
     * Create a new big integer column
     */
    public function bigInteger(string $name, bool $unsigned = false): self;

    /**
     * Create a new small integer column
     */
    public function smallInteger(string $name, bool $unsigned = false): self;

    /**
     * Create a new tiny integer column
     */
    public function tinyInteger(string $name, bool $unsigned = false): self;

    /**
     * Create a new medium integer column
     */
    public function mediumInteger(string $name, bool $unsigned = false): self;

    /**
     * Create auto-incrementing integer primary key
     */
    public function increments(string $name = 'id'): self;

    /**
     * Create auto-incrementing big integer primary key
     */
    public function bigIncrements(string $name = 'id'): self;

    /**
     * Create a new string column
     */
    public function string(string $name, int $length = 255): self;

    /**
     * Create a new char column
     */
    public function char(string $name, int $length = 1): self;

    /**
     * Create a new text column
     */
    public function text(string $name): self;

    /**
     * Create a new medium text column
     */
    public function mediumText(string $name): self;

    /**
     * Create a new long text column
     */
    public function longText(string $name): self;

    /**
     * Create a new boolean column
     */
    public function boolean(string $name): self;

    /**
     * Create a new date column
     */
    public function date(string $name): self;

    /**
     * Create a new datetime column
     */
    public function dateTime(string $name): self;

    /**
     * Create a new time column
     */
    public function time(string $name, int $precision = 0): self;

    /**
     * Create a new timestamp column
     */
    public function timestamp(string $name): self;

    /**
     * Add created_at and updated_at timestamp columns
     */
    public function timestamps(): self;

    /**
     * Create a new decimal column
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): self;

    /**
     * Create a new float column
     */
    public function float(string $name, int $precision = 8, int $scale = 2): self;

    /**
     * Create a new double column
     */
    public function double(string $name, int $precision = 8, int $scale = 2): self;

    /**
     * Create a new JSON column
     */
    public function json(string $name): self;

    /**
     * Create a new UUID column
     */
    public function uuid(string $name): self;

    /**
     * Create a new IP address column
     */
    public function ipAddress(string $name): self;

    /**
     * Create a new MAC address column
     */
    public function macAddress(string $name): self;

    /**
     * Create a new binary column
     */
    public function binary(string $name, int $length = 255): self;

    /**
     * Create a new enum column
     */
    public function enum(string $name, array $values): self;

    /**
     * Create a new set column
     */
    public function set(string $name, array $values): self;

    /**
     * Make the column nullable
     */
    public function nullable(bool $nullable = true): self;

    /**
     * Set a default value for the column
     */
    public function default($value): self;

    /**
     * Make the column unique
     */
    public function unique(string $indexName = null): self;

    /**
     * Add an index to the column
     */
    public function index(string $indexName = null): self;

    /**
     * Add a comment to the column
     */
    public function comment(string $comment): self;

    /**
     * Set the column to be unsigned (for numeric types)
     */
    public function unsigned(): self;

    /**
     * Create a primary key
     */
    public function primary(array $columns): self;

    /**
     * Create a foreign key constraint
     */
    public function foreign(string $column): ForeignKeyDefinitionInterface;

    /**
     * Create the table
     */
    public function createTable(string $tableName): bool;

    /**
     * Drop a table
     */
    public function dropTable(string $tableName): bool;

    /**
     * Check if a table exists
     */
    public function hasTable(string $tableName): bool;

    /**
     * Check if a column exists
     */
    public function hasColumn(string $tableName, string $columnName): bool;

    /**
     * Reset the schema builder state
     */
    public function reset(): self;

    /**
     * Set IF NOT EXISTS clause
     */
    public function ifNotExists(): self;

    /**
     * Add soft deletes (deleted_at timestamp)
     */
    public function softDeletes(): self;

    /**
     * Create remember token column (for authentication)
     */
    public function rememberToken(): self;

    /**
     * Create polymorphic columns (for polymorphic relationships)
     */
    public function morphs(string $name): self;

    /**
     * Get table information
     */
    public function getTableInfo(string $tableName): array;
}