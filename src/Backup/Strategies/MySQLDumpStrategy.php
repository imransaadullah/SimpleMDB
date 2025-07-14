<?php

namespace SimpleMDB\Backup\Strategies;

use SimpleMDB\Backup\BackupStrategy;
use SimpleMDB\Backup\BackupConfig;
use SimpleMDB\Backup\BackupResult;
use SimpleMDB\Backup\BackupType;
use SimpleMDB\Backup\BackupException;
use DateTime;

/**
 * MySQL dump backup strategy
 */
class MySQLDumpStrategy extends BackupStrategy
{
    /**
     * Execute the backup strategy
     */
    public function execute(BackupConfig $config): BackupResult
    {
        $startTime = microtime(true);
        
        try {
            // Validate configuration
            if (!$this->validate($config)) {
                throw BackupException::invalidConfiguration('Invalid backup configuration');
            }
            
            // Generate backup data based on type
            $backupData = $this->generateBackupData($config);
            
            // Apply compression if enabled
            $backupData = $this->compressData($backupData, $config);
            
            // Apply encryption if enabled
            $backupData = $this->encryptData($backupData, $config);
            
            // Calculate checksum
            $checksum = $this->calculateChecksum($backupData);
            
            // Generate backup ID and path
            $backupId = $this->generateBackupId($config);
            $backupPath = $this->generateBackupPath($config, $backupId);
            
            // Calculate metadata
            $metadata = $this->generateMetadata($config);
            
            // Store backup data
            // Note: Storage would be handled by BackupManager
            
            $duration = microtime(true) - $startTime;
            
            return new BackupResult(
                $backupId,
                $config->getName(),
                $backupPath,
                strlen($backupData),
                new DateTime(),
                $duration,
                $checksum,
                $metadata,
                true
            );
            
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            return new BackupResult(
                uniqid('backup_failed_', true),
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
     * Generate backup data based on configuration
     */
    private function generateBackupData(BackupConfig $config): string
    {
        $sql = '';
        $database = $config->getDatabase();
        $type = $config->getType();
        
        // Add header comment
        $sql .= "-- SimpleMDB Backup\n";
        $sql .= "-- Database: {$database}\n";
        $sql .= "-- Type: {$type->getDescription()}\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Generator: MySQLDumpStrategy\n\n";
        
        // Set SQL mode and charset
        $sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
        $sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
        $sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
        $sql .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
        
        // Disable foreign key checks
        $sql .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
        $sql .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n\n";
        
        // Get tables to backup
        $tables = $this->getTablesToBackup($config);
        
        foreach ($tables as $table) {
            if ($type->includesSchema()) {
                $sql .= $this->generateCreateTableSQL($database, $table);
            }
            
            if ($type->includesData()) {
                $sql .= $this->generateInsertSQL($database, $table);
            }
            
            $sql .= "\n";
        }
        
        // Restore settings
        $sql .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
        $sql .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
        $sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
        $sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
        $sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
        
        return $sql;
    }

    /**
     * Generate CREATE TABLE SQL for a table
     */
    private function generateCreateTableSQL(string $database, string $table): string
    {
        try {
            $result = $this->db->query("SHOW CREATE TABLE `{$database}`.`{$table}`")->fetch('assoc');
            
            if (!$result || !isset($result['Create Table'])) {
                return "-- Unable to get CREATE TABLE statement for {$table}\n\n";
            }
            
            $createTable = $result['Create Table'];
            
            $sql = "-- Table structure for table `{$table}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable . ";\n\n";
            
            return $sql;
            
        } catch (\Exception $e) {
            return "-- Error generating CREATE TABLE for {$table}: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Generate INSERT SQL for table data
     */
    private function generateInsertSQL(string $database, string $table): string
    {
        try {
            // Check if table has data
            $countResult = $this->db->query("SELECT COUNT(*) as count FROM `{$database}`.`{$table}`")->fetch('assoc');
            $rowCount = (int)($countResult['count'] ?? 0);
            
            if ($rowCount === 0) {
                return "-- No data for table `{$table}`\n\n";
            }
            
            $sql = "-- Dumping data for table `{$table}`\n";
            $sql .= "LOCK TABLES `{$table}` WRITE;\n";
            $sql .= "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n";
            
            // Get all data from table
            $dataResult = $this->db->query("SELECT * FROM `{$database}`.`{$table}`")->fetchAll('assoc');
            
            if (!empty($dataResult)) {
                // Get column names
                $columns = array_keys($dataResult[0]);
                $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
                
                $sql .= "INSERT INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES\n";
                
                $valueRows = [];
                foreach ($dataResult as $row) {
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
                    $valueRows[] = '(' . implode(', ', $values) . ')';
                }
                
                $sql .= implode(",\n", $valueRows) . ";\n";
            }
            
            $sql .= "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n";
            $sql .= "UNLOCK TABLES;\n\n";
            
            return $sql;
            
        } catch (\Exception $e) {
            return "-- Error generating INSERT statements for {$table}: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Generate backup ID
     */
    private function generateBackupId(BackupConfig $config): string
    {
        $timestamp = date('Ymd_His');
        $hash = substr(md5($config->getName() . $config->getDatabase() . time()), 0, 8);
        return "backup_{$timestamp}_{$hash}";
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
        $tables = $this->getTablesToBackup($config);
        
        return [
            'strategy' => 'MySQLDumpStrategy',
            'database' => $config->getDatabase(),
            'type' => $config->getType()->value,
            'tables' => $tables,
            'table_count' => count($tables),
            'compression' => $config->isCompressEnabled() ? $config->getCompressionMethod() : 'none',
            'encryption' => $config->isEncryptEnabled() ? 'AES-256-CBC' : 'none',
            'include_tables' => $config->getIncludeTables(),
            'exclude_tables' => $config->getExcludeTables(),
            'description' => $config->getDescription(),
            'tags' => $config->getTags()
        ];
    }
} 