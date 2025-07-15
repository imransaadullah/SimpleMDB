<?php

namespace SimpleMDB\Backup\Strategies;

use SimpleMDB\Backup\BackupStrategy;
use SimpleMDB\Backup\BackupConfig;
use SimpleMDB\Backup\BackupResult;
use SimpleMDB\Backup\BackupType;
use SimpleMDB\Backup\BackupException;
use DateTime;

/**
 * Memory-efficient streaming MySQL dump strategy
 * 
 * This strategy processes data in chunks to avoid memory exhaustion
 * while maintaining backward compatibility with MySQLDumpStrategy
 */
class StreamingMySQLDumpStrategy extends MySQLDumpStrategy
{
    private int $chunkSize = 1000; // Rows per chunk
    private string $tempDirectory;
    
    public function __construct($db, int $chunkSize = 1000, ?string $tempDirectory = null)
    {
        parent::__construct($db);
        $this->chunkSize = $chunkSize;
        $this->tempDirectory = $tempDirectory ?? sys_get_temp_dir();
    }

    /**
     * Execute backup with streaming/chunked processing
     */
    public function execute(BackupConfig $config): BackupResult
    {
        $startTime = microtime(true);
        
        try {
            if (!$this->validate($config)) {
                throw BackupException::invalidConfiguration('Invalid backup configuration');
            }
            
            // Create temporary file for streaming
            $tempFile = tempnam($this->tempDirectory, 'backup_stream_');
            $handle = fopen($tempFile, 'w');
            
            if (!$handle) {
                throw BackupException::backupFailed($config->getName(), 'Cannot create temporary file');
            }
            
            // Stream backup data in chunks
            $this->streamBackupData($handle, $config);
            
            fclose($handle);
            
            // Read final data
            $backupData = file_get_contents($tempFile);
            unlink($tempFile); // Clean up
            
            // Apply compression if enabled (same as parent)
            $backupData = $this->compressData($backupData, $config);
            
            // Apply encryption if enabled (same as parent)
            $backupData = $this->encryptData($backupData, $config);
            
            // Calculate checksum
            $checksum = $this->calculateChecksum($backupData);
            
            // Generate backup metadata
            $backupId = $this->generateBackupId($config);
            $backupPath = $this->generateBackupPath($config, $backupId);
            $metadata = $this->generateMetadata($config);
            
            $duration = microtime(true) - $startTime;
            
            return new BackupResult(
                $backupId,
                $config->getName(),
                $backupPath,
                strlen($backupData),
                new DateTime(),
                $duration,
                $checksum,
                array_merge($metadata, [
                    'strategy' => 'streaming',
                    'chunk_size' => $this->chunkSize,
                    'memory_efficient' => true
                ]),
                true
            );
            
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            return new BackupResult(
                uniqid('backup_', true),
                $config->getName(),
                '',
                0,
                new DateTime(),
                $duration,
                '',
                [],
                false,
                $e->getMessage()
            );
        }
    }

    /**
     * Stream backup data to file handle in chunks
     */
    private function streamBackupData($handle, BackupConfig $config): void
    {
        $database = $config->getDatabase();
        
        // Write header
        $this->writeHeader($handle, $config);
        
        // Get tables to backup
        $tables = $this->getTablesForBackup($config);
        
        foreach ($tables as $table) {
            if ($config->getType()->includesSchema()) {
                $this->streamTableSchema($handle, $table);
            }
            
            if ($config->getType()->includesData()) {
                $this->streamTableData($handle, $table);
            }
        }
        
        // Write footer
        $this->writeFooter($handle, $config);
    }

    /**
     * Stream table data in chunks to avoid memory issues
     */
    private function streamTableData($handle, string $table): void
    {
        fwrite($handle, "\n-- Data for table `$table`\n");
        fwrite($handle, "LOCK TABLES `$table` WRITE;\n");
        fwrite($handle, "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n");
        
        // Get total row count for progress tracking
        $countQuery = "SELECT COUNT(*) as total FROM `$table`";
        $countResult = $this->db->query($countQuery)->fetch('assoc');
        $totalRows = $countResult['total'] ?? 0;
        
        if ($totalRows > 0) {
            $offset = 0;
            
            while ($offset < $totalRows) {
                // Fetch chunk of data using direct SQL for offset/limit control
                $dataQuery = "SELECT * FROM `$table` LIMIT $offset, {$this->chunkSize}";
                $rows = $this->db->query($dataQuery)->fetchAll('assoc');
                
                if (empty($rows)) {
                    break;
                }
                
                // Convert rows to INSERT statements
                $this->writeInsertStatements($handle, $table, $rows);
                
                $offset += $this->chunkSize;
                
                // Optional: Yield control to prevent timeouts
                if ($offset % ($this->chunkSize * 10) === 0) {
                    usleep(1000); // 1ms pause every 10 chunks
                }
            }
        }
        
        fwrite($handle, "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n");
        fwrite($handle, "UNLOCK TABLES;\n");
    }

