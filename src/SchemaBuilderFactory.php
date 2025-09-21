<?php

namespace SimpleMDB;

use SimpleMDB\Interfaces\SchemaBuilderInterface;
use SimpleMDB\Schema\MySQL\MySQLSchemaBuilder;
use SimpleMDB\Schema\PostgreSQL\PostgreSQLSchemaBuilder;

/**
 * SchemaBuilderFactory
 * 
 * Factory class for creating database-specific schema builders
 * while maintaining backward compatibility with the existing SchemaBuilder class.
 */
class SchemaBuilderFactory
{
    /**
     * Create a schema builder instance (auto-detects database type from connection)
     * This is the main factory method that should be used in most cases.
     *
     * @param DatabaseInterface $db Database connection
     * @param string|null $type Database type (optional override)
     * @return SchemaBuilderInterface
     */
    public static function create(DatabaseInterface $db, ?string $type = null): SchemaBuilderInterface
    {
        // Auto-detect database type if not provided (preferred approach)
        if ($type === null) {
            $type = self::detectDatabaseType($db);
        }

        return match ($type) {
            DatabaseFactory::TYPE_MYSQLI, DatabaseFactory::TYPE_PDO, 'mysql' => new MySQLSchemaBuilder($db),
            DatabaseFactory::TYPE_POSTGRESQL, DatabaseFactory::TYPE_PGSQL, 'postgresql', 'postgres' => new PostgreSQLSchemaBuilder($db),
            default => new MySQLSchemaBuilder($db) // Fallback to MySQL for backward compatibility
        };
    }

    /**
     * Create a MySQL-specific schema builder
     *
     * @param DatabaseInterface $db Database connection
     * @return MySQLSchemaBuilder
     */
    public static function createMySQL(DatabaseInterface $db): MySQLSchemaBuilder
    {
        return new MySQLSchemaBuilder($db);
    }

    /**
     * Create a PostgreSQL-specific schema builder
     *
     * @param DatabaseInterface $db Database connection
     * @return PostgreSQLSchemaBuilder
     */
    public static function createPostgreSQL(DatabaseInterface $db): PostgreSQLSchemaBuilder
    {
        return new PostgreSQLSchemaBuilder($db);
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
     * Get all available schema builder types
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
     * Check if a database type is supported for schema building
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
