<?php

namespace SimpleMDB\Exceptions;

/**
 * Exception thrown when database connection fails
 */
class ConnectionException extends SimpleMDBException
{
    public static function connectionFailed(string $host, string $database, string $originalMessage): self
    {
        return new self(
            "Failed to connect to database '$database' on host '$host': $originalMessage",
            1001,
            null,
            ['host' => $host, 'database' => $database]
        );
    }

    public static function connectionLost(string $reason = ''): self
    {
        return new self(
            "Database connection lost" . ($reason ? ": $reason" : ''),
            1002,
            null,
            ['reason' => $reason]
        );
    }

    public static function sslConnectionFailed(string $reason = ''): self
    {
        return new self(
            "SSL connection failed" . ($reason ? ": $reason" : ''),
            1003,
            null,
            ['ssl_error' => $reason]
        );
    }

    public static function connectionTimeout(int $timeoutSeconds): self
    {
        return new self(
            "Connection timeout after $timeoutSeconds seconds",
            1004,
            null,
            ['timeout' => $timeoutSeconds]
        );
    }
} 