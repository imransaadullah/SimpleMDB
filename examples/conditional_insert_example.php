<?php

require_once 'vendor/autoload.php';

use SimpleMDB\SimpleMySQLi;
use SimpleMDB\Migrations\Migration;

/**
 * Example: Conditional Insert Operations
 * 
 * This example demonstrates how to safely insert data by checking
 * unique constraints before inserting. This prevents duplicate data
 * and makes data insertion idempotent.
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'your_password';
$database = 'test_db';

try {
    // Create database connection
    $db = new SimpleMySQLi($host, $username, $password, $database);
    
    echo "=== Conditional Insert Example ===\n\n";
    
    // Example 1: Basic conditional insert
    echo "1. Basic conditional insert:\n";
    $schema = new \SimpleMDB\SchemaBuilder($db);
    
    // Create a table with unique email constraint
    $schema->createTableIfNotExists('users', function($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    
    // Try to insert the same user multiple times
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_active' => true
    ];
    
    // First insert - should succeed
    $result1 = $schema->insertIfNotExists('users', $userData);
    echo "   First insert: " . ($result1 ? "✅ Inserted" : "❌ Failed") . "\n";
    
    // Second insert - should be skipped (record exists)
    $result2 = $schema->insertIfNotExists('users', $userData);
    echo "   Second insert: " . ($result2 ? "✅ Inserted" : "⏭️ Skipped (already exists)") . "\n";
    
    // Check if record exists
    $exists = $schema->recordExists('users', $userData);
    echo "   Record exists: " . ($exists ? "✅ Yes" : "❌ No") . "\n\n";
    
    // Example 2: Multiple records with conditional insert
    echo "2. Multiple records conditional insert:\n";
    $users = [
        [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_active' => true
        ],
        [
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'is_active' => false
        ],
        [
            'name' => 'Alice Brown',
            'email' => 'alice@example.com',
            'is_active' => true
        ]
    ];
    
    // Insert multiple users
    $results = $schema->insertManyIfNotExists('users', $users);
    echo "   Total records: {$results['total']}\n";
    echo "   Inserted: {$results['inserted']}\n";
    echo "   Skipped: {$results['skipped']}\n";
    echo "   Errors: " . count($results['errors']) . "\n\n";
    
    // Example 3: Upsert (insert or update)
    echo "3. Upsert example:\n";
    $userData = [
        'name' => 'John Doe Updated',
        'email' => 'john@example.com', // Same email, should update
        'is_active' => false
    ];
    
    $upsertResult = $schema->upsert('users', $userData);
    echo "   Upsert result: " . ($upsertResult ? "✅ Success" : "❌ Failed") . "\n\n";
    
    // Example 4: Migration with conditional inserts
    echo "4. Migration with conditional inserts:\n";
    
    class ConditionalInsertMigration extends Migration
    {
        public function up(): void
        {
            // Create products table
            $this->createTableIfNotExists('products', function($table) {
                $table->id();
                $table->string('sku')->unique();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->integer('stock')->default(0);
                $table->timestamps();
            });
            
            // Insert products only if they don't exist
            $products = [
                [
                    'sku' => 'PROD-001',
                    'name' => 'Laptop',
                    'price' => 999.99,
                    'stock' => 10
                ],
                [
                    'sku' => 'PROD-002',
                    'name' => 'Mouse',
                    'price' => 29.99,
                    'stock' => 50
                ],
                [
                    'sku' => 'PROD-003',
                    'name' => 'Keyboard',
                    'price' => 79.99,
                    'stock' => 25
                ]
            ];
            
            $results = $this->insertManyIfNotExists('products', $products);
            echo "   Products migration - Inserted: {$results['inserted']}, Skipped: {$results['skipped']}\n";
            
            // Update product if exists, insert if not
            $productData = [
                'sku' => 'PROD-001',
                'name' => 'Laptop Pro',
                'price' => 1299.99,
                'stock' => 15
            ];
            
            $upsertResult = $this->upsert('products', $productData);
            echo "   Product upsert: " . ($upsertResult ? "✅ Success" : "❌ Failed") . "\n";
        }
        
        public function down(): void
        {
            $this->schema->dropTable('products');
        }
    }
    
    // Run the migration
    $migration = new ConditionalInsertMigration($db);
    $migration->up();
    
    echo "\n";
    
    // Example 5: Check unique fields
    echo "5. Check unique fields:\n";
    $uniqueFields = $schema->getUniqueFields('users');
    echo "   Users table unique fields: " . implode(', ', $uniqueFields) . "\n";
    
    $uniqueFields = $schema->getUniqueFields('products');
    echo "   Products table unique fields: " . implode(', ', $uniqueFields) . "\n\n";
    
    // Example 6: Manual unique field specification
    echo "6. Manual unique field specification:\n";
    $userData = [
        'name' => 'Charlie Wilson',
        'email' => 'charlie@example.com',
        'is_active' => true
    ];
    
    // Specify unique fields manually
    $result = $schema->insertIfNotExists('users', $userData, ['email']);
    echo "   Manual unique field check: " . ($result ? "✅ Inserted" : "⏭️ Skipped") . "\n";
    
    // Try to insert with same email but different name
    $userData2 = [
        'name' => 'Charlie Wilson Jr.',
        'email' => 'charlie@example.com', // Same email
        'is_active' => false
    ];
    
    $result2 = $schema->insertIfNotExists('users', $userData2, ['email']);
    echo "   Duplicate email check: " . ($result2 ? "✅ Inserted" : "⏭️ Skipped") . "\n\n";
    
    echo "✅ Conditional insert example completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 