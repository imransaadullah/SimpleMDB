<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * Interface for database connection pool operations
 */
interface ConnectionPoolInterface
{
    /**
     * Create a new connection pool instance
     */
    public function __construct(array $config);

    /**
     * Get a connection for write operations
     */
    public function getWriteConnection(): DatabaseInterface;

    /**
     * Get a connection for read operations (with load balancing)
     */
    public function getReadConnection(): DatabaseInterface;

    /**
     * Get a connection (read or write)
     */
    public function getConnection(string $type = 'read'): DatabaseInterface;

    /**
     * Execute a query with automatic read/write routing
     */
    public function executeQuery(string $sql, array $params = []): mixed;

    /**
     * Execute a transaction (always uses write connection)
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get connection pool statistics
     */
    public function getStats(): array;

    /**
     * Perform health check on all connections
     */
    public function healthCheck(): array;

    /**
     * Close all connections in the pool
     */
    public function closeAll(): void;

    /**
     * Get maximum connections
     */
    public function getMaxConnections(): int;

    /**
     * Get minimum connections
     */
    public function getMinConnections(): int;

    /**
     * Get connection timeout
     */
    public function getConnectionTimeout(): int;

    /**
     * Check if health checks are enabled
     */
    public function isHealthChecksEnabled(): bool;

    /**
     * Get health check interval
     */
    public function getHealthCheckInterval(): int;

    /**
     * Set maximum connections
     */
    public function setMaxConnections(int $maxConnections): self;

    /**
     * Set minimum connections
     */
    public function setMinConnections(int $minConnections): self;

    /**
     * Set connection timeout
     */
    public function setConnectionTimeout(int $timeout): self;

    /**
     * Enable/disable health checks
     */
    public function setHealthChecks(bool $enabled): self;

    /**
     * Set health check interval
     */
    public function setHealthCheckInterval(int $interval): self;

    /**
     * Get pool configuration
     */
    public function getConfig(): array;

    /**
     * Check if pool is healthy
     */
    public function isHealthy(): bool;

    /**
     * Get active connections count
     */
    public function getActiveConnectionsCount(): int;

    /**
     * Get idle connections count
     */
    public function getIdleConnectionsCount(): int;

    /**
     * Get total connections count
     */
    public function getTotalConnectionsCount(): int;

    /**
     * Check if write query
     */
    public function isWriteQuery(string $sql): bool;

    /**
     * Reconnect a specific connection
     */
    public function reconnectConnection(DatabaseInterface $connection, string $type, string $key): void;

    /**
     * Get connection usage statistics
     */
    public function getConnectionUsageStats(): array;
} 