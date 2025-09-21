<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder_PostgreSQL;

/**
 * PostgreSQL Compatibility Test Suite
 * Tests all PostgreSQL-specific functionality and backward compatibility
 */
class PostgreSQLCompatibilityTest
{
    private $db;
    private $schema;
    private $testResults = [];

    public function __construct()
    {
        echo "🧪 SimpleMDB PostgreSQL Compatibility Test Suite\n";
        echo "===============================================\n\n";
    }

    public function runAllTests(): bool
    {
        try {
            $this->setupConnection();
            $this->testDatabaseFactory();
            $this->testSchemaBuilder();
            $this->testDataTypes();
            $this->testCRUDOperations();
            $this->testTransactions();
            $this->testBackwardCompatibility();
            $this->testAdvancedFeatures();
            $this->cleanup();
            
            return $this->printResults();
        } catch (Exception $e) {
            echo "💥 Fatal Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function setupConnection(): void
    {
        echo "📡 Setting up PostgreSQL connection...\n";
        
        try {
            $this->db = DatabaseFactory::create(
                'postgresql',
                'localhost',
                'postgres',
                'password',
                'testdb',
                'UTF8',
                'assoc',
                ['sslmode' => 'prefer'],
                5432
            );
            
            $this->schema = new SchemaBuilder_PostgreSQL($this->db);
            $this->addTestResult('Connection Setup', true, 'PostgreSQL connection established');
            
        } catch (Exception $e) {
            $this->addTestResult('Connection Setup', false, $e->getMessage());
            throw $e;
        }
    }

    private function testDatabaseFactory(): void
    {
        echo "🏭 Testing DatabaseFactory...\n";
        
        // Test PostgreSQL type
        try {
            $db1 = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'testdb');
            $this->addTestResult('Factory PostgreSQL', true, 'PostgreSQL factory works');
        } catch (Exception $e) {
            $this->addTestResult('Factory PostgreSQL', false, $e->getMessage());
        }
        
        // Test pgsql alias
        try {
            $db2 = DatabaseFactory::create('pgsql', 'localhost', 'postgres', 'password', 'testdb');
            $this->addTestResult('Factory PGSQL Alias', true, 'PGSQL alias works');
        } catch (Exception $e) {
            $this->addTestResult('Factory PGSQL Alias', false, $e->getMessage());
        }
        
        // Test invalid type
        try {
            DatabaseFactory::create('invalid', 'localhost', 'user', 'pass', 'db');
            $this->addTestResult('Factory Invalid Type', false, 'Should have thrown exception');
        } catch (InvalidArgumentException $e) {
            $this->addTestResult('Factory Invalid Type', true, 'Correctly rejected invalid type');
        }
    }

    private function testSchemaBuilder(): void
    {
        echo "🏗️  Testing PostgreSQL SchemaBuilder...\n";
        
        // Test table creation
        try {
            $sql = $this->schema
                ->increments('id')
                ->string('name', 100)
                ->string('email', 150)->unique()
                ->boolean('is_active')->default(true)
                ->timestamps()
                ->createTable('test_users');
            
            $this->addTestResult('Table Creation', true, 'PostgreSQL table created successfully');
        } catch (Exception $e) {
            $this->addTestResult('Table Creation', false, $e->getMessage());
        }
        
        // Test table exists
        try {
            $exists = $this->schema->hasTable('test_users');
            $this->addTestResult('Table Exists Check', $exists, $exists ? 'Table exists check works' : 'Table not found');
        } catch (Exception $e) {
            $this->addTestResult('Table Exists Check', false, $e->getMessage());
        }
        
        // Test column exists
        try {
            $hasColumn = $this->schema->hasColumn('test_users', 'name');
            $this->addTestResult('Column Exists Check', $hasColumn, $hasColumn ? 'Column exists check works' : 'Column not found');
        } catch (Exception $e) {
            $this->addTestResult('Column Exists Check', false, $e->getMessage());
        }
    }

    private function testDataTypes(): void
    {
        echo "🎯 Testing PostgreSQL-specific data types...\n";
        
        try {
            // Create table with PostgreSQL-specific types
            $this->schema
                ->increments('id')
                ->jsonb('data')                    // PostgreSQL JSONB
                ->inet('ip_address')               // PostgreSQL INET
                ->macaddr('mac_address')           // PostgreSQL MACADDR  
                ->textArray('tags')                // PostgreSQL TEXT[]
                ->integerArray('numbers')          // PostgreSQL INTEGER[]
                ->uuidWithDefault('external_id')   // UUID with default
                ->createTable('test_datatypes');
            
            $this->addTestResult('PostgreSQL Data Types', true, 'All PostgreSQL-specific data types created');
        } catch (Exception $e) {
            $this->addTestResult('PostgreSQL Data Types', false, $e->getMessage());
        }
    }

    private function testCRUDOperations(): void
    {
        echo "📝 Testing CRUD operations...\n";
        
        // Test INSERT
        try {
            $result = $this->db->write_data('test_users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->addTestResult('INSERT Operation', (bool)$result, 'Insert operation successful');
        } catch (Exception $e) {
            $this->addTestResult('INSERT Operation', false, $e->getMessage());
        }
        
        // Test SELECT
        try {
            $users = $this->db->read_data_all('test_users');
            $this->addTestResult('SELECT Operation', !empty($users), 'Select operation returned data');
        } catch (Exception $e) {
            $this->addTestResult('SELECT Operation', false, $e->getMessage());
        }
        
        // Test UPDATE
        try {
            $result = $this->db->update('test_users', 
                ['name' => 'Updated User'], 
                'WHERE email = ?', 
                ['test@example.com']
            );
            
            $this->addTestResult('UPDATE Operation', (bool)$result, 'Update operation successful');
        } catch (Exception $e) {
            $this->addTestResult('UPDATE Operation', false, $e->getMessage());
        }
        
        // Test DELETE
        try {
            $result = $this->db->delete('test_users', 'email = ?', ['test@example.com']);
            $this->addTestResult('DELETE Operation', (bool)$result, 'Delete operation successful');
        } catch (Exception $e) {
            $this->addTestResult('DELETE Operation', false, $e->getMessage());
        }
    }

    private function testTransactions(): void
    {
        echo "💾 Testing transactions...\n";
        
        try {
            $this->db->transaction(function($db) {
                $db->write_data('test_users', [
                    'name' => 'Transaction User 1',
                    'email' => 'trans1@example.com',
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $db->write_data('test_users', [
                    'name' => 'Transaction User 2',
                    'email' => 'trans2@example.com',
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            });
            
            // Verify both records were inserted
            $count = $this->db->query("SELECT COUNT(*) FROM \"test_users\"")->fetch('col');
            $this->addTestResult('Transaction Success', $count >= 2, "Transaction inserted $count records");
            
        } catch (Exception $e) {
            $this->addTestResult('Transaction Success', false, $e->getMessage());
        }
        
        // Test transaction rollback
        try {
            $initialCount = $this->db->query("SELECT COUNT(*) FROM \"test_users\"")->fetch('col');
            
            try {
                $this->db->transaction(function($db) {
                    $db->write_data('test_users', [
                        'name' => 'Rollback User',
                        'email' => 'rollback@example.com',
                        'is_active' => true,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Force an error to trigger rollback
                    throw new Exception('Intentional rollback');
                });
            } catch (Exception $e) {
                // Expected exception
            }
            
            $finalCount = $this->db->query("SELECT COUNT(*) FROM \"test_users\"")->fetch('col');
            $this->addTestResult('Transaction Rollback', $initialCount == $finalCount, 'Transaction rollback works correctly');
            
        } catch (Exception $e) {
            $this->addTestResult('Transaction Rollback', false, $e->getMessage());
        }
    }

    private function testBackwardCompatibility(): void
    {
        echo "🔄 Testing backward compatibility...\n";
        
        // All these methods should work identically to MySQL version
        $methods = [
            'query', 'fetch', 'fetchAll', 'numRows', 'affectedRows',
            'insertId', 'beginTransaction', 'commit', 'rollback',
            'prepare', 'execute', 'closeCursor', 'quote'
        ];
        
        foreach ($methods as $method) {
            try {
                $reflection = new ReflectionClass($this->db);
                $hasMethod = $reflection->hasMethod($method);
                $this->addTestResult("Method: $method", $hasMethod, $hasMethod ? 'Method exists' : 'Method missing');
            } catch (Exception $e) {
                $this->addTestResult("Method: $method", false, $e->getMessage());
            }
        }
    }

    private function testAdvancedFeatures(): void
    {
        echo "⚡ Testing advanced PostgreSQL features...\n";
        
        // Test JSONB operations
        try {
            $this->db->write_data('test_datatypes', [
                'data' => json_encode(['key' => 'value', 'number' => 42]),
                'ip_address' => '192.168.1.1',
                'tags' => '{tag1,tag2,tag3}',
                'numbers' => '{1,2,3,4,5}'
            ]);
            
            // Query using JSONB operators
            $result = $this->db->query("
                SELECT data->>'key' as key_value, 
                       array_length(tags, 1) as tag_count
                FROM \"test_datatypes\" 
                WHERE data ? 'key'
            ")->fetch('assoc');
            
            $success = $result && $result['key_value'] === 'value';
            $this->addTestResult('JSONB Operations', $success, $success ? 'JSONB queries work' : 'JSONB query failed');
            
        } catch (Exception $e) {
            $this->addTestResult('JSONB Operations', false, $e->getMessage());
        }
        
        // Test array operations
        try {
            $result = $this->db->query("
                SELECT tags[1] as first_tag,
                       numbers[1] as first_number
                FROM \"test_datatypes\"
                LIMIT 1
            ")->fetch('assoc');
            
            $success = !empty($result);
            $this->addTestResult('Array Operations', $success, $success ? 'Array operations work' : 'Array query failed');
            
        } catch (Exception $e) {
            $this->addTestResult('Array Operations', false, $e->getMessage());
        }
    }

    private function cleanup(): void
    {
        echo "🧹 Cleaning up test data...\n";
        
        try {
            $this->schema->dropTable('test_users');
            $this->schema->dropTable('test_datatypes');
            $this->addTestResult('Cleanup', true, 'Test tables dropped successfully');
        } catch (Exception $e) {
            $this->addTestResult('Cleanup', false, $e->getMessage());
        }
    }

    private function addTestResult(string $test, bool $success, string $message): void
    {
        $this->testResults[] = [
            'test' => $test,
            'success' => $success,
            'message' => $message
        ];
        
        $icon = $success ? '✅' : '❌';
        echo "  $icon $test: $message\n";
    }

    private function printResults(): bool
    {
        echo "\n📊 Test Results Summary\n";
        echo "=====================\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $result) {
            if ($result['success']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $total = $passed + $failed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        
        echo "Total Tests: $total\n";
        echo "Passed: $passed ✅\n";
        echo "Failed: $failed ❌\n";
        echo "Success Rate: $percentage%\n\n";
        
        if ($failed > 0) {
            echo "❌ Failed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    echo "  • {$result['test']}: {$result['message']}\n";
                }
            }
            echo "\n";
        }
        
        $success = $failed === 0;
        echo $success ? "🎉 All tests passed! PostgreSQL compatibility is working perfectly.\n" 
                     : "⚠️  Some tests failed. Please check the PostgreSQL setup and configuration.\n";
        
        return $success;
    }
}

// Run the test suite
$test = new PostgreSQLCompatibilityTest();
$success = $test->runAllTests();

exit($success ? 0 : 1);
?>

