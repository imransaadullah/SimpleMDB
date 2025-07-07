<?php

namespace SimpleMDB\Exceptions;

/**
 * Exception thrown when SQL query execution fails
 */
class QueryException extends SimpleMDBException
{
    public static function syntaxError(string $sql, array $params = [], string $error = ''): self
    {
        return (new self(
            "SQL syntax error: $error",
            2001
        ))->withSql($sql, $params);
    }

    public static function executionFailed(string $sql, array $params = [], string $error = ''): self
    {
        return (new self(
            "Query execution failed: $error",
            2002
        ))->withSql($sql, $params);
    }

    public static function timeout(string $sql, array $params = [], int $timeoutSeconds = 0): self
    {
        return (new self(
            "Query timeout after $timeoutSeconds seconds",
            2003
        ))->withSql($sql, $params)->withContext(['timeout' => $timeoutSeconds]);
    }

    public static function deadlock(string $sql, array $params = []): self
    {
        return (new self(
            "Deadlock detected during query execution",
            2004
        ))->withSql($sql, $params);
    }

    public static function lockTimeout(string $sql, array $params = []): self
    {
        return (new self(
            "Lock wait timeout exceeded",
            2005
        ))->withSql($sql, $params);
    }

    public static function tableNotFound(string $tableName, string $sql = '', array $params = []): self
    {
        return (new self(
            "Table '$tableName' doesn't exist",
            2006
        ))->withSql($sql, $params)->withContext(['table' => $tableName]);
    }

    public static function columnNotFound(string $columnName, string $tableName = '', string $sql = '', array $params = []): self
    {
        return (new self(
            "Column '$columnName' not found" . ($tableName ? " in table '$tableName'" : ''),
            2007
        ))->withSql($sql, $params)->withContext(['column' => $columnName, 'table' => $tableName]);
    }

    public static function constraintViolation(string $constraint, string $sql = '', array $params = []): self
    {
        return (new self(
            "Constraint violation: $constraint",
            2008
        ))->withSql($sql, $params)->withContext(['constraint' => $constraint]);
    }

    public static function duplicateEntry(string $key, string $sql = '', array $params = []): self
    {
        return (new self(
            "Duplicate entry for key '$key'",
            2009
        ))->withSql($sql, $params)->withContext(['key' => $key]);
    }
} 