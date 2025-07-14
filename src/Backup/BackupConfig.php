<?php

namespace SimpleMDB\Backup;

/**
 * Configuration for backup operations
 */
class BackupConfig
{
    private string $name;
    private string $database;
    private BackupType $type;
    private array $includeTables;
    private array $excludeTables;
    private bool $compressEnabled;
    private string $compressionMethod;
    private bool $encryptEnabled;
    private ?string $encryptionKey;
    private string $storageLocation;
    private array $storageOptions;
    private ?string $description;
    private array $tags;
    private bool $verifyAfterBackup;
    private bool $generateMigrations;
    private int $maxFileSize;
    private int $tablesPerFile;

    public function __construct(string $name, string $database)
    {
        $this->name = $name;
        $this->database = $database;
        $this->type = BackupType::FULL;
        $this->includeTables = [];
        $this->excludeTables = [];
        $this->compressEnabled = false;
        $this->compressionMethod = 'gzip';
        $this->encryptEnabled = false;
        $this->encryptionKey = null;
        $this->storageLocation = 'local://backups/';
        $this->storageOptions = [];
        $this->description = null;
        $this->tags = [];
        $this->verifyAfterBackup = true;
        $this->generateMigrations = false;
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->tablesPerFile = 50;
    }

    // Getters
    public function getName(): string { return $this->name; }
    public function getDatabase(): string { return $this->database; }
    public function getType(): BackupType { return $this->type; }
    public function getIncludeTables(): array { return $this->includeTables; }
    public function getExcludeTables(): array { return $this->excludeTables; }
    public function isCompressEnabled(): bool { return $this->compressEnabled; }
    public function getCompressionMethod(): string { return $this->compressionMethod; }
    public function isEncryptEnabled(): bool { return $this->encryptEnabled; }
    public function getEncryptionKey(): ?string { return $this->encryptionKey; }
    public function getStorageLocation(): string { return $this->storageLocation; }
    public function getStorageOptions(): array { return $this->storageOptions; }
    public function getDescription(): ?string { return $this->description; }
    public function getTags(): array { return $this->tags; }
    public function shouldVerifyAfterBackup(): bool { return $this->verifyAfterBackup; }
    public function shouldGenerateMigrations(): bool { return $this->generateMigrations; }
    public function getMaxFileSize(): int { return $this->maxFileSize; }
    public function getTablesPerFile(): int { return $this->tablesPerFile; }

    // Setters (fluent interface)
    public function setType(BackupType $type): self { $this->type = $type; return $this; }
    public function setIncludeTables(array $tables): self { $this->includeTables = $tables; return $this; }
    public function setExcludeTables(array $tables): self { $this->excludeTables = $tables; return $this; }
    public function setCompression(bool $enabled, string $method = 'gzip'): self { 
        $this->compressEnabled = $enabled; 
        $this->compressionMethod = $method; 
        return $this; 
    }
    public function setEncryption(bool $enabled, ?string $key = null): self { 
        $this->encryptEnabled = $enabled; 
        $this->encryptionKey = $key; 
        return $this; 
    }
    public function setStorageLocation(string $location): self { $this->storageLocation = $location; return $this; }
    public function setStorageOptions(array $options): self { $this->storageOptions = $options; return $this; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function setTags(array $tags): self { $this->tags = $tags; return $this; }
    public function setVerifyAfterBackup(bool $verify): self { $this->verifyAfterBackup = $verify; return $this; }
    public function setGenerateMigrations(bool $generate): self { $this->generateMigrations = $generate; return $this; }
    public function setMaxFileSize(int $size): self { $this->maxFileSize = $size; return $this; }
    public function setTablesPerFile(int $count): self { $this->tablesPerFile = $count; return $this; }

    /**
     * Get configuration as array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'database' => $this->database,
            'type' => $this->type->value,
            'include_tables' => $this->includeTables,
            'exclude_tables' => $this->excludeTables,
            'compress_enabled' => $this->compressEnabled,
            'compression_method' => $this->compressionMethod,
            'encrypt_enabled' => $this->encryptEnabled,
            'storage_location' => $this->storageLocation,
            'storage_options' => $this->storageOptions,
            'description' => $this->description,
            'tags' => $this->tags,
            'verify_after_backup' => $this->verifyAfterBackup,
            'generate_migrations' => $this->generateMigrations,
            'max_file_size' => $this->maxFileSize,
            'tables_per_file' => $this->tablesPerFile
        ];
    }
} 