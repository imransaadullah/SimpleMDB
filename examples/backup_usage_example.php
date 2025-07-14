<?php

/**
 * SimpleMDB Backup System Usage Example
 * 
 * This example demonstrates how to use the new backup system
 * with expressive syntax and migration generation capabilities.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Backup\BackupManager;

// Create database connection
$db = DatabaseFactory::create('pdo', 'localhost', 'root', '', 'healthcare_db', 'utf8mb4');

// Create backup manager
$backupManager = new BackupManager($db, '/path/to/backups');

echo "🔥 SimpleMDB Backup System Demo\n";
echo str_repeat("=", 50) . "\n\n";

// Example 1: Basic Full Backup
echo "📦 Example 1: Basic Full Backup\n";
echo str_repeat("-", 30) . "\n";

$result = $backupManager->backup('daily_healthcare_backup')
                       ->description('Daily backup of healthcare database')
                       ->tag('production')
                       ->compress('gzip')
                       ->verify()
                       ->execute();

if ($result->isSuccess()) {
    echo "✅ Backup completed successfully!\n";
    echo "   ID: {$result->getId()}\n";
    echo "   Size: {$result->getFormattedSize()}\n";
    echo "   Duration: {$result->getFormattedDuration()}\n";
} else {
    echo "❌ Backup failed: {$result->getErrorMessage()}\n";
}

echo "\n";

// Example 2: Schema-Only Backup with Migration Generation
echo "🏗️  Example 2: Schema Backup with Migration Generation\n";
echo str_repeat("-", 50) . "\n";

$result = $backupManager->backup('schema_snapshot')
                       ->schemaOnly()
                       ->generateMigrations()
                       ->splitLargeFiles(25)  // 25 tables per file
                       ->useExpressiveSyntax()
                       ->outputPath('migrations/')
                       ->tag('development')
                       ->execute();

if ($result->isSuccess()) {
    echo "✅ Schema backup with migrations completed!\n";
    echo "   Generated migration files in: migrations/\n";
    echo "   Tables per file: 25\n";
    echo "   Using expressive SimpleMDB syntax\n";
} else {
    echo "❌ Schema backup failed: {$result->getErrorMessage()}\n";
}

echo "\n";

// Example 3: Selective Table Backup
echo "🎯 Example 3: Selective Table Backup\n";
echo str_repeat("-", 35) . "\n";

$result = $backupManager->backup('user_data_backup')
                       ->includeTables(['users', 'user_profiles', 'user_sessions'])
                       ->compress('gzip')
                       ->encrypt('my-secret-encryption-key')
                       ->description('User-related tables backup')
                       ->execute();

if ($result->isSuccess()) {
    echo "✅ Selective backup completed!\n";
    echo "   Tables included: users, user_profiles, user_sessions\n";
    echo "   Compressed and encrypted\n";
} else {
    echo "❌ Selective backup failed: {$result->getErrorMessage()}\n";
}

echo "\n";

// Example 4: Preview Before Backup
echo "👀 Example 4: Preview Before Backup\n";
echo str_repeat("-", 35) . "\n";

$preview = $backupManager->backup('preview_test')
                        ->dataOnly()
                        ->excludeTables(['logs', 'sessions'])
                        ->compress()
                        ->preview();

echo "📋 Backup Preview:\n";
echo "   Name: {$preview['name']}\n";
echo "   Database: {$preview['database']}\n";
echo "   Type: {$preview['type_description']}\n";
echo "   Compression: {$preview['compression']}\n";
echo "   Encryption: {$preview['encryption']}\n";
echo "   Excluded tables: " . implode(', ', $preview['exclude_tables']) . "\n";

echo "\n";

// Example 5: List All Backups
echo "📋 Example 5: List All Backups\n";
echo str_repeat("-", 30) . "\n";

$backups = $backupManager->list();

if (empty($backups)) {
    echo "No backups found.\n";
} else {
    echo "Found " . count($backups) . " backup(s):\n\n";
    
    foreach ($backups as $backup) {
        echo "  📦 {$backup->getName()}\n";
        echo "     ID: {$backup->getId()}\n";
        echo "     Type: {$backup->getType()->getDescription()}\n";
        echo "     Size: {$backup->getFormattedSize()}\n";
        echo "     Created: {$backup->getCreatedAt()->format('Y-m-d H:i:s')}\n";
        echo "     Database: {$backup->getDatabase()}\n\n";
    }
}

// Example 6: Restore from Backup
echo "🔄 Example 6: Restore from Backup\n";
echo str_repeat("-", 35) . "\n";

if (!empty($backups)) {
    $latestBackup = $backups[0]; // Get the first (latest) backup
    
    $restorePreview = $backupManager->restore($latestBackup->getId())
                                   ->to('healthcare_db_restored')
                                   ->createSnapshot()
                                   ->verify()
                                   ->preview();
    
    echo "🔍 Restore Preview:\n";
    echo "   Backup: {$restorePreview['backup_name']}\n";
    echo "   From: {$restorePreview['source_database']}\n";
    echo "   To: {$restorePreview['target_database']}\n";
    echo "   Will create snapshot: " . ($restorePreview['create_snapshot'] ? 'Yes' : 'No') . "\n";
    echo "   Will verify: " . ($restorePreview['verify_before_restore'] ? 'Yes' : 'No') . "\n";
    
    // Uncomment to actually execute restore
    // $restoreResult = $backupManager->restore($latestBackup->getId())
    //                                ->to('healthcare_db_restored')
    //                                ->createSnapshot()
    //                                ->verify()
    //                                ->execute();
} else {
    echo "No backups available for restore demonstration.\n";
}

echo "\n";

// Example 7: Backup with Migration Generation Features
echo "🧬 Example 7: Advanced Migration Generation\n";
echo str_repeat("-", 45) . "\n";

echo "Creating backup with intelligent migration features:\n\n";

$migrationConfig = $backupManager->backup('advanced_migration_backup')
                                ->full()
                                ->generateMigrations()
                                ->useExpressiveSyntax()
                                ->splitLargeFiles(30)
                                ->maxFileSize(5 * 1024 * 1024) // 5MB max per file
                                ->outputPath('generated_migrations/')
                                ->description('Advanced backup with intelligent migration splitting')
                                ->tag('migration-generation')
                                ->preview();

echo "🎯 Migration Generation Configuration:\n";
echo "   Generate migrations: " . ($migrationConfig['generate_migrations'] ? 'Yes' : 'No') . "\n";
echo "   Tables per file: 30\n";
echo "   Max file size: 5MB\n";
echo "   Output path: generated_migrations/\n";
echo "   Expressive syntax: Yes\n";
echo "   This will create intelligent, readable migration files!\n";

echo "\n";

// Example 8: Storage Statistics
echo "📊 Example 8: Storage Statistics\n";
echo str_repeat("-", 35) . "\n";

// Get storage stats (this would work with actual storage adapter)
echo "📈 Storage Usage:\n";
echo "   Total backups: " . count($backups) . "\n";
echo "   Storage location: /path/to/backups\n";
echo "   Features available:\n";
echo "     ✅ Compression (gzip, bzip2)\n";
echo "     ✅ Encryption (AES-256-CBC)\n";
echo "     ✅ Migration generation\n";
echo "     ✅ Selective table backup\n";
echo "     ✅ Multiple storage adapters\n";
echo "     ✅ Fluent expressive API\n";

echo "\n";
echo "🎉 Backup System Demo Complete!\n";
echo "The SimpleMDB backup system is ready for production use.\n"; 