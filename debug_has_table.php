<?php

require_once 'vendor/autoload.php';

use SimpleMDB\SimpleMySQLi;

// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'zxcvbnm,./';
$database = 'span_healthcare';

try {
    // Create database connection
    $db = new SimpleMySQLi($host, $username, $password, $database);
    
    // Create schema builder
    $schema = new \SimpleMDB\SchemaBuilder($db);
    
    // Test table name
    $tableName = 'migrations';
    
    echo "=== Testing hasTable for '{$tableName}' ===\n";
    
    // Test the debug method
    $debug = $schema->debugHasTable($tableName);
    
    echo "Debug Results:\n";
    echo json_encode($debug, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test the regular hasTable method
    $hasTable = $schema->hasTable($tableName);
    echo "hasTable('{$tableName}') returns: " . ($hasTable ? 'true' : 'false') . "\n";
    
    // Test with a non-existent table
    $nonExistentTable = 'non_existent_table_' . time();
    $hasNonExistentTable = $schema->hasTable($nonExistentTable);
    echo "hasTable('{$nonExistentTable}') returns: " . ($hasNonExistentTable ? 'true' : 'false') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 