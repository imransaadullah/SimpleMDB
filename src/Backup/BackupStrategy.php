<?php

namespace SimpleMDB\Backup;

use SimpleMDB\DatabaseInterface;

/**
 * Abstract base class for backup strategies
 */
abstract class BackupStrategy
{
    protected DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Execute the backup strategy
     */
    abstract public function execute(BackupConfig $config): BackupResult;

    /**
     * Validate the backup configuration
     */
    public function validate(BackupConfig $config): bool
    {
        // Basic validation - can be overridden by strategies
        if (empty($config->getName())) {
            return false;
        }

        if (empty($config->getDatabase())) {
            return false;
        }

        return true;
    }

    /**
     * Estimate backup size
     */
    public function estimateSize(BackupConfig $config): int
    {
        try {
            $database = $config->getDatabase();
            $sql = "SELECT SUM(data_length + index_length) as size 
                    FROM information_schema.tables 
                    WHERE table_schema = ?";
            
            $result = $this->db->query($sql, [$database])->fetch('assoc');
            return (int)($result['size'] ?? 0);
            
        } catch (\Exception $e) {
            return 0; // Unknown size
        }
    }

    /**
     * Get list of tables to backup
     */
    protected function getTablesToBackup(BackupConfig $config): array
    {
        try {
            $database = $config->getDatabase();
            $sql = "SELECT table_name FROM information_schema.tables 
                    WHERE table_schema = ? AND table_type = 'BASE TABLE'";
            
            $tables = $this->db->query($sql, [$database])->fetchAll('col');
            
            // Apply include/exclude filters
            if (!empty($config->getIncludeTables())) {
                $tables = array_intersect($tables, $config->getIncludeTables());
            }
            
            if (!empty($config->getExcludeTables())) {
                $tables = array_diff($tables, $config->getExcludeTables());
            }
            
            return array_values($tables);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculate checksum for data
     */
    protected function calculateChecksum(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Compress data if compression is enabled
     */
    protected function compressData(string $data, BackupConfig $config): string
    {
        if (!$config->isCompressEnabled()) {
            return $data;
        }

        switch ($config->getCompressionMethod()) {
            case 'gzip':
                return gzencode($data);
            case 'bzip2':
                return bzcompress($data);
            case 'lz4':
                // Would need lz4 extension
                return $data;
            default:
                return $data;
        }
    }

    /**
     * Encrypt data if encryption is enabled
     */
    protected function encryptData(string $data, BackupConfig $config): string
    {
        if (!$config->isEncryptEnabled() || !$config->getEncryptionKey()) {
            return $data;
        }

        $key = $config->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        
        // Prepend IV to encrypted data
        return base64_encode($iv . base64_decode($encrypted));
    }

    /**
     * Get database connection
     */
    protected function getDatabase(): DatabaseInterface
    {
        return $this->db;
    }
} 