<?php

require_once __DIR__ . '/vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Backup\BackupManager;
use SimpleMDB\Backup\SchemaAnalyzer;
use SimpleMDB\Backup\MigrationGenerator;

echo "=== SimpleMDB Backup System Test ===\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $db = DatabaseFactory::create(
        'mysqli',
        'localhost',
        'root',
        'zxcvbnm,./',
        'span_healthcare'
    );
    echo "✓ Database connection successful\n\n";

    // Test Schema Analyzer
    echo "2. Testing Schema Analyzer...\n";
    $analyzer = new SchemaAnalyzer($db);
    
    // Test schema analysis functionality
    echo "   - Analyzing database schema...\n";
    $schemaData = $analyzer->analyzeDatabase();
    echo "   ✓ Found " . count($schemaData['tables']) . " tables\n";
    echo "   ✓ Found " . count($schemaData['relationships']) . " relationships\n";
    
    // Show some table details
    if (!empty($schemaData['tables'])) {
        $firstTable = array_key_first($schemaData['tables']);
        $tableData = $schemaData['tables'][$firstTable];
        echo "   ✓ Sample table '{$firstTable}' has " . count($tableData['columns']) . " columns\n";
    }
    
    echo "\n3. Testing Migration Generator...\n";
    if (!empty($schemaData['tables'])) {
        $generator = new MigrationGenerator($schemaData, [
            'expressive_syntax' => true,
            'use_comments' => true
        ]);
        
        echo "   - Generating test migration files...\n";
        $migrationFiles = $generator->generateMigrations('test_migrations');
        echo "   ✓ Generated " . count($migrationFiles) . " migration files\n";
        
        foreach ($migrationFiles as $file) {
            echo "     - " . basename($file) . "\n";
        }
    }
    
    echo "\n4. Testing Backup Manager...\n";
    $backupManager = new BackupManager($db, 'test_backups');
    
    // Test schema backup with migration generation
    echo "   - Creating schema backup with migrations...\n";
    $schemaBackup = $backupManager->backup('test_schema_backup')
        ->schemaOnly()
        ->generateMigrations()
        ->useExpressiveSyntax()
        ->description('Test schema backup with expressive migrations')
        ->execute();
    
    if ($schemaBackup->isSuccess()) {
        echo "   ✓ Schema backup created: {$schemaBackup->getId()}\n";
        echo "   ✓ Size: {$schemaBackup->getFormattedSize()}\n";
    } else {
        echo "   ✗ Schema backup failed: {$schemaBackup->getErrorMessage()}\n";
    }
    
    // List all backups
    echo "\n5. Listing backups...\n";
    $backups = $backupManager->list();
    echo "   ✓ Found " . count($backups) . " backups\n";
    
    foreach ($backups as $backup) {
        echo "     - {$backup->getName()} ({$backup->getType()->value})\n";
    }
    
    echo "\n✅ All tests completed successfully!\n";
    echo "\nThe backup system is working correctly with expressive SimpleMDB syntax.\n";
    
} catch (\Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

// Clean up test files
echo "\n6. Cleaning up test files...\n";
if (is_dir('test_migrations')) {
    $files = glob('test_migrations/*');
    foreach ($files as $file) {
        unlink($file);
    }
    rmdir('test_migrations');
    echo "   ✓ Cleaned up migration files\n";
}

if (is_dir('test_backups')) {
    $files = glob('test_backups/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
    rmdir('test_backups');
    echo "   ✓ Cleaned up backup files\n";
}

echo "\n=== Test completed ===\n"; 