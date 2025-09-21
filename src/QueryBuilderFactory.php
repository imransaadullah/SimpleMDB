<?php

namespace SimpleMDB;

use SimpleMDB\Interfaces\QueryBuilderInterface;
use SimpleMDB\Query\MySQL\MySQLQueryBuilder;
use SimpleMDB\Query\PostgreSQL\PostgreSQLQueryBuilder;

/**
 * QueryBuilderFactory
 * 
 * Factory class for creating database-specific query builders
 */
class QueryBuilderFactory
{
    /**
     * Create a query builder instance (auto-detects database type from connection)
     * This is the main factory method that should be used in most cases.
     *
     * @param DatabaseInterface|null $db Database connection for auto-detection
     * @param string|null $type Database type (optional override)
     * @return QueryBuilderInterface
     */
    public static function create(?DatabaseInterface $db = null, ?string $type = null): QueryBuilderInterface
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
            DatabaseFactory::TYPE_MYSQLI, DatabaseFactory::TYPE_PDO, 'mysql' => new MySQLQueryBuilder(),
            DatabaseFactory::TYPE_POSTGRESQL, DatabaseFactory::TYPE_PGSQL, 'postgresql', 'postgres' => new PostgreSQLQueryBuilder(),
            default => new MySQLQueryBuilder() // Fallback to MySQL
        };
    }

    /**
     * Create a MySQL-specific query builder
     *
     * @return MySQLQueryBuilder
     */
    public static function createMySQL(): MySQLQueryBuilder
    {
        return new MySQLQueryBuilder();
    }

    /**
     * Create a PostgreSQL-specific query builder
     *
     * @return PostgreSQLQueryBuilder
     */
    public static function createPostgreSQL(): PostgreSQLQueryBuilder
    {
        return new PostgreSQLQueryBuilder();
    }

    /**
     * Detect database type from connection instance
     *
     * @param DatabaseInterface $db Database connection
     * @return string Database type
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
            // Check if it's PostgreSQL PDO
            if (str_contains($className, 'PostgreSQL')) {
                return DatabaseFactory::TYPE_POSTGRESQL;
            }
            return DatabaseFactory::TYPE_PDO;
        }
        
        // Default to MySQL for backward compatibility
        return DatabaseFactory::TYPE_PDO;
    }

    /**
     * Get all available query builder types
     *
     * @return array
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
     * Check if a database type is supported for query building
     *
     * @param string $type Database type
     * @return bool
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
