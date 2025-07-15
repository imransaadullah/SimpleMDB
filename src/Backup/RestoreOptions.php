<?php

namespace SimpleMDB\Backup;

use DateTime;

/**
 * Advanced restore options for point-in-time recovery and selective restoration
 * 
 * This class provides enhanced restore capabilities while maintaining
 * backward compatibility with the existing RestoreBuilder API.
 */
class RestoreOptions
{
    private ?DateTime $pointInTime = null;
    private array $selectedTables = [];
    private array $excludedTables = [];
    private array $dataFilters = [];
    private bool $schemaOnly = false;
    private bool $dataOnly = false;
    private bool $createSnapshot = false;
    private bool $verifyBeforeRestore = true;
    private bool $skipForeignKeyChecks = true;
    private bool $dropExistingTables = false;
    private ?string $targetDatabase = null;
    private array $tableMapping = [];
    private ?string $binaryLogPath = null;
    private array $customSQLBefore = [];
    private array $customSQLAfter = [];

    /**
     * Set point-in-time for recovery
     */
    public function pointInTime(DateTime $timestamp): self
    {
        $this->pointInTime = $timestamp;
        return $this;
    }

    /**
     * Get point-in-time timestamp
     */
    public function getPointInTime(): ?DateTime
    {
        return $this->pointInTime;
    }

    /**
     * Select specific tables to restore
     */
    public function onlyTables(array $tables): self
    {
        $this->selectedTables = $tables;
        return $this;
    }

    /**
     * Get selected tables
     */
    public function getSelectedTables(): array
    {
        return $this->selectedTables;
    }

    /**
     * Exclude specific tables from restore
     */
    public function excludeTables(array $tables): self
    {
        $this->excludedTables = $tables;
        return $this;
    }