    /**
     * Write INSERT statements for a chunk of rows
     */
    private function writeInsertStatements($handle, string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }
        
        // Get column names from first row
        $columns = array_keys($rows[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        fwrite($handle, "INSERT INTO `$table` ($columnList) VALUES\n");
        
        $valueStrings = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (is_numeric($value)) {
                    $values[] = $value;
                } else {
                    $values[] = "'" . $this->db->quote($value) . "'";
                }
            }
            $valueStrings[] = '(' . implode(', ', $values) . ')';
        }
        
        fwrite($handle, implode(",\n", $valueStrings) . ";\n");
    }

    /**
     * Stream table schema
     */
    private function streamTableSchema($handle, string $table): void
    {
        fwrite($handle, "\n-- Table structure for table `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        
        // Get CREATE TABLE statement using raw SQL (administrative command)
        $createResult = $this->db->query("SHOW CREATE TABLE `$table`")->fetch('assoc');
        
        if ($createResult && isset($createResult['Create Table'])) {
            fwrite($handle, $createResult['Create Table'] . ";\n");
        }
    }

    /**
     * Get tables for backup based on configuration
     */
    private function getTablesForBackup(BackupConfig $config): array
    {
        // Get all tables using direct SQL query
        $database = $config->getDatabase();
        $tablesQuery = "SELECT table_name FROM information_schema.tables 
                        WHERE table_schema = ? AND table_type = 'BASE TABLE'";
        
        $allTables = $this->db->query($tablesQuery, [$database])->fetchAll('assoc');
        $tables = array_column($allTables, 'table_name');
        
        // Apply include/exclude filters
        if (!empty($config->getIncludeTables())) {
            $tables = array_intersect($tables, $config->getIncludeTables());
        }
        
        if (!empty($config->getExcludeTables())) {
            $tables = array_diff($tables, $config->getExcludeTables());
        }
        
        return array_values($tables);
    }

    /**
     * Write backup header
     */
    private function writeHeader($handle, BackupConfig $config): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $database = $config->getDatabase();
        
        fwrite($handle, "-- SimpleMDB Backup\n");
        fwrite($handle, "-- Generated: $timestamp\n");
        fwrite($handle, "-- Database: $database\n");
        fwrite($handle, "-- Strategy: Streaming (Memory Efficient)\n");
        fwrite($handle, "-- Chunk Size: {$this->chunkSize} rows\n");
        fwrite($handle, "\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "START TRANSACTION;\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n");
        fwrite($handle, "\n");
    }

    /**
     * Write backup footer
     */
    private function writeFooter($handle, BackupConfig $config): void
    {
        fwrite($handle, "\nCOMMIT;\n");
        fwrite($handle, "-- End of SimpleMDB Backup\n");
    }

    /**
     * Set chunk size for processing
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Get current chunk size
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Generate backup ID
     */
    private function generateBackupId(BackupConfig $config): string
    {
        $timestamp = date('Ymd_His');
        $hash = substr(md5($config->getName() . $config->getDatabase() . time()), 0, 8);
        return "backup_stream_{$timestamp}_{$hash}";
    }

    /**
     * Generate backup file path
     */
    private function generateBackupPath(BackupConfig $config, string $backupId): string
    {
        $extension = '.sql';
        
        if ($config->isCompressEnabled()) {
            $extension .= '.gz';
        }
        
        if ($config->isEncryptEnabled()) {
            $extension .= '.enc';
        }
        
        $database = $config->getDatabase();
        $date = date('Y/m/d');
        
        return "{$date}/{$database}/{$backupId}{$extension}";
    }

    /**
     * Generate backup metadata
     */
    private function generateMetadata(BackupConfig $config): array
    {
        $tables = $this->getTablesForBackup($config);
        
        return [
            'strategy' => 'StreamingMySQLDumpStrategy',
            'database' => $config->getDatabase(),
            'type' => $config->getType()->value,
            'tables' => $tables,
            'table_count' => count($tables),
            'chunk_size' => $this->chunkSize,
            'memory_efficient' => true,
            'compression' => $config->isCompressEnabled() ? $config->getCompressionMethod() : 'none',
            'encryption' => $config->isEncryptEnabled() ? 'aes-256-cbc' : 'none',
            'include_tables' => $config->getIncludeTables(),
            'exclude_tables' => $config->getExcludeTables(),
            'description' => $config->getDescription(),
            'tags' => $config->getTags()
        ];
    }
} 