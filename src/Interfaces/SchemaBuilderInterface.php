<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * Interface for schema building operations
 */
interface SchemaBuilderInterface
{
    /**
     * Create a new schema builder instance
     */
    public function __construct(DatabaseInterface $db);

    /**
     * Integer column methods
     */
    public function integer(string $name, bool $unsigned = false, bool $autoIncrement = false): self;
    public function bigInteger(string $name, bool $unsigned = false): self;
    public function mediumInteger(string $name, bool $unsigned = false): self;
    public function smallInteger(string $name, bool $unsigned = false): self;
    public function tinyInteger(string $name, bool $unsigned = false): self;
    public function increments(string $name): self;
    public function bigIncrements(string $name): self;

    /**
     * String column methods
     */
    public function string(string $name, int $length = 255): self;
    public function text(string $name): self;
    public function char(string $name, int $length = 1): self;
    public function binary(string $name, ?int $length = null): self;

    /**
     * Date/Time column methods
     */
    public function datetime(string $name): self;
    public function timestamp(string $name, bool $onUpdate = false): self;
    public function date(string $name): self;
    public function time(string $name, int $precision = 0): self;
    public function year(string $name): self;

    /**
     * Numeric column methods
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): self;
    public function float(string $name, int $precision = 8, int $scale = 2): self;
    public function double(string $name, int $precision = 15, int $scale = 8): self;

    /**
     * Special column methods
     */
    public function boolean(string $name): self;
    public function json(string $name): self;
    public function enum(string $name, array $values): self;
    public function uuid(string $name): self;
    public function ulid(string $name): self;
    public function ipAddress(string $name): self;
    public function macAddress(string $name): self;
    public function morphs(string $name): self;
    public function nullableMorphs(string $name): self;

    /**
     * Unsigned integer methods
     */
    public function unsignedBigInteger(string $name): self;
    public function unsignedInteger(string $name): self;
    public function unsignedMediumInteger(string $name): self;
    public function unsignedSmallInteger(string $name): self;
    public function unsignedTinyInteger(string $name): self;

    /**
     * Special methods
     */
    public function rememberToken(): self;
    public function softDeletesTz(): self;
    public function timestamps(): self;
    public function softDeletes(): self;

    /**
     * Column modifiers
     */
    public function nullable(): self;
    public function default($value): self;
    public function unsigned(): self;
    public function after(string $column): self;
    public function first(): self;
    public function comment(string $comment): self;
    public function columnCharset(string $charset): self;
    public function columnCollation(string $collation): self;
    public function autoIncrement(): self;
    public function useCurrent(): self;
    public function useCurrentOnUpdate(): self;
    public function invisible(): self;
    public function unique(): self;

    /**
     * Index methods
     */
    public function primaryKey(string|array $columns): self;
    public function uniqueIndex(string|array $columns, ?string $name = null): self;
    public function index(string|array $columns, ?string $name = null): self;
    public function foreignKey(string $column, string $referenceTable, string $referenceColumn): self;

    /**
     * Idempotent index methods (safe for repeated execution)
     */
    public function hasIndex(string $tableName, string $indexName): bool;
    public function hasIndexByColumns(string $tableName, array $columns): bool;
    public function getIndexes(string $tableName): array;
    public function addIndexIfNotExists(string $tableName, array $columns, ?string $name = null, bool $unique = false): bool;
    public function addUniqueIndexIfNotExists(string $tableName, array $columns, ?string $name = null): bool;
    public function addForeignKeyIfNotExists(
        string $tableName, 
        string $column, 
        string $referenceTable, 
        string $referenceColumn, 
        ?string $name = null,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): bool;
    public function addColumnIfNotExists(string $tableName, string $columnName, array $definition): bool;
    public function createTableIfNotExists(string $tableName, callable $callback): bool;

    /**
     * Table options
     */
    public function engine(string $engine): self;
    public function charset(string $charset): self;
    public function collation(string $collation): self;
    public function ifNotExists(): self;
    public function strict(): self;

    /**
     * Table operations
     */
    public function createTable(string $tableName): bool;
    public function dropTable(string $tableName): bool;
    public function hasTable(string $tableName): bool;
    public function hasColumn(string $tableName, string $columnName): bool;
    public function addColumn(string $tableName, string $columnName, array $definition): bool;
    public function dropColumn(string $tableName, string $columnName): bool;
    public function modifyColumn(string $tableName, string $columnName, array $definition): bool;

    /**
     * Utility methods
     */
    public function reset(): self;
    public function buildColumnDefinition(string $name, array $column): string;
    public function generateCreateTableSql(string $tableName): string;
    public function table(string $tableName): \SimpleMDB\TableAlter;
} 