<?php

namespace SimpleMDB\Backup;

/**
 * Core interface for backup operations
 */
interface BackupInterface
{
    /**
     * Execute the backup operation
     */
    public function execute(): BackupResult;
    
    /**
     * Get backup configuration
     */
    public function getConfig(): BackupConfig;
    
    /**
     * Validate backup configuration
     */
    public function validate(): bool;
    
    /**
     * Get estimated backup size
     */
    public function getEstimatedSize(): int;
} 