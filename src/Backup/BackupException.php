<?php

namespace SimpleMDB\Backup;

use Exception;

/**
 * Exception thrown during backup operations
 */
class BackupException extends Exception
{
    /**
     * Create exception for backup creation failure
     */
    public static function backupFailed(string $name, string $reason): self
    {
        return new self("Backup failed for '$name': $reason");
    }

    /**
     * Create exception for restoration failure
     */
    public static function restoreFailed(string $backupId, string $reason): self
    {
        return new self("Restore failed for backup '$backupId': $reason");
    }

    /**
     * Create exception for storage operation failure
     */
    public static function storageFailed(string $operation, string $reason): self
    {
        return new self("Storage operation '$operation' failed: $reason");
    }

    /**
     * Create exception for strategy not found
     */
    public static function strategyNotFound(string $type): self
    {
        return new self("Backup strategy not found for type: $type");
    }

    /**
     * Create exception for invalid configuration
     */
    public static function invalidConfiguration(string $reason): self
    {
        return new self("Invalid backup configuration: $reason");
    }
} 