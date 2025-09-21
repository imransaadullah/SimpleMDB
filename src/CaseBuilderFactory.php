<?php

namespace SimpleMDB;

use SimpleMDB\Interfaces\CaseBuilderInterface;
use SimpleMDB\CaseBuilder\MySQL\MySQLCaseBuilder;
use SimpleMDB\CaseBuilder\PostgreSQL\PostgreSQLCaseBuilder;
use SimpleMDB\CaseBuilder\MySQL\FluentMySQLCaseBuilder;
use SimpleMDB\CaseBuilder\PostgreSQL\FluentPostgreSQLCaseBuilder;

/**
 * CaseBuilderFactory
 * 
 * Factory class for creating database-specific CASE statement builders
 */
class CaseBuilderFactory
{
    /**
     * Create a CASE builder instance (auto-detects database type from connection)
     *
     * @param DatabaseInterface|null $db Database connection for auto-detection
     * @param string|null $type Database type (optional override)
     * @return CaseBuilderInterface
     */
    public static function create(?DatabaseInterface $db = null, ?string $type = null): CaseBuilderInterface
    {
        // Auto-detect from database connection (preferred approach)
        if ($db !== null && $type === null) {
            $type = self::detectDatabaseType($db);
        }
        
        // If no database connection provided, default to MySQL for backward compatibility
        if ($type === null) {
            $type = DatabaseFactory::TYPE_PDO;
        }

        return match ($type) {
            DatabaseFactory::TYPE_MYSQLI, DatabaseFactory::TYPE_PDO, 'mysql' => new MySQLCaseBuilder(),
            DatabaseFactory::TYPE_POSTGRESQL, DatabaseFactory::TYPE_PGSQL, 'postgresql', 'postgres' => new PostgreSQLCaseBuilder(),
            default => new MySQLCaseBuilder() // Fallback to MySQL
        };
    }

    /**
     * Create a MySQL-specific CASE builder
     */
    public static function createMySQL(): MySQLCaseBuilder
    {
        return new MySQLCaseBuilder();
    }

    /**
     * Create a PostgreSQL-specific CASE builder
     */
    public static function createPostgreSQL(): PostgreSQLCaseBuilder
    {
        return new PostgreSQLCaseBuilder();
    }

    /**
     * Create a fluent MySQL CASE builder
     */
    public static function fluent(?DatabaseInterface $db = null, ?string $type = null): FluentMySQLCaseBuilder|FluentPostgreSQLCaseBuilder
    {
        // Auto-detect from database connection (preferred approach)
        if ($db !== null && $type === null) {
            $type = self::detectDatabaseType($db);
        }
        
        // If no database connection provided, default to MySQL for backward compatibility
        if ($type === null) {
            $type = DatabaseFactory::TYPE_PDO;
        }

        return match ($type) {
            DatabaseFactory::TYPE_MYSQLI, DatabaseFactory::TYPE_PDO, 'mysql' => new FluentMySQLCaseBuilder(),
            DatabaseFactory::TYPE_POSTGRESQL, DatabaseFactory::TYPE_PGSQL, 'postgresql', 'postgres' => new FluentPostgreSQLCaseBuilder(),
            default => new FluentMySQLCaseBuilder() // Fallback to MySQL
        };
    }

    /**
     * Create a fluent MySQL CASE builder
     */
    public static function fluentMySQL(): FluentMySQLCaseBuilder
    {
        return new FluentMySQLCaseBuilder();
    }

    /**
     * Create a fluent PostgreSQL CASE builder
     */
    public static function fluentPostgreSQL(): FluentPostgreSQLCaseBuilder
    {
        return new FluentPostgreSQLCaseBuilder();
    }

    /**
     * Detect database type from connection instance
     */
    private static function detectDatabaseType(DatabaseInterface $db): string
    {
        $className = get_class($db);
        
        if (str_contains($className, 'PostgreSQL') || str_contains($className, 'Postgre')) {
            return DatabaseFactory::TYPE_POSTGRESQL;
        }
        
        if (str_contains($className, 'MySQLi')) {
            return DatabaseFactory::TYPE_MYSQLI;
        }
        
        if (str_contains($className, 'PDO')) {
            return DatabaseFactory::TYPE_PDO;
        }
        
        // Default to MySQL for backward compatibility
        return DatabaseFactory::TYPE_PDO;
    }

    /**
     * Get all available CASE builder types
     */
    public static function getAvailableTypes(): array
    {
        return [
            DatabaseFactory::TYPE_MYSQLI => 'MySQL via MySQLi',
            DatabaseFactory::TYPE_PDO => 'MySQL via PDO',
            DatabaseFactory::TYPE_POSTGRESQL => 'PostgreSQL',
            DatabaseFactory::TYPE_PGSQL => 'PostgreSQL (alias)'
        ];
    }

    /**
     * Check if a database type is supported for CASE building
     */
    public static function isSupported(string $type): bool
    {
        return in_array($type, [
            DatabaseFactory::TYPE_MYSQLI,
            DatabaseFactory::TYPE_PDO,
            DatabaseFactory::TYPE_POSTGRESQL,
            DatabaseFactory::TYPE_PGSQL,
            'mysql',
            'postgresql',
            'postgres'
        ]);
    }
}
