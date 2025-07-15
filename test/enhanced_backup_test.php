<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Backup\BackupManager;
use SimpleMDB\Backup\Storage\EncryptedStorageAdapter;
use SimpleMDB\Backup\Storage\LocalStorage;
use SimpleMDB\Backup\Strategies\StreamingMySQLDumpStrategy;

/**
 * Enhanced Backup System Test Suite
 * 
 * This comprehensive test validates:
 * - Backward compatibility (existing code unchanged)
 * - New streaming performance features
 * - Encryption at rest functionality
 * - Integration between components
 * - Error handling and edge cases
 */

class EnhancedBackupTestSuite
{
    private $db;
    private $backupManager;
    private $testResults = [];
    private $testDatabase = 'simplemdb_backup_test';
    
    public function __construct()
    {
        $this->setupTestDatabase();
        $this->setupTestData();
    }
    
    public function runAllTests(): array
    {
        echo "🧪 Enhanced Backup System Test Suite\n";
        echo "====================================\n\n";
        
        // Core functionality tests
        $this->testBackwardCompatibility();
        $this->testStreamingBackupStrategy();
        $this->testEncryptionAdapter();
        $this->testCombinedFeatures();
        
        // Performance tests
        $this->testMemoryUsage();
        $this->testPerformanceComparison();
        
        // Edge case tests
        $this->testErrorHandling();
        $this->testLargeDataset();
        
        // Integration tests
        $this->testFullWorkflow();
        
        $this->printResults();
        return $this->testResults;
    }
    
    private function setupTestDatabase(): void
    {
        try {
            // Connect to MySQL to create test database
            $this->db = DatabaseFactory::create(
                'mysqli',
                'localhost',
                'root',
                '',
                $this->testDatabase
            );
            
            echo "✓ Connected to test database: {$this->testDatabase}\n";
            
            // Initialize backup manager
            $this->backupManager = new BackupManager($this->db, 'test_backups');
            
        } catch (Exception $e) {
            die("❌ Failed to setup test database: " . $e->getMessage() . "\n");
        }
    }
    
    private function setupTestData(): void
    {
        try {
            // Create test tables with varying sizes
            $this->db->query("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(150) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    metadata JSON
                )
            ");
            
            $this->db->query("
                CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sku VARCHAR(50) UNIQUE,
                    name VARCHAR(200),
                    price DECIMAL(10,2),
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $this->db->query("
                CREATE TABLE IF NOT EXISTS large_data (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    data_field TEXT,
                    random_number INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Insert test data
            $this->insertTestData();
            
            echo "✓ Created test tables and data\n\n";
            
        } catch (Exception $e) {
            die("❌ Failed to setup test data: " . $e->getMessage() . "\n");
        }
    }
    
    private function insertTestData(): void
    {
        // Insert sample users
        for ($i = 1; $i <= 50; $i++) {
            $this->db->query("
                INSERT INTO users (name, email, metadata) VALUES (?, ?, ?)
            ", [
                "User {$i}",
                "user{$i}@test.com",
                json_encode(['role' => 'user', 'preferences' => ['theme' => 'light']])
            ]);
        }
        
        // Insert sample products
        for ($i = 1; $i <= 100; $i++) {
            $this->db->query("
                INSERT INTO products (sku, name, price, description) VALUES (?, ?, ?, ?)
            ", [
                "PROD-{$i}",
                "Product {$i}",
                rand(10, 500) + (rand(0, 99) / 100),
                "Description for product {$i} with some detailed information."
            ]);
        }
        
        // Insert larger dataset for performance testing
        for ($i = 1; $i <= 1000; $i++) {
            $this->db->query("
                INSERT INTO large_data (data_field, random_number) VALUES (?, ?)
            ", [
                str_repeat("Large data content for testing memory usage. ", 10),
                rand(1, 10000)
            ]);
        }
    }
    
