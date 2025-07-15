<?php

namespace SimpleMDB\Backup;

/**
 * Fluent interface for building backup configurations
 */
class BackupBuilder
{
    private BackupManager $manager;
    private BackupConfig $config;

    public function __construct(BackupManager $manager, BackupConfig $config)
    {
        $this->manager = $manager;
        $this->config = $config;
    }

    /**
     * Set backup to full (schema + data) - this is the default
     */
    public function full(): self
    {
        $this->config->setType(BackupType::FULL);
        return $this;
    }

    /**
     * Set backup to schema only
     */
    public function schemaOnly(): self
    {
        $this->config->setType(BackupType::SCHEMA_ONLY);
        return $this;
    }

    /**
     * Set backup to data only
     */
    public function dataOnly(): self
    {
        $this->config->setType(BackupType::DATA_ONLY);
        return $this;
    }

    /**
     * Set backup to incremental
     */
    public function incremental(string $lastBackupId = ''): self
    {
        $this->config->setType(BackupType::INCREMENTAL);
        if ($lastBackupId) {
            $options = $this->config->getStorageOptions();
            $options['last_backup_id'] = $lastBackupId;
            $this->config->setStorageOptions($options);
        }
        return $this;
    }

    /**
     * Include specific tables
     */
    public function includeTables(array $tables): self
    {
        $this->config->setIncludeTables($tables);
        return $this;
    }

    /**
     * Exclude specific tables
     */
    public function excludeTables(array $tables): self
    {
        $this->config->setExcludeTables($tables);
        return $this;
    }

    /**
     * Enable compression
     */
    public function compress(string $method = 'gzip'): self
    {
        $this->config->setCompression(true, $method);
        return $this;
    }

    /**
     * Disable compression
     */
    public function noCompression(): self
    {
        $this->config->setCompression(false);
        return $this;
    }

    /**
     * Enable encryption
     */
    public function encrypt(string $key): self
    {
        $this->config->setEncryption(true, $key);
        return $this;
    }

    /**
     * Set storage location
     */
    public function store(string $location): self
    {
        $this->config->setStorageLocation($location);
        return $this;
    }

    /**
     * Set description
     */
    public function description(string $description): self
    {
        $this->config->setDescription($description);
        return $this;
    }

    /**
     * Add tag
     */
    public function tag(string $tag): self
    {
        $tags = $this->config->getTags();
        $tags[] = $tag;
        $this->config->setTags($tags);
        return $this;
    }

    /**
     * Add multiple tags
     */
    public function tags(array $tags): self
    {
        $existingTags = $this->config->getTags();
        $this->config->setTags(array_merge($existingTags, $tags));
        return $this;
    }

    /**
     * Enable verification after backup
     */
    public function verify(): self
    {
        $this->config->setVerifyAfterBackup(true);
        return $this;
    }

    /**
     * Disable verification after backup
     */
    public function noVerification(): self
    {
        $this->config->setVerifyAfterBackup(false);
        return $this;
    }

    /**
     * Generate migration files from schema
     */
    public function generateMigrations(): self
    {
        $this->config->setGenerateMigrations(true);
        return $this;
    }

    /**
     * Set maximum file size for split operations
     */
    public function maxFileSize(int $bytes): self
    {
        $this->config->setMaxFileSize($bytes);
        return $this;
    }

    /**
     * Set tables per migration file
     */
    public function tablesPerFile(int $count): self
    {
        $this->config->setTablesPerFile($count);
        return $this;
    }

    /**
     * Split large files (convenience method)
     */
    public function splitLargeFiles(int $tablesPerFile = 50): self
    {
        return $this->tablesPerFile($tablesPerFile);
    }

    /**
     * Use expressive syntax for generated migrations
     */
    public function useExpressiveSyntax(): self
    {
        $options = $this->config->getStorageOptions();
        $options['use_expressive_syntax'] = true;
        $this->config->setStorageOptions($options);
        return $this;
    }

    /**
     * Set output path for generated files
     */
    public function outputPath(string $path): self
    {
        $options = $this->config->getStorageOptions();
        $options['output_path'] = $path;
        $this->config->setStorageOptions($options);
        return $this;
    }

    /**
     * Use streaming strategy for memory efficiency (optional enhancement)
     */
    public function streaming(int $chunkSize = 1000): self
    {
        $options = $this->config->getStorageOptions();
        $options['use_streaming'] = true;
        $options['chunk_size'] = $chunkSize;
        $this->config->setStorageOptions($options);
        return $this;
    }

    /**
     * Enable encryption for backup data (optional enhancement)
     */
    public function encrypted(string $encryptionKey, string $cipher = 'aes-256-cbc'): self
    {
        $options = $this->config->getStorageOptions();
        $options['encryption_enabled'] = true;
        $options['encryption_key'] = $encryptionKey;
        $options['encryption_cipher'] = $cipher;
        $this->config->setStorageOptions($options);
        return $this;
    }

    /**
     * Execute the backup
     */
    public function execute(): BackupResult
    {
        return $this->manager->executeBackup($this->config);
    }

    /**
     * Get the backup configuration
     */
    public function getConfig(): BackupConfig
    {
        return $this->config;
    }

    /**
     * Validate the configuration
     */
    public function validate(): bool
    {
        // Basic validation
        if (empty($this->config->getName())) {
            return false;
        }

        if (empty($this->config->getDatabase())) {
            return false;
        }

        // Check that we have valid storage location
        if (empty($this->config->getStorageLocation())) {
            return false;
        }

        return true;
    }

    /**
     * Get estimated backup size
     */
    public function estimateSize(): int
    {
        // This would use the strategy to estimate size
        return 0; // Placeholder
    }

    /**
     * Get preview of what will be backed up
     */
    public function preview(): array
    {
        return [
            'name' => $this->config->getName(),
            'database' => $this->config->getDatabase(),
            'type' => $this->config->getType()->value,
            'type_description' => $this->config->getType()->getDescription(),
            'include_tables' => $this->config->getIncludeTables(),
            'exclude_tables' => $this->config->getExcludeTables(),
            'compression' => $this->config->isCompressEnabled() ? $this->config->getCompressionMethod() : 'none',
            'encryption' => $this->config->isEncryptEnabled() ? 'enabled' : 'disabled',
            'storage_location' => $this->config->getStorageLocation(),
            'generate_migrations' => $this->config->shouldGenerateMigrations(),
            'estimated_size' => $this->estimateSize()
        ];
    }
} 