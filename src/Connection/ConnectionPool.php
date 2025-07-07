<?php

namespace SimpleMDB\Connection;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\DatabaseFactory;
use SimpleMDB\Exceptions\ConnectionException;
use SimpleMDB\Traits\LoggerAwareTrait;

/**
 * Database connection pool with read/write splitting
 */
class ConnectionPool
{
    use LoggerAwareTrait;

    private array $config;
    private array $writeConnections = [];
    private array $readConnections = [];
    private array $allConnections = [];
    private int $currentReadIndex = 0;
    private int $maxConnections;
    private int $minConnections;
    private int $connectionTimeout;
    private array $connectionStats = [];
    private bool $enableHealthChecks;
    private int $healthCheckInterval;
    private array $lastHealthCheck = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->maxConnections = $config['pool']['max_connections'] ?? 10;
        $this->minConnections = $config['pool']['min_connections'] ?? 2;
        $this->connectionTimeout = $config['pool']['connection_timeout'] ?? 30;
        $this->enableHealthChecks = $config['pool']['health_checks'] ?? true;
        $this->healthCheckInterval = $config['pool']['health_check_interval'] ?? 60;

        $this->initializeConnections();
    }

    /**
     * Get a connection for write operations
     */
    public function getWriteConnection(): DatabaseInterface
    {
        $connection = $this->getConnection('write');
        $this->log('debug', 'Retrieved write connection', [
            'connection_id' => spl_object_id($connection)
        ]);
        return $connection;
    }

    /**
     * Get a connection for read operations (with load balancing)
     */
    public function getReadConnection(): DatabaseInterface
    {
        $connection = $this->getConnection('read');
        $this->log('debug', 'Retrieved read connection', [
            'connection_id' => spl_object_id($connection)
        ]);
        return $connection;
    }

    /**
     * Get a connection (read or write)
     */
    public function getConnection(string $type = 'read'): DatabaseInterface
    {
        if ($type === 'write') {
            return $this->getWriteConnectionInternal();
        }

        return $this->getReadConnectionInternal();
    }

    /**
     * Execute a query with automatic read/write routing
     */
    public function executeQuery(string $sql, array $params = []): mixed
    {
        $isWriteQuery = $this->isWriteQuery($sql);
        $connection = $isWriteQuery ? $this->getWriteConnection() : $this->getReadConnection();

        $this->log('debug', 'Executing query with auto-routing', [
            'sql' => $sql,
            'params' => $params,
            'type' => $isWriteQuery ? 'write' : 'read',
            'connection_id' => spl_object_id($connection)
        ]);

        return $connection->query($sql, $params);
    }

    /**
     * Execute a transaction (always uses write connection)
     */
    public function transaction(callable $callback): mixed
    {
        $connection = $this->getWriteConnection();
        
        $this->log('debug', 'Starting transaction on write connection', [
            'connection_id' => spl_object_id($connection)
        ]);

        return $connection->transaction($callback);
    }

    /**
     * Get connection pool statistics
     */
    public function getStats(): array
    {
        $this->updateConnectionStats();

        return [
            'write_connections' => [
                'active' => count($this->writeConnections),
                'config' => count($this->config['write'] ?? []),
            ],
            'read_connections' => [
                'active' => count($this->readConnections),
                'config' => count($this->config['read'] ?? []),
            ],
            'total_connections' => count($this->allConnections),
            'max_connections' => $this->maxConnections,
            'min_connections' => $this->minConnections,
            'connection_stats' => $this->connectionStats,
            'health_checks_enabled' => $this->enableHealthChecks,
            'last_health_check' => $this->lastHealthCheck
        ];
    }

    /**
     * Perform health checks on all connections
     */
    public function healthCheck(): array
    {
        if (!$this->enableHealthChecks) {
            return ['status' => 'disabled'];
        }

        $results = [];
        $currentTime = time();

        // Check write connections
        foreach ($this->writeConnections as $key => $connection) {
            $result = $this->checkConnectionHealth($connection, 'write', $key);
            $results['write'][$key] = $result;

            if (!$result['healthy']) {
                $this->log('warning', 'Unhealthy write connection detected', [
                    'connection_key' => $key,
                    'error' => $result['error']
                ]);
                $this->reconnectConnection($connection, 'write', $key);
            }
        }

        // Check read connections
        foreach ($this->readConnections as $key => $connection) {
            $result = $this->checkConnectionHealth($connection, 'read', $key);
            $results['read'][$key] = $result;

            if (!$result['healthy']) {
                $this->log('warning', 'Unhealthy read connection detected', [
                    'connection_key' => $key,
                    'error' => $result['error']
                ]);
                $this->reconnectConnection($connection, 'read', $key);
            }
        }

        $this->lastHealthCheck['timestamp'] = $currentTime;
        $this->lastHealthCheck['results'] = $results;

        return $results;
    }

    /**
     * Close all connections
     */
    public function closeAll(): void
    {
        $this->log('info', 'Closing all connections in pool');

        foreach ($this->allConnections as $connection) {
            try {
                $connection->close();
            } catch (\Exception $e) {
                $this->log('warning', 'Error closing connection', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->writeConnections = [];
        $this->readConnections = [];
        $this->allConnections = [];
        $this->connectionStats = [];
    }

    /**
     * Initialize connections based on configuration
     */
    private function initializeConnections(): void
    {
        // Initialize write connections
        if (isset($this->config['write'])) {
            $writeConfig = $this->config['write'];
            
            if (is_array($writeConfig) && isset($writeConfig[0])) {
                // Multiple write connections (for clustering)
                foreach ($writeConfig as $index => $config) {
                    $this->createConnection($config, 'write', $index);
                }
            } else {
                // Single write connection
                $this->createConnection($writeConfig, 'write', 0);
            }
        }

        // Initialize read connections
        if (isset($this->config['read'])) {
            foreach ($this->config['read'] as $index => $config) {
                $this->createConnection($config, 'read', $index);
            }
        } else {
            // Use write connection for reads if no read replicas configured
            $this->readConnections = $this->writeConnections;
        }

        $this->log('info', 'Connection pool initialized', [
            'write_connections' => count($this->writeConnections),
            'read_connections' => count($this->readConnections)
        ]);
    }

    /**
     * Create a new database connection
     */
    private function createConnection(array $config, string $type, int $index): void
    {
        try {
            $connection = DatabaseFactory::create(
                $config['driver'] ?? 'pdo',
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4',
                $config['fetch_type'] ?? 'assoc',
                $config['ssl_options'] ?? []
            );

            $connectionId = spl_object_id($connection);
            $key = "{$type}_{$index}";

            if ($type === 'write') {
                $this->writeConnections[$key] = $connection;
            } else {
                $this->readConnections[$key] = $connection;
            }

            $this->allConnections[$connectionId] = $connection;
            $this->connectionStats[$connectionId] = [
                'type' => $type,
                'key' => $key,
                'created_at' => time(),
                'queries_executed' => 0,
                'last_used' => null,
                'config' => $config
            ];

            $this->log('debug', "Created {$type} connection", [
                'key' => $key,
                'host' => $config['host'],
                'database' => $config['database']
            ]);

        } catch (\Exception $e) {
            throw ConnectionException::connectionFailed(
                $config['host'],
                $config['database'],
                $e->getMessage()
            );
        }
    }

    /**
     * Get write connection with fallback
     */
    private function getWriteConnectionInternal(): DatabaseInterface
    {
        if (empty($this->writeConnections)) {
            throw new ConnectionException('No write connections available');
        }

        // For now, use the first available write connection
        // In the future, we could implement load balancing for write connections too
        $connection = reset($this->writeConnections);
        $this->updateConnectionUsage($connection);

        return $connection;
    }

    /**
     * Get read connection with load balancing
     */
    private function getReadConnectionInternal(): DatabaseInterface
    {
        if (empty($this->readConnections)) {
            throw new ConnectionException('No read connections available');
        }

        // Round-robin load balancing
        $connections = array_values($this->readConnections);
        $connection = $connections[$this->currentReadIndex % count($connections)];
        $this->currentReadIndex++;

        $this->updateConnectionUsage($connection);

        return $connection;
    }

    /**
     * Update connection usage statistics
     */
    private function updateConnectionUsage(DatabaseInterface $connection): void
    {
        $connectionId = spl_object_id($connection);
        
        if (isset($this->connectionStats[$connectionId])) {
            $this->connectionStats[$connectionId]['queries_executed']++;
            $this->connectionStats[$connectionId]['last_used'] = time();
        }
    }

    /**
     * Update overall connection statistics
     */
    private function updateConnectionStats(): void
    {
        // This could include more detailed metrics like query times, error rates, etc.
        // For now, we'll keep the basic stats we're already tracking
    }

    /**
     * Check if a SQL query is a write operation
     */
    private function isWriteQuery(string $sql): bool
    {
        $sql = trim(strtoupper($sql));
        $writeKeywords = ['INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'CREATE', 'DROP', 'ALTER', 'TRUNCATE'];
        
        foreach ($writeKeywords as $keyword) {
            if (strpos($sql, $keyword) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check health of a specific connection
     */
    private function checkConnectionHealth(DatabaseInterface $connection, string $type, string $key): array
    {
        try {
            // Simple health check - execute a lightweight query
            $result = $connection->query('SELECT 1 as health_check');
            $data = $result->fetch('assoc');

            return [
                'healthy' => $data['health_check'] === 1,
                'type' => $type,
                'key' => $key,
                'checked_at' => time(),
                'error' => null
            ];

        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'type' => $type,
                'key' => $key,
                'checked_at' => time(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reconnect a failed connection
     */
    private function reconnectConnection(DatabaseInterface $connection, string $type, string $key): void
    {
        try {
            $connectionId = spl_object_id($connection);
            $config = $this->connectionStats[$connectionId]['config'] ?? null;

            if (!$config) {
                $this->log('error', 'Cannot reconnect: configuration not found', [
                    'type' => $type,
                    'key' => $key
                ]);
                return;
            }

            // Remove old connection
            unset($this->allConnections[$connectionId]);
            unset($this->connectionStats[$connectionId]);

            if ($type === 'write') {
                unset($this->writeConnections[$key]);
            } else {
                unset($this->readConnections[$key]);
            }

            // Create new connection
            $index = (int) substr($key, strrpos($key, '_') + 1);
            $this->createConnection($config, $type, $index);

            $this->log('info', 'Successfully reconnected database connection', [
                'type' => $type,
                'key' => $key
            ]);

        } catch (\Exception $e) {
            $this->log('error', 'Failed to reconnect database connection', [
                'type' => $type,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }
} 