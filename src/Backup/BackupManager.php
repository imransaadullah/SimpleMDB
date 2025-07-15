<?php

namespace SimpleMDB\Backup;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SchemaBuilder;
use SimpleMDB\Traits\LoggerAwareTrait;
use SimpleMDB\Traits\EventDispatcherAwareTrait;
use SimpleMDB\Backup\Storage\LocalStorage;
use SimpleMDB\Backup\Strategies\MySQLDumpStrategy;
use DateTime;

/**
 * Main backup management class
 */
class BackupManager
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    private DatabaseInterface $db;
    private string $backupsPath;
    private string $backupTable = 'backups';
    private array $loadedBackups = [];
    private array $strategies = [];
    private array $storageAdapters = [];
    private ?SchemaAnalyzer $schemaAnalyzer = null;
    private ?MigrationGenerator $migrationGenerator = null;

    public function __construct(DatabaseInterface $db, string $backupsPath = 'backups')
    {
        $this->db = $db;
        $this->backupsPath = rtrim($backupsPath, '/');
        $this->ensureBackupTable();
        $this->registerDefaultStrategies();
        $this->registerDefaultStorageAdapters();
    }

    /**
     * Create a new backup
     */
    public function backup(string $name): BackupBuilder
    {
        $this->log('info', "Creating backup: $name");
        
        $config = new BackupConfig($name, $this->getCurrentDatabase());
        return new BackupBuilder($this, $config);
    }

    /**
     * Restore from a backup
     */
    public function restore(string $backupId): RestoreBuilder
    {
        $this->log('info', "Preparing to restore backup: $backupId");
        
        $backup = $this->getBackupById($backupId);
        if (!$backup) {
            throw new BackupException("Backup not found: $backupId");
        }
        
        return new RestoreBuilder($this, $backup);
    }

    /**
     * List all backups
     */
    public function list(): array
    {
        if (!empty($this->loadedBackups)) {
            return $this->loadedBackups;
        }

        try {
            $escapedTable = "`{$this->backupTable}`";
            $sql = "SELECT * FROM {$escapedTable} ORDER BY created_at DESC";
            $results = $this->db->query($sql)->fetchAll('assoc');
            
            $backups = [];
            foreach ($results as $row) {
                $backups[] = $this->createBackupFromRow($row);
            }
            
            $this->loadedBackups = $backups;
            return $backups;
            
        } catch (\Exception $e) {
            $this->log('error', "Failed to list backups", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get backup by ID
     */
    public function getBackupById(string $id): ?BackupMetadata
    {
        $backups = $this->list();
        
        foreach ($backups as $backup) {
            if ($backup->getId() === $id) {
                return $backup;
            }
        }
        
        return null;
    }

    /**
     * Delete a backup
     */
    public function delete(string $backupId): bool
    {
        try {
            $backup = $this->getBackupById($backupId);
            if (!$backup) {
                throw new BackupException("Backup not found: $backupId");
            }

            // Delete from storage
            $storage = $this->getStorageAdapter($backup->getStorageType());
            $storage->delete($backupId);

            // Delete from database
            $escapedTable = "`{$this->backupTable}`";
            $this->db->delete($escapedTable, 'id = ?', [$backupId]);

            // Clear cache
            $this->loadedBackups = [];

            $this->log('info', "Deleted backup: $backupId");
            return true;

        } catch (\Exception $e) {
            $this->log('error', "Failed to delete backup: $backupId", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verify backup integrity
     */
    public function verify(string $backupId): bool
    {
        try {
            $backup = $this->getBackupById($backupId);
            if (!$backup) {
                return false;
            }

            $storage = $this->getStorageAdapter($backup->getStorageType());
            if (!$storage->exists($backupId)) {
                return false;
            }

            // Verify checksum if available
            if ($backup->getChecksum()) {
                $data = $storage->retrieve($backupId);
                $calculatedChecksum = hash('sha256', $data);
                return $calculatedChecksum === $backup->getChecksum();
            }

            return true;

        } catch (\Exception $e) {
            $this->log('error', "Failed to verify backup: $backupId", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Execute backup with given configuration
     */
    public function executeBackup(BackupConfig $config): BackupResult
    {
        $startTime = microtime(true);
        $this->log('info', "Starting backup execution", ['name' => $config->getName()]);

        try {
            // Get appropriate strategy (with optional enhancements)
            $strategy = $this->getStrategyWithEnhancements($config);
            
            // Setup enhanced storage if encryption is requested
            $this->setupEnhancedStorage($config);
            
            // Execute backup
            $result = $strategy->execute($config);
            
            if ($result->isSuccess()) {
                // Generate migrations if requested
                if ($config->shouldGenerateMigrations()) {
                    $this->generateMigrations($config, $result);
                }
                
                // Record backup metadata
                $this->recordBackup($result, $config);
                
                // Clear cache to include new backup
                $this->loadedBackups = [];
                
                $duration = microtime(true) - $startTime;
                $this->log('info', "Backup completed successfully", [
                    'name' => $config->getName(),
                    'duration' => $duration,
                    'size' => $result->getFormattedSize()
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->log('error', "Backup failed", [
                'name' => $config->getName(),
                'duration' => $duration,
                'error' => $e->getMessage()
            ]);
            
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
     * Get backup strategy for type
     */
    private function getStrategy(BackupType $type): BackupStrategy
    {
        $strategyKey = $type->value;
        
        if (!isset($this->strategies[$strategyKey])) {
            throw new BackupException("No strategy registered for backup type: {$type->value}");
        }
        
        return $this->strategies[$strategyKey];
    }

    /**
     * Get strategy with optional enhancements (streaming, etc.)
     */
    private function getStrategyWithEnhancements(BackupConfig $config): BackupStrategy
    {
        $options = $config->getStorageOptions();
        
        // Use streaming strategy if requested
        if (isset($options['use_streaming']) && $options['use_streaming']) {
            $chunkSize = $options['chunk_size'] ?? 1000;
            $streamingStrategy = new \SimpleMDB\Backup\Strategies\StreamingMySQLDumpStrategy($this->db, $chunkSize);
            return $streamingStrategy;
        }
        
        // Otherwise use default strategy
        return $this->getStrategy($config->getType());
    }

    /**
     * Setup enhanced storage with encryption if requested
     */
    private function setupEnhancedStorage(BackupConfig $config): void
    {
        $options = $config->getStorageOptions();
        
        // Setup encryption if requested
        if (isset($options['encryption_enabled']) && $options['encryption_enabled']) {
            $encryptionKey = $options['encryption_key'] ?? null;
            $cipher = $options['encryption_cipher'] ?? 'aes-256-cbc';
            
            if (!$encryptionKey) {
                throw new BackupException('Encryption key is required when encryption is enabled');
            }
            
            // Wrap existing storage with encryption
            $storageType = $this->extractStorageType($config->getStorageLocation());
            $baseStorage = $this->getStorageAdapter($storageType);
            
            $encryptedStorage = new \SimpleMDB\Backup\Storage\EncryptedStorageAdapter(
                $baseStorage,
                $encryptionKey,
                $cipher
            );
            
            // Register the encrypted storage adapter temporarily
            $this->registerStorageAdapter($storageType . '_encrypted', $encryptedStorage);
        }
    }

    /**
     * Get storage adapter
     */
    private function getStorageAdapter(string $type): \SimpleMDB\Backup\Storage\StorageInterface
    {
        if (!isset($this->storageAdapters[$type])) {
            throw new BackupException("No storage adapter registered for type: $type");
        }
        
        return $this->storageAdapters[$type];
    }

    /**
     * Register backup strategy
     */
    public function registerStrategy(BackupType $type, BackupStrategy $strategy): self
    {
        $this->strategies[$type->value] = $strategy;
        return $this;
    }

    /**
     * Register storage adapter
     */
    public function registerStorageAdapter(string $type, \SimpleMDB\Backup\Storage\StorageInterface $adapter): self
    {
        $this->storageAdapters[$type] = $adapter;
        return $this;
    }

    /**
     * Register default strategies
     */
    private function registerDefaultStrategies(): void
    {
        $mysqlStrategy = new MySQLDumpStrategy($this->db);
        
        $this->registerStrategy(BackupType::FULL, $mysqlStrategy);
        $this->registerStrategy(BackupType::SCHEMA_ONLY, $mysqlStrategy);
        $this->registerStrategy(BackupType::DATA_ONLY, $mysqlStrategy);
        
        // Register enhanced strategies (optional - automatically used when requested)
        $streamingStrategy = new \SimpleMDB\Backup\Strategies\StreamingMySQLDumpStrategy($this->db);
        $this->registerStrategy(BackupType::INCREMENTAL, $streamingStrategy);
        $this->registerStrategy(BackupType::DIFFERENTIAL, $streamingStrategy);
    }

    /**
     * Register default storage adapters
     */
    private function registerDefaultStorageAdapters(): void
    {
        $this->registerStorageAdapter('local', new LocalStorage($this->backupsPath));
    }

    /**
     * Ensure backup table exists
     */
    private function ensureBackupTable(): void
    {
        try {
            $schema = new SchemaBuilder($this->db);
            
            if (!$schema->hasTable($this->backupTable)) {
                $schema->string('id', 255)
                       ->string('name')
                       ->string('database_name')
                       ->string('type', 50)
                       ->bigInteger('size')->unsigned()
                       ->string('checksum')->nullable()
                       ->string('storage_type', 50)->default('local')
                       ->string('storage_path')
                       ->text('metadata')->nullable()
                       ->datetime('created_at')
                       ->primaryKey('id')
                       ->index(['name', 'created_at'], 'name_created_index')
                       ->index(['type'], 'type_index')
                       ->createTable($this->backupTable);

                $this->log('info', "Created backups table: {$this->backupTable}");
            }
        } catch (\Exception $e) {
            // Table might already exist, try to verify it exists
            try {
                $this->db->query("SELECT 1 FROM `{$this->backupTable}` LIMIT 1");
                $this->log('info', "Backups table already exists: {$this->backupTable}");
            } catch (\Exception $verifyException) {
                throw new BackupException("Failed to create or verify backup table: " . $e->getMessage());
            }
        }
    }

    /**
     * Record backup metadata
     */
    private function recordBackup(BackupResult $result, BackupConfig $config): void
    {
        $data = [
            'id' => $result->getId(),
            'name' => $result->getName(),
            'database_name' => $config->getDatabase(),
            'type' => $config->getType()->value,
            'size' => $result->getSize(),
            'checksum' => $result->getChecksum(),
            'storage_type' => $this->extractStorageType($config->getStorageLocation()),
            'storage_path' => $result->getPath(),
            'metadata' => json_encode($result->getMetadata()),
            'created_at' => $result->getCreatedAt()->format('Y-m-d H:i:s')
        ];

        $escapedTable = "`{$this->backupTable}`";
        $this->db->write_data($escapedTable, $data);
    }

    /**
     * Create backup metadata from database row
     */
    private function createBackupFromRow(array $row): BackupMetadata
    {
        return new BackupMetadata(
            $row['id'],
            $row['name'],
            $row['database_name'],
            BackupType::from($row['type']),
            new DateTime($row['created_at']),
            (int)$row['size'],
            $row['checksum'] ?? '',
            $row['storage_type'],
            $row['storage_path'],
            json_decode($row['metadata'] ?? '[]', true)
        );
    }

    /**
     * Extract storage type from location URL
     */
    private function extractStorageType(string $location): string
    {
        if (str_starts_with($location, 'local://')) {
            return 'local';
        } elseif (str_starts_with($location, 's3://')) {
            return 's3';
        } elseif (str_starts_with($location, 'ftp://')) {
            return 'ftp';
        } else {
            return 'local'; // Default
        }
    }

    /**
     * Get current database name
     */
    private function getCurrentDatabase(): string
    {
        try {
            $result = $this->db->query("SELECT DATABASE() as db_name")->fetch('assoc');
            return $result['db_name'] ?? 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Set backup table name
     */
    public function setBackupTable(string $tableName): self
    {
        $this->backupTable = $tableName;
        return $this;
    }

    /**
     * Set backups path
     */
    public function setBackupsPath(string $path): self
    {
        $this->backupsPath = rtrim($path, '/');
        $this->loadedBackups = []; // Reset cache
        return $this;
    }

    /**
     * Generate migrations from current database schema
     */
    private function generateMigrations(BackupConfig $config, BackupResult $result): void
    {
        try {
            $this->log('info', "Generating migrations", ['backup' => $config->getName()]);
            
            // Initialize schema analyzer if not already done
            if (!$this->schemaAnalyzer) {
                $this->schemaAnalyzer = new SchemaAnalyzer($this->db, $config->getDatabase());
            }
            
            // Analyze database schema
            $schemaData = $this->schemaAnalyzer->analyzeDatabase();
            
            // Filter tables if specified
            if ($config->getIncludeTables()) {
                $schemaData['tables'] = array_intersect_key(
                    $schemaData['tables'], 
                    array_flip($config->getIncludeTables())
                );
            }
            
            if ($config->getExcludeTables()) {
                $schemaData['tables'] = array_diff_key(
                    $schemaData['tables'], 
                    array_flip($config->getExcludeTables())
                );
            }
            
            // Initialize migration generator if not already done
            if (!$this->migrationGenerator) {
                $options = [
                    'expressive_syntax' => $config->getStorageOptions()['use_expressive_syntax'] ?? true,
                    'split_tables' => $config->getTablesPerFile() < count($schemaData['tables']),
                    'tables_per_file' => $config->getTablesPerFile(),
                    'use_comments' => true,
                    'preserve_order' => true,
                    'generate_indexes' => true,
                    'generate_foreign_keys' => true
                ];
                
                $this->migrationGenerator = new MigrationGenerator($schemaData, $options);
            } else {
                // Update with new schema data
                $this->migrationGenerator->__construct($schemaData, [
                    'expressive_syntax' => $config->getStorageOptions()['use_expressive_syntax'] ?? true,
                    'split_tables' => $config->getTablesPerFile() < count($schemaData['tables']),
                    'tables_per_file' => $config->getTablesPerFile(),
                    'use_comments' => true,
                    'preserve_order' => true,
                    'generate_indexes' => true,
                    'generate_foreign_keys' => true
                ]);
            }
            
            // Generate migrations
            $migrationsPath = $this->backupsPath . '/migrations/' . $config->getName();
            $generatedFiles = $this->migrationGenerator->generateMigrations($migrationsPath);
            
            $this->log('info', "Migrations generated successfully", [
                'backup' => $config->getName(),
                'files_generated' => count($generatedFiles),
                'output_path' => $migrationsPath
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', "Failed to generate migrations", [
                'backup' => $config->getName(),
                'error' => $e->getMessage()
            ]);
            // Don't fail the backup if migration generation fails
        }
    }
} 