<?php

namespace SimpleMDB\Exceptions;

/**
 * Exception thrown when schema operations fail
 */
class SchemaException extends SimpleMDBException
{
    public static function tableCreationFailed(string $tableName, string $error = ''): self
    {
        return new self(
            "Failed to create table '$tableName': $error",
            3001,
            null,
            ['table' => $tableName, 'operation' => 'create']
        );
    }

    public static function tableAlterationFailed(string $tableName, string $operation, string $error = ''): self
    {
        return new self(
            "Failed to alter table '$tableName' ($operation): $error",
            3002,
            null,
            ['table' => $tableName, 'operation' => $operation]
        );
    }

    public static function columnDefinitionInvalid(string $columnName, string $definition, string $error = ''): self
    {
        return new self(
            "Invalid column definition for '$columnName': $error",
            3003,
            null,
            ['column' => $columnName, 'definition' => $definition]
        );
    }

    public static function indexCreationFailed(string $indexName, string $tableName, string $error = ''): self
    {
        return new self(
            "Failed to create index '$indexName' on table '$tableName': $error",
            3004,
            null,
            ['index' => $indexName, 'table' => $tableName]
        );
    }

    public static function foreignKeyConstraintFailed(string $constraint, string $error = ''): self
    {
        return new self(
            "Foreign key constraint '$constraint' failed: $error",
            3005,
            null,
            ['constraint' => $constraint]
        );
    }

    public static function migrationFailed(string $migration, string $direction, string $error = ''): self
    {
        return new self(
            "Migration '$migration' failed during '$direction': $error",
            3006,
            null,
            ['migration' => $migration, 'direction' => $direction]
        );
    }

    public static function invalidMigrationState(string $migration, string $expectedState, string $actualState): self
    {
        return new self(
            "Migration '$migration' in invalid state. Expected: $expectedState, Actual: $actualState",
            3007,
            null,
            ['migration' => $migration, 'expected' => $expectedState, 'actual' => $actualState]
        );
    }
} 