<?php

namespace SimpleMDB\Backup;

use DateTime;

/**
 * Metadata for a backup
 */
class BackupMetadata
{
    private string $id;
    private string $name;
    private string $database;
    private BackupType $type;
    private DateTime $createdAt;
    private int $size;
    private string $checksum;
    private string $storageType;
    private string $storagePath;
    private array $metadata;

    public function __construct(
        string $id,
        string $name,
        string $database,
        BackupType $type,
        DateTime $createdAt,
        int $size,
        string $checksum = '',
        string $storageType = 'local',
        string $storagePath = '',
        array $metadata = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->database = $database;
        $this->type = $type;
        $this->createdAt = $createdAt;
        $this->size = $size;
        $this->checksum = $checksum;
        $this->storageType = $storageType;
        $this->storagePath = $storagePath;
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getType(): BackupType
    {
        return $this->type;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getStorageType(): string
    {
        return $this->storageType;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'database' => $this->database,
            'type' => $this->type->value,
            'type_description' => $this->type->getDescription(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'size' => $this->size,
            'formatted_size' => $this->getFormattedSize(),
            'checksum' => $this->checksum,
            'storage_type' => $this->storageType,
            'storage_path' => $this->storagePath,
            'metadata' => $this->metadata
        ];
    }
} 