    /**
     * Get excluded tables
     */
    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }

    /**
     * Add data filters for selective restore
     */
    public function withDataFilters(array $filters): self
    {
        $this->dataFilters = $filters;
        return $this;
    }

    /**
     * Add data filter for specific table
     */
    public function addDataFilter(string $table, string $condition): self
    {
        $this->dataFilters[$table] = $condition;
        return $this;
    }

    /**
     * Get data filters
     */
    public function getDataFilters(): array
    {
        return $this->dataFilters;
    }

    /**
     * Restore schema only
     */
    public function schemaOnly(): self
    {
        $this->schemaOnly = true;
        $this->dataOnly = false;
        return $this;
    }

    /**
     * Check if schema only restore
     */
    public function isSchemaOnly(): bool
    {
        return $this->schemaOnly;
    }

    /**
     * Restore data only
     */
    public function dataOnly(): self
    {
        $this->dataOnly = true;
        $this->schemaOnly = false;
        return $this;
    }

    /**
     * Check if data only restore
     */
    public function isDataOnly(): bool
    {
        return $this->dataOnly;
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
     * Check if should create snapshot
     */
    public function shouldCreateSnapshot(): bool
    {
        return $this->createSnapshot;
    }

    /**
     * Skip verification before restore
     */
    public function skipVerification(): self
    {
        $this->verifyBeforeRestore = false;
        return $this;
    }

    /**
     * Check if should verify before restore
     */
    public function shouldVerifyBeforeRestore(): bool
    {
        return $this->verifyBeforeRestore;
    }

    /**
     * Skip foreign key checks during restore
     */
    public function skipForeignKeyChecks(): self
    {
        $this->skipForeignKeyChecks = true;
        return $this;
    }

    /**
     * Enable foreign key checks during restore
     */
    public function enableForeignKeyChecks(): self
    {
        $this->skipForeignKeyChecks = false;
        return $this;
    }

    /**
     * Check if should skip foreign key checks
     */
    public function shouldSkipForeignKeyChecks(): bool
    {
        return $this->skipForeignKeyChecks;
    }

    /**
     * Drop existing tables before restore
     */
    public function dropExistingTables(): self
    {
        $this->dropExistingTables = true;
        return $this;
    }

    /**
     * Check if should drop existing tables
     */
    public function shouldDropExistingTables(): bool
    {
        return $this->dropExistingTables;
    }

    /**
     * Set target database for restore
     */
    public function toDatabase(string $database): self
    {
        $this->targetDatabase = $database;
        return $this;
    }

    /**
     * Get target database
     */
    public function getTargetDatabase(): ?string
    {
        return $this->targetDatabase;
    }

    /**
     * Map tables to different names during restore
     */
    public function mapTables(array $mapping): self
    {
        $this->tableMapping = $mapping;
        return $this;
    }

    /**
     * Map single table to different name
     */
    public function mapTable(string $from, string $to): self
    {
        $this->tableMapping[$from] = $to;
        return $this;
    }

    /**
     * Get table mapping
     */
    public function getTableMapping(): array
    {
        return $this->tableMapping;
    }

    /**
     * Set binary log path for point-in-time recovery
     */
    public function withBinaryLogs(string $path): self
    {
        $this->binaryLogPath = $path;
        return $this;
    }

    /**
     * Get binary log path
     */
    public function getBinaryLogPath(): ?string
    {
        return $this->binaryLogPath;
    }

    /**
     * Add custom SQL to execute before restore
     */
    public function addCustomSQLBefore(string $sql): self
    {
        $this->customSQLBefore[] = $sql;
        return $this;
    }

    /**
     * Add custom SQL to execute after restore
     */
    public function addCustomSQLAfter(string $sql): self
    {
        $this->customSQLAfter[] = $sql;
        return $this;
    }

    /**
     * Get custom SQL to execute before restore
     */
    public function getCustomSQLBefore(): array
    {
        return $this->customSQLBefore;
    }

    /**
     * Get custom SQL to execute after restore
     */
    public function getCustomSQLAfter(): array
    {
        return $this->customSQLAfter;
    }

    /**
     * Check if this is a selective restore
     */
    public function isSelectiveRestore(): bool
    {
        return !empty($this->selectedTables) || 
               !empty($this->excludedTables) || 
               !empty($this->dataFilters) ||
               !empty($this->tableMapping) ||
               $this->schemaOnly ||
               $this->dataOnly;
    }

    /**
     * Check if point-in-time recovery is requested
     */
    public function isPointInTimeRecovery(): bool
    {
        return $this->pointInTime !== null;
    }

    /**
     * Get all options as array for serialization
     */
    public function toArray(): array
    {
        return [
            'point_in_time' => $this->pointInTime?->format('Y-m-d H:i:s'),
            'selected_tables' => $this->selectedTables,
            'excluded_tables' => $this->excludedTables,
            'data_filters' => $this->dataFilters,
            'schema_only' => $this->schemaOnly,
            'data_only' => $this->dataOnly,
            'create_snapshot' => $this->createSnapshot,
            'verify_before_restore' => $this->verifyBeforeRestore,
            'skip_foreign_key_checks' => $this->skipForeignKeyChecks,
            'drop_existing_tables' => $this->dropExistingTables,
            'target_database' => $this->targetDatabase,
            'table_mapping' => $this->tableMapping,
            'binary_log_path' => $this->binaryLogPath,
            'custom_sql_before' => $this->customSQLBefore,
            'custom_sql_after' => $this->customSQLAfter
        ];
    }

    /**
     * Create RestoreOptions from array
     */
    public static function fromArray(array $data): self
    {
        $options = new self();
        
        if (isset($data['point_in_time']) && $data['point_in_time']) {
            $options->pointInTime(new DateTime($data['point_in_time']));
        }
        
        if (isset($data['selected_tables'])) {
            $options->onlyTables($data['selected_tables']);
        }
        
        if (isset($data['excluded_tables'])) {
            $options->excludeTables($data['excluded_tables']);
        }
        
        if (isset($data['data_filters'])) {
            $options->withDataFilters($data['data_filters']);
        }
        
        if (isset($data['schema_only']) && $data['schema_only']) {
            $options->schemaOnly();
        }
        
        if (isset($data['data_only']) && $data['data_only']) {
            $options->dataOnly();
        }
        
        if (isset($data['create_snapshot']) && $data['create_snapshot']) {
            $options->createSnapshot();
        }
        
        if (isset($data['verify_before_restore'])) {
            if (!$data['verify_before_restore']) {
                $options->skipVerification();
            }
        }
        
        if (isset($data['skip_foreign_key_checks'])) {
            if ($data['skip_foreign_key_checks']) {
                $options->skipForeignKeyChecks();
            } else {
                $options->enableForeignKeyChecks();
            }
        }
        
        if (isset($data['drop_existing_tables']) && $data['drop_existing_tables']) {
            $options->dropExistingTables();
        }
        
        if (isset($data['target_database'])) {
            $options->toDatabase($data['target_database']);
        }
        
        if (isset($data['table_mapping'])) {
            $options->mapTables($data['table_mapping']);
        }
        
        if (isset($data['binary_log_path'])) {
            $options->withBinaryLogs($data['binary_log_path']);
        }
        
        if (isset($data['custom_sql_before'])) {
            foreach ($data['custom_sql_before'] as $sql) {
                $options->addCustomSQLBefore($sql);
            }
        }
        
        if (isset($data['custom_sql_after'])) {
            foreach ($data['custom_sql_after'] as $sql) {
                $options->addCustomSQLAfter($sql);
            }
        }
        
        return $options;
    }
} 