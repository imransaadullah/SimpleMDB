<?php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SimpleQuery;

// Test configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'test_db';

try {
    // Test with SimpleMySQLi
    echo "=== Testing SimpleMySQLi closeCursor() ===\n";
    $db = DatabaseFactory::create('mysqli', $host, $username, $password, $database);
    
    // Create a test table
    $db->query("CREATE TABLE IF NOT EXISTS test_cursor (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50))");
    
    // Insert some test data
    $db->query("INSERT INTO test_cursor (name) VALUES ('Test 1'), ('Test 2'), ('Test 3')");
    
    // Execute a query
    $result = $db->query("SELECT * FROM test_cursor");
    
    // Fetch some data
    $row1 = $result->fetch('assoc');
    echo "First row: " . json_encode($row1) . "\n";
    
    // Close cursor
    $result->closeCursor();
    echo "Cursor closed successfully\n";
    
    // Try to fetch again (should work with a new query)
    $result2 = $db->query("SELECT COUNT(*) as count FROM test_cursor");
    $count = $result2->fetch('assoc');
    echo "Count: " . json_encode($count) . "\n";
    
    // Test with SimplePDO
    echo "\n=== Testing SimplePDO closeCursor() ===\n";
    $dbPdo = DatabaseFactory::create('pdo', $host, $username, $password, $database);
    
    // Execute a query
    $resultPdo = $dbPdo->query("SELECT * FROM test_cursor");
    
    // Fetch some data
    $row1Pdo = $resultPdo->fetch('assoc');
    echo "First row (PDO): " . json_encode($row1Pdo) . "\n";
    
    // Close cursor
    $resultPdo->closeCursor();
    echo "Cursor closed successfully (PDO)\n";
    
    // Try to fetch again (should work with a new query)
    $result2Pdo = $dbPdo->query("SELECT COUNT(*) as count FROM test_cursor");
    $countPdo = $result2Pdo->fetch('assoc');
    echo "Count (PDO): " . json_encode($countPdo) . "\n";
    
    // Clean up
    $db->query("DROP TABLE IF EXISTS test_cursor");
    
    echo "\n✅ closeCursor() method works correctly in both implementations!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 