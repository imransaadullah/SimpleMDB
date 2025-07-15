<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Backup\BackupManager;
use SimpleMDB\Backup\Storage\EncryptedStorageAdapter;
use SimpleMDB\Backup\Storage\LocalStorage;

/**
 * Enhanced Backup System Example
 * 
 * This example demonstrates the new performance optimizations, encryption,
 * and enhanced restore capabilities while maintaining 100% backward compatibility
 * with existing backup code.
 */

// Database connection (same as before - no changes)
$db = DatabaseFactory::create(
    'mysqli',        // Database type
    'localhost',     // Host
    'root',          // Username
    '',              // Password
    'my_database'    // Database name
);

$backupManager = new BackupManager($db, 'backups/');

echo "=== Enhanced Backup System Examples ===\n\n";

// 1. EXISTING CODE STILL WORKS (100% backward compatible)
echo "1. Traditional backup (existing code unchanged):\n";
$traditionalBackup = $backupManager
    ->backup('traditional_backup')
    ->full()
    ->compress()
    ->execute();

if ($traditionalBackup->isSuccess()) {
    echo "✓ Traditional backup successful: {$traditionalBackup->getFormattedSize()}\n";
    echo "  Path: {$traditionalBackup->getPath()}\n";
} else {
    echo "✗ Traditional backup failed: {$traditionalBackup->getErrorMessage()}\n";
}

echo "\n";

// 2. NEW PERFORMANCE OPTIMIZATION (optional streaming)
echo "2. Memory-efficient streaming backup (NEW - optional):\n";
$streamingBackup = $backupManager
    ->backup('streaming_backup')
    ->full()
    ->streaming(500) // Process 500 rows at a time (optional enhancement)
    ->compress('gzip')
    ->execute();

if ($streamingBackup->isSuccess()) {
    echo "✓ Streaming backup successful: {$streamingBackup->getFormattedSize()}\n";
    echo "  Strategy: " . ($streamingBackup->getMetadata()['strategy'] ?? 'default') . "\n";
    echo "  Memory efficient: " . ($streamingBackup->getMetadata()['memory_efficient'] ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ Streaming backup failed: {$streamingBackup->getErrorMessage()}\n";
}

echo "\n";

// 3. NEW ENCRYPTION AT REST (optional security)
echo "3. Encrypted backup (NEW - optional):\n";

// Generate secure encryption key
$encryptionKey = EncryptedStorageAdapter::generateKey('aes-256-cbc');
$encodedKey = EncryptedStorageAdapter::encodeKey($encryptionKey);
echo "Generated encryption key: " . substr($encodedKey, 0, 20) . "...\n";

$encryptedBackup = $backupManager
    ->backup('encrypted_backup')
    ->full()
    ->encrypted($encryptionKey, 'aes-256-cbc') // NEW - optional encryption
    ->compress()
    ->execute();

if ($encryptedBackup->isSuccess()) {
    echo "✓ Encrypted backup successful: {$encryptedBackup->getFormattedSize()}\n";
    echo "  Encryption: " . ($encryptedBackup->getMetadata()['encryption'] ?? 'none') . "\n";
} else {
    echo "✗ Encrypted backup failed: {$encryptedBackup->getErrorMessage()}\n";
}

echo "\n";

// 4. COMBINED ENHANCEMENTS (streaming + encryption)
echo "4. High-performance encrypted backup (NEW - combined features):\n";
$advancedBackup = $backupManager
    ->backup('advanced_backup')
    ->full()
    ->streaming(1000)          // Memory efficient
    ->encrypted($encryptionKey) // Secure
    ->compress('gzip')         // Space efficient
    ->execute();

if ($advancedBackup->isSuccess()) {
    echo "✓ Advanced backup successful: {$advancedBackup->getFormattedSize()}\n";
    echo "  Features: Streaming + Encryption + Compression\n";
} else {
    echo "✗ Advanced backup failed: {$advancedBackup->getErrorMessage()}\n";
}

echo "\n";

// 5. LIST ALL BACKUPS (existing functionality)
echo "5. All backups:\n";
$backups = $backupManager->list();
foreach ($backups as $backup) {
    echo "  - {$backup->getName()} ({$backup->getFormattedSize()}) - {$backup->getCreatedAt()->format('Y-m-d H:i:s')}\n";
}

echo "\n";

// 6. EXISTING RESTORE WORKS UNCHANGED
echo "6. Traditional restore (existing code unchanged):\n";
if (!empty($backups)) {
    $firstBackup = $backups[0];
    
    // Traditional restore (existing API - no changes)
    try {
        $restore = $backupManager
            ->restore($firstBackup->getId())
            ->to('restored_database')
            ->verify(); // This would normally execute, but we'll skip for the example
        
        echo "✓ Restore configuration created successfully\n";
        echo "  Backup: {$firstBackup->getName()}\n";
        echo "  Target: restored_database\n";
        // Note: Not executing to avoid database changes in example
        
    } catch (Exception $e) {
        echo "✗ Restore configuration failed: {$e->getMessage()}\n";
    }
}

echo "\n";

// 7. PERFORMANCE COMPARISON
echo "7. Performance comparison:\n";
echo "Traditional backups: Load entire table into memory\n";
echo "Streaming backups: Process in small chunks, constant memory usage\n";
echo "Encrypted backups: Transparent encryption, minimal performance impact\n";

echo "\n=== Key Benefits ===\n";
echo "• 100% Backward Compatible: All existing code works unchanged\n";
echo "• Memory Efficient: Streaming handles large tables without memory issues\n";
echo "• Secure: Optional encryption at rest with industry-standard ciphers\n";
echo "• Performance: Significant improvements for large databases\n";
echo "• Pure Framework: No external dependencies or infrastructure requirements\n";

echo "\n=== Usage Notes ===\n";
echo "• Use streaming() for large tables (>100MB)\n";
echo "• Use encrypted() for sensitive data\n";
echo "• Combine features as needed\n";
echo "• All features are optional - use what you need\n";
echo "• Existing backups continue to work without any changes\n";

echo "\nExample completed successfully!\n"; 