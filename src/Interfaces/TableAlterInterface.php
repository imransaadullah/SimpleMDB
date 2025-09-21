<?php

namespace SimpleMDB\Interfaces;

/**
 * TableAlterInterface
 * 
 * Defines the contract for table alteration across different database engines.
 * Each database engine can implement this interface with its specific ALTER TABLE syntax.
 */
interface TableAlterInterface
{
    /**
     * Add a new column to the table
     */
    public function addColumn(string $columnName, string $type, array $options = []): self;

    /**
     * Modify an existing column
     */
    public function modifyColumn(string $columnName, string $type, array $options = []): self;

    /**
     * Drop a column from the table
     */
    public function dropColumn(string $columnName): self;

    /**
     * Rename a column
     */
    public function renameColumn(string $oldName, string $newName): self;

    /**
     * Add an index
     */
    public function addIndex(string $indexName, array $columns, string $type = 'INDEX'): self;

    /**
     * Drop an index
     */
    public function dropIndex(string $indexName): self;

    /**
     * Add a primary key
     */
    public function addPrimaryKey(array $columns): self;

    /**
     * Drop the primary key
     */
    public function dropPrimaryKey(): self;

    /**
     * Add a foreign key
     */
    public function addForeignKey(string $column, string $referencedTable, string $referencedColumn, array $options = []): self;

    /**
     * Drop a foreign key
     */
    public function dropForeignKey(string $constraintName): self;

    /**
     * Add a unique constraint
     */
    public function addUnique(array $columns, string $constraintName = null): self;

    /**
     * Drop a unique constraint
     */
    public function dropUnique(string $constraintName): self;

    /**
     * Rename the table
     */
    public function renameTable(string $newName): self;

    /**
     * Change table engine (MySQL specific)
     */
    public function engine(string $engine): self;

    /**
     * Change table charset (MySQL specific)
     */
    public function charset(string $charset): self;

    /**
     * Change table collation (MySQL specific)
     */
    public function collation(string $collation): self;

    /**
     * Add a check constraint
     */
    public function addCheck(string $constraintName, string $condition): self;

    /**
     * Drop a check constraint
     */
    public function dropCheck(string $constraintName): self;

    /**
     * Execute the alterations
     */
    public function execute(): bool;

    /**
     * Get the generated SQL statements
     */
    public function toSql(): array;

    /**
     * Reset the alter builder
     */
    public function reset(): self;
}