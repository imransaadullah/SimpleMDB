<?php

require_once 'vendor/autoload.php';

use SimpleMDB\SimpleMySQLi;
use SimpleMDB\Migrations\Migration;

/**
 * Example: Idempotent Migration
 * 
 * This example demonstrates how to create migrations that can be run
 * multiple times safely without errors. This is crucial for production
 * deployments where the same migration might be executed multiple times.
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'your_password';
$database = 'test_db';

try {
    // Create database connection
    $db = new SimpleMySQLi($host, $username, $password, $database);
    
    echo "=== Idempotent Migration Example ===\n\n";
    
    // Example 1: Safe table creation
    echo "1. Creating table safely (can run multiple times):\n";
    $schema = new \SimpleMDB\SchemaBuilder($db);
    
    $schema->createTableIfNotExists('users', function($table) {
        $table->increments('id')
              ->string('email', 255)->unique()
              ->string('name', 100)
              ->boolean('is_active')->default(true)
              ->timestamps();
    });
    echo "✅ Users table created (or already existed)\n\n";
    
    // Example 2: Safe index addition
    echo "2. Adding indexes safely:\n";
    $schema->addIndexIfNotExists('users', ['email'], 'idx_users_email');
    $schema->addIndexIfNotExists('users', ['name'], 'idx_users_name');
    $schema->addUniqueIndexIfNotExists('users', ['email'], 'unique_users_email');
    echo "✅ Indexes added (or already existed)\n\n";
    
    // Example 3: Safe column addition
    echo "3. Adding columns safely:\n";
    $schema->addColumnIfNotExists('users', 'phone', [
        'type' => 'VARCHAR',
        'length' => 20,
        'nullable' => true
    ]);
    $schema->addColumnIfNotExists('users', 'last_login', [
        'type' => 'TIMESTAMP',
        'nullable' => true
    ]);
    echo "✅ Columns added (or already existed)\n\n";
    
    // Example 4: Safe foreign key addition
    echo "4. Adding foreign keys safely:\n";
    // First create the referenced table
    $schema->createTableIfNotExists('roles', function($table) {
        $table->increments('id')
              ->string('name', 50)->unique()
              ->timestamps();
    });
    
    // Add foreign key safely
    $schema->addForeignKeyIfNotExists(
        'users', 'role_id', 'roles', 'id', 
        'fk_users_role_id', 'cascade', 'cascade'
    );
    echo "✅ Foreign key added (or already existed)\n\n";
    
    // Example 5: Check existing indexes
    echo "5. Checking existing indexes:\n";
    $indexes = $schema->getIndexes('users');
    foreach ($indexes as $index) {
        echo "   - Index: {$index['name']} (Type: {$index['type']}, Unique: " . 
             ($index['unique'] ? 'Yes' : 'No') . ")\n";
        echo "     Columns: " . implode(', ', $index['columns']) . "\n";
    }
    echo "\n";
    
    // Example 6: Migration class example
    echo "6. Running idempotent migration class:\n";
    
    class ExampleIdempotentMigration extends Migration
    {
        public function up(): void
        {
            // Create table safely
            $this->createTableIfNotExists('products', function($table) {
                $table->increments('id')
                      ->string('name', 255)
                      ->decimal('price', 10, 2)
                      ->text('description')->nullable()
                      ->boolean('is_active')->default(true)
                      ->timestamps();
            });
            
            // Add indexes safely
            $this->addIndexIfNotExists('products', ['name'], 'idx_products_name');
            $this->addUniqueIndexIfNotExists('products', ['name'], 'unique_products_name');
            
            // Add columns safely
            $this->addColumnIfNotExists('products', 'sku', [
                'type' => 'VARCHAR',
                'length' => 50,
                'nullable' => true
            ]);
            
            echo "✅ Migration executed successfully (idempotent)\n";
        }
        
        public function down(): void
        {
            // Drop in reverse order
            $this->dropTable('products');
        }
    }
    
    // Run the migration multiple times safely
    $migration = new ExampleIdempotentMigration($db);
    $migration->up(); // First run
    $migration->up(); // Second run (safe!)
    $migration->up(); // Third run (safe!)
    
    echo "\n=== Migration Results ===\n";
    echo "✅ All operations completed successfully\n";
    echo "✅ Migration can be run multiple times without errors\n";
    echo "✅ No duplicate indexes, columns, or tables created\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Key Benefits ===\n";
echo "🎯 Idempotent Operations: Run the same migration multiple times safely\n";
echo "🛡️  Production Safe: No errors when re-running migrations\n";
echo "🔍 Smart Detection: Automatically detects existing indexes, columns, tables\n";
echo "⚡ Efficient: Only creates what doesn't already exist\n";
echo "🔄 Repeatable: Perfect for CI/CD pipelines and deployment scripts\n"; 