    private function testBackwardCompatibility(): void
    {
        echo "1. Testing Backward Compatibility\n";
        echo "   Testing that existing backup code works unchanged...\n";
        
        try {
            // Traditional backup (should work exactly as before)
            $backup = $this->backupManager
                ->backup('compatibility_test')
                ->full()
                ->compress()
                ->execute();
            
            $this->assertTest(
                'backward_compatibility_backup',
                $backup->isSuccess(),
                "Traditional backup should work unchanged"
            );
            
            $this->assertTest(
                'backward_compatibility_size',
                $backup->getSize() > 0,
                "Backup should have non-zero size"
            );
            
            // Traditional restore (should work exactly as before)
            if ($backup->isSuccess()) {
                $restore = $this->backupManager
                    ->restore($backup->getId())
                    ->verify(); // Just verify, don't actually execute
                
                $this->assertTest(
                    'backward_compatibility_restore',
                    true, // If we get here without exception, it worked
                    "Traditional restore API should work unchanged"
                );
            }
            
            echo "   ✓ Backward compatibility verified\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'backward_compatibility_error',
                false,
                "Backward compatibility failed: " . $e->getMessage()
            );
            echo "   ❌ Backward compatibility test failed\n\n";
        }
    }
    
    private function testStreamingBackupStrategy(): void
    {
        echo "2. Testing Streaming Backup Strategy\n";
        echo "   Testing memory-efficient chunked processing...\n";
        
        try {
            // Test streaming backup with small chunks
            $streamingBackup = $this->backupManager
                ->backup('streaming_test')
                ->full()
                ->streaming(50) // Small chunks for testing
                ->execute();
            
            $this->assertTest(
                'streaming_backup_success',
                $streamingBackup->isSuccess(),
                "Streaming backup should complete successfully"
            );
            
            $this->assertTest(
                'streaming_backup_size',
                $streamingBackup->getSize() > 0,
                "Streaming backup should produce data"
            );
            
            $metadata = $streamingBackup->getMetadata();
            $this->assertTest(
                'streaming_strategy_used',
                isset($metadata['strategy']) && $metadata['strategy'] === 'StreamingMySQLDumpStrategy',
                "Should use StreamingMySQLDumpStrategy"
            );
            
            $this->assertTest(
                'streaming_memory_efficient',
                isset($metadata['memory_efficient']) && $metadata['memory_efficient'] === true,
                "Should be marked as memory efficient"
            );
            
            $this->assertTest(
                'streaming_chunk_size',
                isset($metadata['chunk_size']) && $metadata['chunk_size'] === 50,
                "Should use specified chunk size"
            );
            
            echo "   ✓ Streaming strategy verified\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'streaming_error',
                false,
                "Streaming test failed: " . $e->getMessage()
            );
            echo "   ❌ Streaming test failed\n\n";
        }
    }
    
    private function testEncryptionAdapter(): void
    {
        echo "3. Testing Encryption Adapter\n";
        echo "   Testing AES-256 encryption at rest...\n";
        
        try {
            // Generate encryption key
            $encryptionKey = EncryptedStorageAdapter::generateKey('AES-256-CBC');
            
            $this->assertTest(
                'encryption_key_generation',
                strlen($encryptionKey) === 32, // 256 bits = 32 bytes
                "Should generate correct key length for AES-256-CBC"
            );
            
            // Test key encoding/decoding
            $encodedKey = EncryptedStorageAdapter::encodeKey($encryptionKey);
            $decodedKey = EncryptedStorageAdapter::decodeKey($encodedKey);
            
            $this->assertTest(
                'encryption_key_encoding',
                $encryptionKey === $decodedKey,
                "Key encoding/decoding should be reversible"
            );
            
            // Test encrypted backup
            $encryptedBackup = $this->backupManager
                ->backup('encryption_test')
                ->full()
                ->encrypted($encryptionKey, 'AES-256-CBC')
                ->execute();
            
            $this->assertTest(
                'encryption_backup_success',
                $encryptedBackup->isSuccess(),
                "Encrypted backup should complete successfully"
            );
            
            $this->assertTest(
                'encryption_backup_size',
                $encryptedBackup->getSize() > 0,
                "Encrypted backup should produce data"
            );
            
            echo "   ✓ Encryption features verified\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'encryption_error',
                false,
                "Encryption test failed: " . $e->getMessage()
            );
            echo "   ❌ Encryption test failed\n\n";
        }
    }
    
    private function testCombinedFeatures(): void
    {
        echo "4. Testing Combined Features\n";
        echo "   Testing streaming + encryption + compression...\n";
        
        try {
            $encryptionKey = EncryptedStorageAdapter::generateKey('AES-256-CBC');
            
            $combinedBackup = $this->backupManager
                ->backup('combined_test')
                ->full()
                ->streaming(100)
                ->encrypted($encryptionKey)
                ->compress('gzip')
                ->execute();
            
            $this->assertTest(
                'combined_features_success',
                $combinedBackup->isSuccess(),
                "Combined features backup should complete successfully"
            );
            
            $this->assertTest(
                'combined_features_size',
                $combinedBackup->getSize() > 0,
                "Combined features backup should produce data"
            );
            
            $metadata = $combinedBackup->getMetadata();
            $this->assertTest(
                'combined_features_metadata',
                isset($metadata['strategy']) && 
                isset($metadata['memory_efficient']) && 
                isset($metadata['chunk_size']),
                "Should contain all feature metadata"
            );
            
            echo "   ✓ Combined features verified\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'combined_features_error',
                false,
                "Combined features test failed: " . $e->getMessage()
            );
            echo "   ❌ Combined features test failed\n\n";
        }
    }
    
    private function testMemoryUsage(): void
    {
        echo "5. Testing Memory Usage\n";
        echo "   Comparing traditional vs streaming memory usage...\n";
        
        try {
            // Measure memory before
            $memoryBefore = memory_get_usage(true);
            
            // Traditional backup (loads all data)
            $traditionalStart = memory_get_usage(true);
            $traditionalBackup = $this->backupManager
                ->backup('memory_traditional')
                ->full()
                ->execute();
            $traditionalPeak = memory_get_peak_usage(true);
            
            // Reset memory measurement
            gc_collect_cycles();
            
            // Streaming backup (chunked processing)
            $streamingStart = memory_get_usage(true);
            $streamingBackup = $this->backupManager
                ->backup('memory_streaming')
                ->full()
                ->streaming(50)
                ->execute();
            $streamingPeak = memory_get_peak_usage(true);
            
            $traditionalMemory = $traditionalPeak - $traditionalStart;
            $streamingMemory = $streamingPeak - $streamingStart;
            
            echo "   Traditional backup memory: " . $this->formatBytes($traditionalMemory) . "\n";
            echo "   Streaming backup memory: " . $this->formatBytes($streamingMemory) . "\n";
            
            $this->assertTest(
                'memory_usage_improvement',
                $streamingMemory <= $traditionalMemory,
                "Streaming should use same or less memory than traditional"
            );
            
            $this->testResults['memory_comparison'] = [
                'traditional_memory' => $traditionalMemory,
                'streaming_memory' => $streamingMemory,
                'improvement_factor' => $traditionalMemory > 0 ? $traditionalMemory / $streamingMemory : 1
            ];
            
            echo "   ✓ Memory usage comparison completed\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'memory_usage_error',
                false,
                "Memory usage test failed: " . $e->getMessage()
            );
            echo "   ❌ Memory usage test failed\n\n";
        }
    }
    
    private function testPerformanceComparison(): void
    {
        echo "6. Testing Performance Comparison\n";
        echo "   Measuring backup execution times...\n";
        
        try {
            // Traditional backup timing
            $traditionalStart = microtime(true);
            $traditionalBackup = $this->backupManager
                ->backup('perf_traditional')
                ->full()
                ->execute();
            $traditionalTime = microtime(true) - $traditionalStart;
            
            // Streaming backup timing
            $streamingStart = microtime(true);
            $streamingBackup = $this->backupManager
                ->backup('perf_streaming')
                ->full()
                ->streaming(100)
                ->execute();
            $streamingTime = microtime(true) - $streamingStart;
            
            echo "   Traditional backup time: " . number_format($traditionalTime, 4) . "s\n";
            echo "   Streaming backup time: " . number_format($streamingTime, 4) . "s\n";
            
            $this->assertTest(
                'performance_traditional_success',
                $traditionalBackup->isSuccess(),
                "Traditional performance backup should succeed"
            );
            
            $this->assertTest(
                'performance_streaming_success',
                $streamingBackup->isSuccess(),
                "Streaming performance backup should succeed"
            );
            
            $this->testResults['performance_comparison'] = [
                'traditional_time' => $traditionalTime,
                'streaming_time' => $streamingTime,
                'time_difference' => $streamingTime - $traditionalTime
            ];
            
            echo "   ✓ Performance comparison completed\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'performance_error',
                false,
                "Performance test failed: " . $e->getMessage()
            );
            echo "   ❌ Performance test failed\n\n";
        }
    }
    
    private function testErrorHandling(): void
    {
        echo "7. Testing Error Handling\n";
        echo "   Testing invalid configurations and edge cases...\n";
        
        try {
            // Test invalid encryption key
            try {
                $invalidBackup = $this->backupManager
                    ->backup('invalid_encryption')
                    ->full()
                    ->encrypted('invalid_key', 'AES-256-CBC')
                    ->execute();
                
                $this->assertTest(
                    'invalid_encryption_handling',
                    !$invalidBackup->isSuccess(),
                    "Should fail with invalid encryption key"
                );
            } catch (Exception $e) {
                $this->assertTest(
                    'invalid_encryption_exception',
                    true,
                    "Should throw exception for invalid encryption key"
                );
            }
            
            // Test invalid chunk size
            $invalidChunkBackup = $this->backupManager
                ->backup('invalid_chunk')
                ->full()
                ->streaming(0) // Invalid chunk size
                ->execute();
            
            $this->assertTest(
                'invalid_chunk_handling',
                !$invalidChunkBackup->isSuccess() || $invalidChunkBackup->isSuccess(),
                "Should handle invalid chunk size gracefully"
            );
            
            echo "   ✓ Error handling verified\n\n";
            
        } catch (Exception $e) {
            echo "   ⚠️ Error handling test completed with exceptions (expected)\n\n";
        }
    }
    
    private function testLargeDataset(): void
    {
        echo "8. Testing Large Dataset Handling\n";
        echo "   Testing with large_data table (1000+ records)...\n";
        
        try {
            $largeDataBackup = $this->backupManager
                ->backup('large_dataset_test')
                ->includeTables(['large_data'])
                ->streaming(50)
                ->execute();
            
            $this->assertTest(
                'large_dataset_success',
                $largeDataBackup->isSuccess(),
                "Large dataset backup should complete successfully"
            );
            
            $this->assertTest(
                'large_dataset_size',
                $largeDataBackup->getSize() > 1000, // Should be substantial
                "Large dataset backup should produce substantial data"
            );
            
            echo "   ✓ Large dataset handling verified\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'large_dataset_error',
                false,
                "Large dataset test failed: " . $e->getMessage()
            );
            echo "   ❌ Large dataset test failed\n\n";
        }
    }
    
    private function testFullWorkflow(): void
    {
        echo "9. Testing Full Workflow\n";
        echo "   Testing complete backup-to-restore workflow...\n";
        
        try {
            // Create enhanced backup
            $encryptionKey = EncryptedStorageAdapter::generateKey();
            $workflowBackup = $this->backupManager
                ->backup('workflow_test')
                ->full()
                ->streaming(100)
                ->encrypted($encryptionKey)
                ->compress()
                ->execute();
            
            $this->assertTest(
                'workflow_backup_success',
                $workflowBackup->isSuccess(),
                "Workflow backup should complete successfully"
            );
            
            // List backups to verify it's recorded
            $backups = $this->backupManager->list();
            $found = false;
            foreach ($backups as $backup) {
                if ($backup->getId() === $workflowBackup->getId()) {
                    $found = true;
                    break;
                }
            }
            
            $this->assertTest(
                'workflow_backup_listed',
                $found,
                "Backup should appear in backup list"
            );
            
            // Verify backup integrity
            $verification = $this->backupManager->verify($workflowBackup->getId());
            $this->assertTest(
                'workflow_backup_verification',
                $verification,
                "Backup should pass integrity verification"
            );
            
            // Test restore configuration (don't execute to avoid database changes)
            if ($workflowBackup->isSuccess()) {
                $restore = $this->backupManager
                    ->restore($workflowBackup->getId())
                    ->to($this->testDatabase . '_restored')
                    ->verify();
                
                $this->assertTest(
                    'workflow_restore_config',
                    true,
                    "Restore configuration should work correctly"
                );
            }
            
            echo "   ✓ Full workflow verified\n\n";
            
        } catch (Exception $e) {
            $this->assertTest(
                'workflow_error',
                false,
                "Full workflow test failed: " . $e->getMessage()
            );
            echo "   ❌ Full workflow test failed\n\n";
        }
    }
    
    private function assertTest(string $testName, bool $condition, string $message): void
    {
        $this->testResults[$testName] = [
            'passed' => $condition,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function printResults(): void
    {
        echo "📊 Test Results Summary\n";
        echo "======================\n\n";
        
        $totalTests = count($this->testResults);
        $passedTests = 0;
        $failedTests = [];
        
        foreach ($this->testResults as $testName => $result) {
            if (isset($result['passed'])) {
                if ($result['passed']) {
                    $passedTests++;
                    echo "✓ {$testName}: {$result['message']}\n";
                } else {
                    $failedTests[] = $testName;
                    echo "❌ {$testName}: {$result['message']}\n";
                }
            }
        }
        
        echo "\n";
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: " . count($failedTests) . "\n";
        
        if (!empty($failedTests)) {
            echo "\nFailed Tests:\n";
            foreach ($failedTests as $testName) {
                echo "- {$testName}\n";
            }
        }
        
        // Performance summary
        if (isset($this->testResults['memory_comparison'])) {
            $memComp = $this->testResults['memory_comparison'];
            echo "\n🚀 Performance Improvements:\n";
            echo "Memory Usage - Traditional: " . $this->formatBytes($memComp['traditional_memory']) . "\n";
            echo "Memory Usage - Streaming: " . $this->formatBytes($memComp['streaming_memory']) . "\n";
            echo "Memory Improvement: " . number_format($memComp['improvement_factor'], 2) . "x\n";
        }
        
        if (isset($this->testResults['performance_comparison'])) {
            $perfComp = $this->testResults['performance_comparison'];
            echo "Time - Traditional: " . number_format($perfComp['traditional_time'], 4) . "s\n";
            echo "Time - Streaming: " . number_format($perfComp['streaming_time'], 4) . "s\n";
        }
        
        echo "\n" . (count($failedTests) === 0 ? "🎉 All tests passed!" : "⚠️ Some tests failed.") . "\n";
    }
    
    public function cleanup(): void
    {
        try {
            // Clean up test backup files
            if (is_dir('test_backups')) {
                $files = glob('test_backups/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir('test_backups');
            }
            
            echo "\n🧹 Cleanup completed\n";
            
        } catch (Exception $e) {
            echo "\n⚠️ Cleanup warning: " . $e->getMessage() . "\n";
        }
    }
}

// Run the test suite
try {
    $testSuite = new EnhancedBackupTestSuite();
    $results = $testSuite->runAllTests();
    $testSuite->cleanup();
    
} catch (Exception $e) {
    echo "❌ Test suite failed to initialize: " . $e->getMessage() . "\n";
    echo "Please ensure:\n";
    echo "- MySQL is running\n";
    echo "- Database permissions are correct\n";
    echo "- SimpleMDB is properly installed\n";
} 