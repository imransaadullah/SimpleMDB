<?php

namespace SimpleMDB\Backup;

/**
 * Result of a restore operation
 */
class RestoreResult
{
    private string $id;
    private string $backupId;
    private string $targetDatabase;
    private bool $success;
    private float $duration;
    private string $message;
    private array $restoredTables;
    private ?string $snapshotId;

    public function __construct(
        string $id,
        string $backupId,
        string $targetDatabase,
        bool $success,
        float $duration,
        string $message = '',
        array $restoredTables = [],
        ?string $snapshotId = null
    ) {
        $this->id = $id;
        $this->backupId = $backupId;
        $this->targetDatabase = $targetDatabase;
        $this->success = $success;
        $this->duration = $duration;
        $this->message = $message;
        $this->restoredTables = $restoredTables;
        $this->snapshotId = $snapshotId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBackupId(): string
    {
        return $this->backupId;
    }

    public function getTargetDatabase(): string
    {
        return $this->targetDatabase;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRestoredTables(): array
    {
        return $this->restoredTables;
    }

    public function getSnapshotId(): ?string
    {
        return $this->snapshotId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'backup_id' => $this->backupId,
            'target_database' => $this->targetDatabase,
            'success' => $this->success,
            'duration' => $this->duration,
            'message' => $this->message,
            'restored_tables' => $this->restoredTables,
            'snapshot_id' => $this->snapshotId
        ];
    }
} 