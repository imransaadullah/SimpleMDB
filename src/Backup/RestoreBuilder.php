<?php

namespace SimpleMDB\Backup;

/**
 * Fluent interface for building restore operations
 */
class RestoreBuilder
{
    private BackupManager $manager;
    private BackupMetadata $backup;
    private ?string $targetDatabase = null;
    private array $targetTables = [];
    private bool $skipSchema = false;
    private bool $skipData = false;
    private bool $createSnapshot = false;
    private bool $verifyBeforeRestore = true;

    public function __construct(BackupManager $manager, BackupMetadata $backup)
    {
        $this->manager = $manager;
        $this->backup = $backup;
    }

    /**
     * Set target database name
     */
    public function to(string $database): self
    {
        $this->targetDatabase = $database;
        return $this;
    }

    /**
     * Restore only specific tables
     */
    public function tables(array $tables): self
    {
        $this->targetTables = $tables;
        return $this;
    }

    /**
     * Skip schema restoration
     */
    public function skipSchema(): self
    {
        $this->skipSchema = true;
        return $this;
    }

    /**
     * Skip data restoration
     */
    public function skipData(): self
    {
        $this->skipData = true;
        return $this;
    }

    /**
     * Create snapshot before restore
     */
    public function createSnapshot(): self
    {
        $this->createSnapshot = true;
        return $this;
    }

    /**
     * Verify backup before restore
     */
    public function verify(): self
    {
        $this->verifyBeforeRestore = true;
        return $this;
    }

    /**
     * Skip verification before restore
     */
    public function noVerification(): self
    {
        $this->verifyBeforeRestore = false;
        return $this;
    }

    /**
     * Execute the restore operation
     */
    public function execute(): RestoreResult
    {
        // Implementation placeholder
        return new RestoreResult(
            uniqid('restore_', true),
            $this->backup->getId(),
            $this->targetDatabase ?? $this->backup->getDatabase(),
            true,
            0.0,
            'Restore completed successfully'
        );
    }

    /**
     * Get restore configuration preview
     */
    public function preview(): array
    {
        return [
            'backup_id' => $this->backup->getId(),
            'backup_name' => $this->backup->getName(),
            'source_database' => $this->backup->getDatabase(),
            'target_database' => $this->targetDatabase ?? $this->backup->getDatabase(),
            'target_tables' => $this->targetTables,
            'skip_schema' => $this->skipSchema,
            'skip_data' => $this->skipData,
            'create_snapshot' => $this->createSnapshot,
            'verify_before_restore' => $this->verifyBeforeRestore
        ];
    }
} 