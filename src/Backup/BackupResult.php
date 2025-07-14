<?php

namespace SimpleMDB\Backup;

use DateTime;

/**
 * Represents the result of a backup operation
 */
class BackupResult
{
    private string $id;
    private string $name;
    private string $path;
    private int $size;
    private DateTime $createdAt;
    private float $duration;
    private string $checksum;
    private array $metadata;
    private bool $success;
    private ?string $errorMessage;

    public function __construct(
        string $id,
        string $name,
        string $path,
        int $size,
        DateTime $createdAt,
        float $duration,
        string $checksum = '',
        array $metadata = [],
        bool $success = true,
        ?string $errorMessage = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->path = $path;
        $this->size = $size;
        $this->createdAt = $createdAt;
        $this->duration = $duration;
        $this->checksum = $checksum;
        $this->metadata = $metadata;
        $this->success = $success;
        $this->errorMessage = $errorMessage;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
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

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getFormattedDuration(): string
    {
        if ($this->duration < 60) {
            return round($this->duration, 2) . ' seconds';
        } elseif ($this->duration < 3600) {
            return round($this->duration / 60, 2) . ' minutes';
        } else {
            return round($this->duration / 3600, 2) . ' hours';
        }
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'size' => $this->size,
            'formatted_size' => $this->getFormattedSize(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'duration' => $this->duration,
            'formatted_duration' => $this->getFormattedDuration(),
            'checksum' => $this->checksum,
            'metadata' => $this->metadata,
            'success' => $this->success,
            'error_message' => $this->errorMessage
        ];
    }
} 