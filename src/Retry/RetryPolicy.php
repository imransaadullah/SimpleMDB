<?php

namespace SimpleMDB\Retry;

use SimpleMDB\Exceptions\ConnectionException;
use SimpleMDB\Exceptions\QueryException;
use Exception;

/**
 * Retry policy for handling transient database errors
 */
class RetryPolicy
{
    private int $maxRetries;
    private int $baseDelayMs;
    private float $backoffMultiplier;
    private int $maxDelayMs;
    private array $retryableExceptions;
    private array $retryableErrorCodes;

    public function __construct(
        int $maxRetries = 3,
        int $baseDelayMs = 100,
        float $backoffMultiplier = 2.0,
        int $maxDelayMs = 5000
    ) {
        $this->maxRetries = $maxRetries;
        $this->baseDelayMs = $baseDelayMs;
        $this->backoffMultiplier = $backoffMultiplier;
        $this->maxDelayMs = $maxDelayMs;
        
        // Default retryable exceptions
        $this->retryableExceptions = [
            ConnectionException::class,
            QueryException::class
        ];
        
        // Default retryable error codes
        $this->retryableErrorCodes = [
            1205, // Lock wait timeout exceeded
            1213, // Deadlock found when trying to get lock
            2006, // MySQL server has gone away
            2013, // Lost connection to MySQL server during query
            1040, // Too many connections
            1203, // User already has more than 'max_user_connections' active connections
        ];
    }

    /**
     * Execute a callable with retry logic
     */
    public function execute(callable $operation, array $args = []): mixed
    {
        $lastException = null;
        
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $operation(...$args);
            } catch (Exception $e) {
                $lastException = $e;
                
                // Don't retry on the last attempt
                if ($attempt >= $this->maxRetries) {
                    break;
                }
                
                // Check if this exception is retryable
                if (!$this->isRetryableException($e)) {
                    break;
                }
                
                // Calculate delay and wait
                $delayMs = $this->calculateDelay($attempt);
                usleep($delayMs * 1000);
            }
        }
        
        throw $lastException;
    }

    /**
     * Execute with custom retry settings
     */
    public function executeWithSettings(
        callable $operation,
        array $args = [],
        int $maxRetries = null,
        int $baseDelayMs = null
    ): mixed {
        $originalMaxRetries = $this->maxRetries;
        $originalBaseDelayMs = $this->baseDelayMs;
        
        if ($maxRetries !== null) {
            $this->maxRetries = $maxRetries;
        }
        if ($baseDelayMs !== null) {
            $this->baseDelayMs = $baseDelayMs;
        }
        
        try {
            return $this->execute($operation, $args);
        } finally {
            $this->maxRetries = $originalMaxRetries;
            $this->baseDelayMs = $originalBaseDelayMs;
        }
    }

    /**
     * Check if an exception is retryable
     */
    private function isRetryableException(Exception $e): bool
    {
        // Check exception type
        foreach ($this->retryableExceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }
        
        // Check error code
        if (in_array($e->getCode(), $this->retryableErrorCodes)) {
            return true;
        }
        
        // Check PDO/MySQLi specific error codes
        if ($this->isTransientDatabaseError($e)) {
            return true;
        }
        
        return false;
    }

    /**
     * Check for transient database errors by message content
     */
    private function isTransientDatabaseError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        $transientErrors = [
            'mysql server has gone away',
            'lost connection to mysql server',
            'connection refused',
            'connection timed out',
            'deadlock found',
            'lock wait timeout exceeded',
            'too many connections',
            'server shutdown in progress',
            'connection lost',
            'connection reset by peer'
        ];
        
        foreach ($transientErrors as $error) {
            if (strpos($message, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate retry delay using exponential backoff
     */
    private function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelayMs * pow($this->backoffMultiplier, $attempt);
        
        // Add jitter to prevent thundering herd
        $jitter = mt_rand(0, (int)($delay * 0.1));
        $delay = $delay + $jitter;
        
        return min($delay, $this->maxDelayMs);
    }

    /**
     * Add custom retryable exception type
     */
    public function addRetryableException(string $exceptionClass): self
    {
        $this->retryableExceptions[] = $exceptionClass;
        return $this;
    }

    /**
     * Add custom retryable error code
     */
    public function addRetryableErrorCode(int $errorCode): self
    {
        $this->retryableErrorCodes[] = $errorCode;
        return $this;
    }

    /**
     * Set maximum number of retries
     */
    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    /**
     * Set base delay in milliseconds
     */
    public function setBaseDelay(int $baseDelayMs): self
    {
        $this->baseDelayMs = $baseDelayMs;
        return $this;
    }

    /**
     * Set backoff multiplier for exponential backoff
     */
    public function setBackoffMultiplier(float $multiplier): self
    {
        $this->backoffMultiplier = $multiplier;
        return $this;
    }

    /**
     * Set maximum delay in milliseconds
     */
    public function setMaxDelay(int $maxDelayMs): self
    {
        $this->maxDelayMs = $maxDelayMs;
        return $this;
    }
} 