<?php
namespace SimpleMDB;

use SimpleMySQLi;

class DatabaseFactory
{
    public const TYPE_MYSQLI = 'mysqli';
    public const TYPE_PDO = 'pdo';

    /**
     * Create a database connection instance
     *
     * @param string $type Database type (mysqli or pdo)
     * @param string $host Hostname or IP address
     * @param string $username Database username
     * @param string $password Database password
     * @param string $dbName Database name
     * @param string $charset (optional) Default character encoding
     * @param string $defaultFetchType (optional) Default fetch type
     * @param array $sslOptions (optional) SSL configuration options
     * @return DatabaseInterface
     * @throws \InvalidArgumentException If invalid database type
     */
    public static function create(
        string $type,
        string $host,
        string $username,
        string $password,
        string $dbName,
        string $charset = 'utf8mb4',
        string $defaultFetchType = 'assoc',
        array $sslOptions = []
    ): DatabaseInterface {
        return match ($type) {
            self::TYPE_MYSQLI => new SimpleMySQLi($host, $username, $password, $dbName, $charset, $defaultFetchType, $sslOptions),
            self::TYPE_PDO => new SimplePDO($host, $username, $password, $dbName, $charset, $defaultFetchType, $sslOptions),
            default => throw new \InvalidArgumentException("Invalid database type: $type")
        };
    }
} 