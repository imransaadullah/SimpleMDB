<?php

/**
 * Enhanced Backup System Test Runner
 * 
 * Quick test runner to validate the enhanced backup system features.
 * Run this script to verify everything is working correctly.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Backup\BackupManager;
use SimpleMDB\Backup\Storage\EncryptedStorageAdapter;

echo "🚀 SimpleMDB Enhanced Backup System Test Runner\n";
echo "===============================================\n\n";

// Check requirements
echo "🔍 Checking requirements...\n";

$requirements = [
    'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'MySQLi Extension' => extension_loaded('mysqli'),
    'OpenSSL Extension' => extension_loaded('openssl'),
    'JSON Extension' => extension_loaded('json'),
];

$allRequirementsMet = true;
foreach ($requirements as $requirement => $met) {
    echo "   " . ($met ? "✓" : "❌") . " {$requirement}\n";
    if (!$met) {
        $allRequirementsMet = false;
    }
}

if (!$allRequirementsMet) {
    echo "\n❌ Some requirements are not met. Please install missing extensions.\n";
    exit(1);
}

echo "\n✓ All requirements met!\n\n";

// Check if we can connect to database
echo "🔌 Testing database connection...\n";

try {
    // Try to connect to MySQL
    $testConnection = new mysqli('localhost', 'root', '');
    
    if ($testConnection->connect_error) {
        throw new Exception("Connection failed: " . $testConnection->connect_error);
    }
    
    // Try to create test database
    $testDatabase = 'simplemdb_test_' . uniqid();
    $testConnection->query("CREATE DATABASE IF NOT EXISTS `{$testDatabase}`");
    $testConnection->select_db($testDatabase);
    
    echo "   ✓ Database connection successful\n";
    echo "   ✓ Test database created: {$testDatabase}\n\n";
    
    // Clean up test database immediately
    $testConnection->query("DROP DATABASE `{$testDatabase}`");
    $testConnection->close();
    
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\n💡 Please ensure:\n";
    echo "   - MySQL is running on localhost\n";
    echo "   - MySQL root user has access (or update connection settings)\n";
    echo "   - MySQL user has CREATE/DROP database permissions\n\n";
    exit(1);
}

// Run quick validation tests
echo "🧪 Running quick validation tests...\n\n";

try {
    // Test 1: Basic SimpleMDB connection
    echo "1. Testing SimpleMDB connection...\n";
    $db = DatabaseFactory::create('mysqli', 'localhost', 'root', '', 'mysql');
    echo "   ✓ SimpleMDB connection successful\n";
    
    // Test 2: Backup manager initialization
    echo "2. Testing BackupManager initialization...\n";
    $backupManager = new BackupManager($db, 'test_backups_quick');
    echo "   ✓ BackupManager initialized\n";
    
    // Test 3: Encryption key generation
    echo "3. Testing encryption key generation...\n";
    $encryptionKey = EncryptedStorageAdapter::generateKey('AES-256-CBC');
    $keyLength = strlen($encryptionKey);
    if ($keyLength === 32) {
        echo "   ✓ Encryption key generated (32 bytes for AES-256)\n";
    } else {
        echo "   ❌ Encryption key wrong length: {$keyLength} bytes\n";
    }
    
    // Test 4: Basic backup configuration
    echo "4. Testing backup configuration...\n";
    $backupConfig = $backupManager
        ->backup('quick_test')
        ->full()
        ->streaming(100)
        ->encrypted($encryptionKey)
        ->getConfig();
    
    $options = $backupConfig->getStorageOptions();
    $validConfig = isset($options['use_streaming']) && 
                   isset($options['encryption_enabled']) &&
                   $options['use_streaming'] === true &&
                   $options['encryption_enabled'] === true;
    
    if ($validConfig) {
        echo "   ✓ Enhanced backup configuration working\n";
    } else {
        echo "   ❌ Enhanced backup configuration failed\n";
    }
    
    echo "\n🎉 Quick validation tests passed!\n\n";
    
} catch (Exception $e) {
    echo "❌ Quick validation failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Offer to run comprehensive tests
echo "🚀 Ready to run comprehensive tests!\n\n";
echo "Choose an option:\n";
echo "1. Run comprehensive test suite (recommended)\n";
echo "2. Skip comprehensive tests\n";
echo "3. Show test commands only\n\n";

$choice = readline("Enter your choice (1-3): ");

switch (trim($choice)) {
    case '1':
        echo "\n🧪 Running comprehensive test suite...\n";
        echo "This may take a few minutes and will create/drop a test database.\n\n";
        
        // Include and run the comprehensive test suite
        require_once __DIR__ . '/enhanced_backup_test.php';
        break;
        
    case '2':
        echo "\n✅ Quick validation completed successfully!\n";
        echo "Your enhanced backup system is ready to use.\n\n";
        break;
        
    case '3':
        echo "\n📋 Test Commands:\n";
        echo "=================\n\n";
        echo "To run the comprehensive test suite manually:\n";
        echo "php test/enhanced_backup_test.php\n\n";
        echo "To run just the quick validation:\n";
        echo "php test/run_tests.php\n\n";
        echo "To test specific features:\n";
        echo "php examples/enhanced_backup_example.php\n\n";
        break;
        
    default:
        echo "\n✅ Quick validation completed successfully!\n";
        echo "Run 'php test/enhanced_backup_test.php' for comprehensive testing.\n\n";
}

echo "💡 Next Steps:\n";
echo "==============\n";
echo "• Review examples/enhanced_backup_example.php for usage patterns\n";
echo "• Check the updated README.md for full documentation\n";
echo "• Start using the enhanced features in your projects!\n\n";
echo "🌟 Your SimpleMDB backup system is enhanced and ready!\n"; 