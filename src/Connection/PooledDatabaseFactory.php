<?php

namespace SimpleMDB\Connection;

use SimpleMDB\Connection\ConnectionPool;
use SimpleMDB\Exceptions\ConnectionException;

/**
 * Factory for creating pooled database connections
 */
class PooledDatabaseFactory
{
    /**
     * Create a connection pool from configuration
     */
    public static function createPool(array $config): ConnectionPool
    {
        // Validate configuration
        self::validateConfig($config);

        return new ConnectionPool($config);
    }

    /**
     * Create a simple pool with single write and multiple read connections
     */
    public static function createSimplePool(
        array $writeConfig,
        array $readConfigs = [],
        array $poolOptions = []
    ): ConnectionPool {
        $config = [
            'write' => $writeConfig,
            'read' => $readConfigs ?: [$writeConfig], // Use write for reads if no read replicas
            'pool' => array_merge([
                'max_connections' => 10,
                'min_connections' => 2,
                'connection_timeout' => 30,
                'health_checks' => true,
                'health_check_interval' => 60
            ], $poolOptions)
        ];

        return self::createPool($config);
    }

    /**
     * Create a pool with master-slave configuration
     */
    public static function createMasterSlavePool(
        array $masterConfig,
        array $slaveConfigs,
        array $poolOptions = []
    ): ConnectionPool {
        return self::createSimplePool($masterConfig, $slaveConfigs, $poolOptions);
    }

    /**
     * Create a pool from environment variables
     */
    public static function createFromEnv(array $poolOptions = []): ConnectionPool
    {
        $writeConfig = [
            'driver' => $_ENV['DB_DRIVER'] ?? 'pdo',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'database' => $_ENV['DB_DATABASE'] ?? 'test',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'ssl_options' => self::getSSLOptionsFromEnv()
        ];

        $readConfigs = [];
        
        // Check for read replica configurations
        $readHostsEnv = $_ENV['DB_READ_HOSTS'] ?? '';
        if (!empty($readHostsEnv)) {
            $readHosts = explode(',', $readHostsEnv);
            foreach ($readHosts as $host) {
                $host = trim($host);
                if (!empty($host)) {
                    $readConfig = $writeConfig;
                    $readConfig['host'] = $host;
                    $readConfigs[] = $readConfig;
                }
            }
        }

        return self::createSimplePool($writeConfig, $readConfigs, $poolOptions);
    }

    /**
     * Create a pool from a configuration file
     */
    public static function createFromFile(string $configFile): ConnectionPool
    {
        if (!file_exists($configFile)) {
            throw new ConnectionException("Configuration file not found: $configFile");
        }

        $extension = pathinfo($configFile, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'php':
                $config = require $configFile;
                break;
            case 'json':
                $config = json_decode(file_get_contents($configFile), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ConnectionException("Invalid JSON in config file: " . json_last_error_msg());
                }
                break;
            default:
                throw new ConnectionException("Unsupported config file format: $extension");
        }

        return self::createPool($config);
    }

    /**
     * Validate configuration array
     */
    private static function validateConfig(array $config): void
    {
        if (!isset($config['write'])) {
            throw new ConnectionException('Write connection configuration is required');
        }

        // Validate write configuration
        self::validateConnectionConfig($config['write'], 'write');

        // Validate read configurations if present
        if (isset($config['read'])) {
            foreach ($config['read'] as $index => $readConfig) {
                self::validateConnectionConfig($readConfig, "read[$index]");
            }
        }
    }

    /**
     * Validate individual connection configuration
     */
    private static function validateConnectionConfig(array $config, string $context): void
    {
        $required = ['host', 'username', 'password', 'database'];
        
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new ConnectionException("Missing required field '$field' in $context configuration");
            }
        }

        // Validate driver
        $allowedDrivers = ['pdo', 'mysqli'];
        $driver = $config['driver'] ?? 'pdo';
        if (!in_array($driver, $allowedDrivers)) {
            throw new ConnectionException("Invalid driver '$driver' in $context configuration. Allowed: " . implode(', ', $allowedDrivers));
        }
    }

    /**
     * Get SSL options from environment variables
     */
    private static function getSSLOptionsFromEnv(): array
    {
        $sslOptions = [];

        if (isset($_ENV['DB_SSL_ENABLE']) && $_ENV['DB_SSL_ENABLE'] === 'true') {
            $sslOptions['enable'] = true;
            
            if (isset($_ENV['DB_SSL_CA'])) {
                $sslOptions['ca'] = $_ENV['DB_SSL_CA'];
            }
            
            if (isset($_ENV['DB_SSL_CERT'])) {
                $sslOptions['cert'] = $_ENV['DB_SSL_CERT'];
            }
            
            if (isset($_ENV['DB_SSL_KEY'])) {
                $sslOptions['key'] = $_ENV['DB_SSL_KEY'];
            }
            
            if (isset($_ENV['DB_SSL_VERIFY'])) {
                $sslOptions['verify_cert'] = $_ENV['DB_SSL_VERIFY'] === 'true';
            }
        }

        return $sslOptions;
    }

    /**
     * Create configuration array for testing
     */
    public static function createTestConfig(
        string $host = 'localhost',
        string $database = 'test',
        string $username = 'root',
        string $password = ''
    ): array {
        return [
            'write' => [
                'driver' => 'pdo',
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'database' => $database,
                'charset' => 'utf8mb4'
            ],
            'read' => [
                [
                    'driver' => 'pdo',
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'database' => $database,
                    'charset' => 'utf8mb4'
                ]
            ],
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'connection_timeout' => 10,
                'health_checks' => false
            ]
        ];
    }
} 