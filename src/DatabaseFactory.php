<?php
namespace SimpleMDB;

use SimpleMDB\Interfaces\SchemaBuilderInterface;
use SimpleMDB\Interfaces\QueryBuilderInterface;
use SimpleMDB\Interfaces\TableAlterInterface;
use SimpleMDB\Interfaces\CaseBuilderInterface;
use SimpleMDB\Schema\MySQL\MySQLSchemaBuilder;
use SimpleMDB\Schema\PostgreSQL\PostgreSQLSchemaBuilder;

class DatabaseFactory
{
    public const TYPE_MYSQLI = 'mysqli';
    public const TYPE_PDO = 'pdo';
    public const TYPE_POSTGRESQL = 'postgresql';
    public const TYPE_PGSQL = 'pgsql'; // Alias for PostgreSQL

    /**
     * Create a database connection instance
     *
     * @param string $type Database type (mysqli, pdo, postgresql, pgsql)
     * @param string $host Hostname or IP address
     * @param string $username Database username
     * @param string $password Database password
     * @param string $dbName Database name
     * @param string $charset (optional) Default character encoding
     * @param string $defaultFetchType (optional) Default fetch type
     * @param array $sslOptions (optional) SSL configuration options
     * @param int $port (optional) Database port
     * @return DatabaseInterface
     * @throws \InvalidArgumentException If invalid database type
     */
    public static function create(
        string $type,
        string $host,
        string $username,
        string $password,
        string $dbName,
        ?string $charset = null,
        string $defaultFetchType = 'assoc',
        array $sslOptions = [],
        ?int $port = null
    ): DatabaseInterface {
        // Set default charset and port based on database type
        if ($charset === null) {
            $charset = in_array($type, [self::TYPE_POSTGRESQL, self::TYPE_PGSQL]) ? 'UTF8' : 'utf8mb4';
        }
        
        if ($port === null) {
            $port = in_array($type, [self::TYPE_POSTGRESQL, self::TYPE_PGSQL]) ? 5432 : 3306;
        }

        return match ($type) {
            self::TYPE_MYSQLI => new \SimpleMDB\SimpleMySQLi($host, $username, $password, $dbName, $charset, $defaultFetchType, $sslOptions),
            self::TYPE_PDO => new \SimpleMDB\SimplePDO($host, $username, $password, $dbName, $charset, $defaultFetchType, $sslOptions),
            self::TYPE_POSTGRESQL, self::TYPE_PGSQL => new \SimpleMDB\PostgreSQLDatabase($host, $username, $password, $dbName, $charset, $defaultFetchType, $sslOptions, $port),
            default => throw new \InvalidArgumentException("Invalid database type: $type. Supported types: mysqli, pdo, postgresql, pgsql")
        };
    }

    /**
     * Create a schema builder instance for the specified database type
     *
     * @param DatabaseInterface $db Database connection
     * @param string|null $type Database type (auto-detected if null)
     * @return SchemaBuilderInterface
     */
    public static function createSchemaBuilder(DatabaseInterface $db, ?string $type = null): SchemaBuilderInterface
    {
        if ($type === null) {
            $type = self::detectDatabaseType($db);
        }

        return match ($type) {
            self::TYPE_MYSQLI, self::TYPE_PDO => new MySQLSchemaBuilder($db),
            self::TYPE_POSTGRESQL, self::TYPE_PGSQL => new PostgreSQLSchemaBuilder($db),
            default => throw new \InvalidArgumentException("Unsupported database type for schema builder: $type")
        };
    }

    /**
     * Create a query builder instance (auto-detects from database connection)
     *
     * @param DatabaseInterface|null $db Database connection for auto-detection
     * @param string|null $type Database type (optional override)
     * @return QueryBuilderInterface
     */
    public static function createQueryBuilder(?DatabaseInterface $db = null, ?string $type = null): QueryBuilderInterface
    {
        return QueryBuilderFactory::create($db, $type);
    }

    /**
     * Create a table alter instance for the specified database type
     *
     * @param DatabaseInterface $db Database connection
     * @param string $tableName Table name
     * @param string|null $type Database type (auto-detected if null)
     * @return TableAlterInterface
     */
    public static function createTableAlter(DatabaseInterface $db, string $tableName, ?string $type = null): TableAlterInterface
    {
        if ($type === null) {
            $type = self::detectDatabaseType($db);
        }

        // TODO: Implement database-specific table alter classes
        throw new \InvalidArgumentException("Table alter not yet implemented for database type: $type");
    }

    /**
     * Create a case builder instance (auto-detects from database connection)
     *
     * @param DatabaseInterface|null $db Database connection for auto-detection
     * @param string|null $type Database type (optional override)
     * @return CaseBuilderInterface
     */
    public static function createCaseBuilder(?DatabaseInterface $db = null, ?string $type = null): CaseBuilderInterface
    {
        return CaseBuilderFactory::create($db, $type);
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
            return self::TYPE_POSTGRESQL;
        }
        
        if (str_contains($className, 'MySQLi')) {
            return self::TYPE_MYSQLI;
        }
        
        if (str_contains($className, 'PDO')) {
            return self::TYPE_PDO;
        }
        
        // Default to MySQL for backward compatibility
        return self::TYPE_PDO;
    }

    /**
     * Get supported database types
     *
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return [
            self::TYPE_MYSQLI => 'MySQL via MySQLi',
            self::TYPE_PDO => 'MySQL via PDO',
            self::TYPE_POSTGRESQL => 'PostgreSQL',
            self::TYPE_PGSQL => 'PostgreSQL (alias)'
        ];
    }

    /**
     * Check if a database type is supported
     *
     * @param string $type Database type
     * @return bool
     */
    public static function isSupported(string $type): bool
    {
        return in_array($type, [
            self::TYPE_MYSQLI,
            self::TYPE_PDO,
            self::TYPE_POSTGRESQL,
            self::TYPE_PGSQL
        ]);
    }
}