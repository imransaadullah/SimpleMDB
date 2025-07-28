<?php

namespace SimpleMDB\Interfaces;

use Exception;

/**
 * Interface for retry policy operations
 */
interface RetryPolicyInterface
{
    /**
     * Create a new retry policy instance
     */
    public function __construct(
        int $maxRetries = 3,
        int $baseDelayMs = 100,
        float $backoffMultiplier = 2.0,
        int $maxDelayMs = 5000
    );

    /**
     * Execute a callable with retry logic
     */
    public function execute(callable $operation, array $args = []): mixed;

    /**
     * Execute with custom retry settings
     */
    public function executeWithSettings(
        callable $operation,
        array $args = [],
        int $maxRetries = null,
        int $baseDelayMs = null
    ): mixed;

    /**
     * Add retryable exception class
     */
    public function addRetryableException(string $exceptionClass): self;

    /**
     * Add retryable error code
     */
    public function addRetryableErrorCode(int $errorCode): self;

    /**
     * Set maximum retries
     */
    public function setMaxRetries(int $maxRetries): self;

    /**
     * Set base delay in milliseconds
     */
    public function setBaseDelay(int $baseDelayMs): self;

    /**
     * Set backoff multiplier
     */
    public function setBackoffMultiplier(float $multiplier): self;

    /**
     * Set maximum delay in milliseconds
     */
    public function setMaxDelay(int $maxDelayMs): self;

    /**
     * Get maximum retries
     */
    public function getMaxRetries(): int;

    /**
     * Get base delay in milliseconds
     */
    public function getBaseDelay(): int;

    /**
     * Get backoff multiplier
     */
    public function getBackoffMultiplier(): float;

    /**
     * Get maximum delay in milliseconds
     */
    public function getMaxDelay(): int;

    /**
     * Get retryable exceptions
     */
    public function getRetryableExceptions(): array;

    /**
     * Get retryable error codes
     */
    public function getRetryableErrorCodes(): array;

    /**
     * Check if exception is retryable
     */
    public function isRetryableException(Exception $e): bool;

    /**
     * Check if error is transient database error
     */
    public function isTransientDatabaseError(Exception $e): bool;

    /**
     * Calculate delay for retry attempt
     */
    public function calculateDelay(int $attempt): int;

    /**
     * Reset retry policy to default settings
     */
    public function reset(): self;

    /**
     * Get retry statistics
     */
    public function getRetryStats(): array;
} 