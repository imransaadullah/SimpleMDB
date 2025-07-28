<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SchemaBuilder;

/**
 * Interface for table alteration operations
 */
interface TableAlterInterface
{
    /**
     * Create a new table alter instance
     */
    public function __construct(DatabaseInterface $db, SchemaBuilder $builder, string $tableName);

    /**
     * Add column operations
     */
    public function addColumn(string $name, array $definition): self;
    public function addInteger(string $name, bool $unsigned = false, bool $autoIncrement = false): self;
    public function addString(string $name, int $length = 255): self;
    public function addText(string $name): self;
    public function addDateTime(string $name): self;
    public function addTimestamp(string $name, bool $onUpdate = false): self;
    public function addDecimal(string $name, int $precision = 8, int $scale = 2): self;
    public function addBoolean(string $name): self;
    public function addJson(string $name): self;
    public function addEnum(string $name, array $values): self;

    /**
     * Modify column operations
     */
    public function modifyColumn(string $name, array $definition): self;
    public function changeColumn(string $oldName, string $newName, array $definition): self;
    public function renameColumn(string $oldName, string $newName): self;

    /**
     * Drop column operations
     */
    public function dropColumn(string $name): self;
    public function dropColumns(array $names): self;

    /**
     * Index operations
     */
    public function addIndex(array $columns, ?string $name = null): self;
    public function addUniqueIndex(array $columns, ?string $name = null): self;
    public function addPrimaryKey(array $columns): self;
    public function dropIndex(string $name): self;
    public function dropUniqueIndex(string $name): self;
    public function dropPrimaryKey(): self;

    /**
     * Foreign key operations
     */
    public function addForeignKey(string $column, string $referenceTable, string $referenceColumn, ?string $name = null): self;
    public function dropForeignKey(string $name): self;

    /**
     * Table options
     */
    public function setEngine(string $engine): self;
    public function setCharset(string $charset): self;
    public function setCollation(string $collation): self;
    public function setComment(string $comment): self;

    /**
     * Column modifiers
     */
    public function nullable(): self;
    public function notNull(): self;
    public function default($value): self;
    public function unsigned(): self;
    public function autoIncrement(): self;
    public function comment(string $comment): self;
    public function after(string $column): self;
    public function first(): self;

    /**
     * Execute the alteration
     */
    public function execute(): bool;

    /**
     * Generate SQL for the alteration
     */
    public function toSql(): string;

    /**
     * Get table name
     */
    public function getTableName(): string;

    /**
     * Get database connection
     */
    public function getDatabase(): DatabaseInterface;

    /**
     * Check if table exists
     */
    public function tableExists(): bool;

    /**
     * Get table structure
     */
    public function getTableStructure(): array;

    /**
     * Get column information
     */
    public function getColumnInfo(string $columnName): ?array;
} 