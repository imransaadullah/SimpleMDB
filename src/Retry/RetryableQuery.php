<?php

namespace SimpleMDB\Retry;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SimpleQuery;
use SimpleMDB\Traits\LoggerAwareTrait;
use SimpleMDB\Traits\EventDispatcherAwareTrait;
use SimpleMDB\Exceptions\QueryException;

/**
 * Wrapper for database operations with retry logic
 */
class RetryableQuery
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    private DatabaseInterface $db;
    private RetryPolicy $retryPolicy;

    public function __construct(DatabaseInterface $db, ?RetryPolicy $retryPolicy = null)
    {
        $this->db = $db;
        $this->retryPolicy = $retryPolicy ?? new RetryPolicy();
    }

    /**
     * Execute a query with retry logic
     */
    public function query(string $sql, array $params = []): mixed
    {
        return $this->retryPolicy->execute(function() use ($sql, $params) {
            $this->log('debug', 'Executing query with retry support', [
                'sql' => $sql,
                'params' => $params
            ]);

            try {
                return $this->db->query($sql, $params);
            } catch (\Exception $e) {
                $this->log('warning', 'Query failed, will retry if applicable', [
                    'sql' => $sql,
                    'params' => $params,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Execute a SimpleQuery with retry logic
     */
    public function executeQuery(SimpleQuery $query, string $fetchType = 'assoc'): mixed
    {
        return $this->retryPolicy->execute(function() use ($query, $fetchType) {
            $sql = $query->toSql();
            $params = $query->getParams();

            $this->log('debug', 'Executing SimpleQuery with retry support', [
                'sql' => $sql,
                'params' => $params,
                'fetchType' => $fetchType
            ]);

            try {
                return $query->execute($this->db, $fetchType);
            } catch (\Exception $e) {
                $this->log('warning', 'SimpleQuery failed, will retry if applicable', [
                    'sql' => $sql,
                    'params' => $params,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Execute a transaction with retry logic
     */
    public function transaction(callable $callback): mixed
    {
        return $this->retryPolicy->execute(function() use ($callback) {
            $this->log('debug', 'Starting transaction with retry support');

            try {
                return $this->db->transaction($callback);
            } catch (\Exception $e) {
                $this->log('warning', 'Transaction failed, will retry if applicable', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Execute a prepared statement with retry logic
     */
    public function executePrepared(string $sql, array $params = []): mixed
    {
        return $this->retryPolicy->execute(function() use ($sql, $params) {
            $this->log('debug', 'Executing prepared statement with retry support', [
                'sql' => $sql,
                'params' => $params
            ]);

            try {
                return $this->db->prepare($sql)->execute($params);
            } catch (\Exception $e) {
                $this->log('warning', 'Prepared statement failed, will retry if applicable', [
                    'sql' => $sql,
                    'params' => $params,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Execute bulk operations with retry logic
     */
    public function executeBulk(callable $operation, array $args = []): mixed
    {
        return $this->retryPolicy->execute(function() use ($operation, $args) {
            $this->log('debug', 'Executing bulk operation with retry support');

            try {
                return $operation(...$args);
            } catch (\Exception $e) {
                $this->log('warning', 'Bulk operation failed, will retry if applicable', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Execute with custom retry settings
     */
    public function executeWithCustomRetry(
        callable $operation,
        array $args = [],
        int $maxRetries = null,
        int $baseDelayMs = null
    ): mixed {
        return $this->retryPolicy->executeWithSettings(
            $operation,
            $args,
            $maxRetries,
            $baseDelayMs
        );
    }

    /**
     * Get the retry policy
     */
    public function getRetryPolicy(): RetryPolicy
    {
        return $this->retryPolicy;
    }

    /**
     * Set a custom retry policy
     */
    public function setRetryPolicy(RetryPolicy $retryPolicy): self
    {
        $this->retryPolicy = $retryPolicy;
        return $this;
    }

    /**
     * Get the underlying database interface
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->db;
    }

    /**
     * Delegate method calls to the underlying database interface
     */
    public function __call(string $method, array $args): mixed
    {
        // For read operations, use retry logic
        $readMethods = [
            'fetchAll', 'fetch', 'fetchRow', 'fetchColumn', 'fetchValue',
            'read_data', 'read_data_all', 'numRows'
        ];

        if (in_array($method, $readMethods)) {
            return $this->retryPolicy->execute(function() use ($method, $args) {
                return $this->db->$method(...$args);
            });
        }

        // For write operations, use retry logic
        $writeMethods = [
            'write_data', 'update', 'delete', 'insert'
        ];

        if (in_array($method, $writeMethods)) {
            return $this->retryPolicy->execute(function() use ($method, $args) {
                return $this->db->$method(...$args);
            });
        }

        // For other methods, delegate directly without retry
        return $this->db->$method(...$args);
    }
} 