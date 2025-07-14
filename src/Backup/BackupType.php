<?php

namespace SimpleMDB\Backup;

/**
 * Enumeration of backup types
 */
enum BackupType: string
{
    case FULL = 'full';           // Complete database (schema + data)
    case SCHEMA_ONLY = 'schema';  // Database structure only
    case DATA_ONLY = 'data';      // Data only (no schema)
    case INCREMENTAL = 'incremental'; // Changes since last backup
    case DIFFERENTIAL = 'differential'; // Changes since last full backup

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::FULL => 'Full backup (schema and data)',
            self::SCHEMA_ONLY => 'Schema only (structure without data)',
            self::DATA_ONLY => 'Data only (without schema)',
            self::INCREMENTAL => 'Incremental (changes since last backup)',
            self::DIFFERENTIAL => 'Differential (changes since last full backup)'
        };
    }

    /**
     * Check if backup type includes schema
     */
    public function includesSchema(): bool
    {
        return match($this) {
            self::FULL, self::SCHEMA_ONLY => true,
            self::DATA_ONLY, self::INCREMENTAL, self::DIFFERENTIAL => false
        };
    }

    /**
     * Check if backup type includes data
     */
    public function includesData(): bool
    {
        return match($this) {
            self::FULL, self::DATA_ONLY, self::INCREMENTAL, self::DIFFERENTIAL => true,
            self::SCHEMA_ONLY => false
        };
    }

    /**
     * Get all available backup types
     */
    public static function getAllTypes(): array
    {
        return [
            self::FULL,
            self::SCHEMA_ONLY,
            self::DATA_ONLY,
            self::INCREMENTAL,
            self::DIFFERENTIAL
        ];
    }
} 