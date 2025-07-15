<?php

/**
 * SimpleMDB Quick Start Example
 * 
 * This file implements the complete 5-minute quick start guide.
 * Perfect for first-time users to get SimpleMDB working immediately.
 * 
 * BEFORE RUNNING:
 * 1. Update the database connection details below
 * 2. Make sure you have a database created
 * 3. Run: composer require simplemdb/simplemdb
 * 4. Run: php examples/quick_start_example.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder;
use SimpleMDB\SimpleQuery;

echo "=== SimpleMDB Quick Start Example ===\n\n";

// STEP 1: Configure your database connection
// 🔧 UPDATE THESE VALUES FOR YOUR DATABASE
$host = 'localhost';
$username = 'root';
$password = '';  // Update with your password
$database = 'test';  // Update with your database name

echo "Step 1: Connecting to database...\n";

// STEP 2: Connect to database
try {
    $db = DatabaseFactory::create('pdo', $host, $username, $password, $database);
    
    if ($db->isConnected()) {
        echo "✅ Connected to database successfully!\n\n";
    } else {
        echo "❌ Connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "\n";
    echo "💡 Please update the database connection details at the top of this file.\n";
    exit(1);
}

// STEP 3: Create a modern table
echo "Step 2: Creating modern users table...\n";

try {
    $schema = new SchemaBuilder($db);
    
    // Drop table if it exists (for clean testing)
    $schema->dropTable('quick_start_users');
    
    // Create a modern users table with enterprise features
    $schema->increments('id')                           // Auto-increment primary key
           ->string('name', 100)->comment('Full name')  // VARCHAR with comment
           ->string('email', 150)->unique()             // Unique email
           ->boolean('is_active')->default(true)        // Boolean with default
           ->json('preferences')->nullable()            // JSON data storage
           ->ipAddress('last_login_ip')->nullable()     // IPv4/IPv6 address
           ->timestamps()                               // created_at, updated_at
           ->createTable('quick_start_users');
    
    echo "✅ Users table created with modern features!\n";
    echo "   - Auto-increment ID\n";
    echo "   - Unique email constraint\n";
    echo "   - JSON preferences storage\n";
    echo "   - IP address field\n";
    echo "   - Automatic timestamps\n\n";
    
} catch (Exception $e) {
    echo "❌ Table creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 4: Insert sample data
echo "Step 3: Inserting sample users...\n";

try {
    // Insert multiple users
    $users = [
        [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'preferences' => json_encode(['theme' => 'dark', 'notifications' => true]),
            'last_login_ip' => '192.168.1.100'
        ],
        [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_active' => true,
            'preferences' => json_encode(['theme' => 'light', 'notifications' => false]),
            'last_login_ip' => '10.0.0.50'
        ],
        [
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'is_active' => false,
            'preferences' => json_encode(['theme' => 'auto', 'notifications' => true]),
            'last_login_ip' => '203.0.113.25'
        ]
    ];
    
    foreach ($users as $user) {
        $userId = SimpleQuery::create()
            ->insert($user)
            ->into('quick_start_users')
            ->execute($db);
        
        echo "✅ User '{$user['name']}' created with ID: $userId\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Data insertion failed: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 5: Query and display data
echo "Step 4: Querying data...\n";

try {
    // Query active users
    $activeUsers = SimpleQuery::create()
        ->select(['id', 'name', 'email', 'is_active', 'last_login_ip', 'created_at'])
        ->from('quick_start_users')
        ->where('is_active = ?', [true])
        ->orderBy('created_at DESC')
        ->execute($db);
    
    echo "✅ Found " . count($activeUsers) . " active users:\n";
    foreach ($activeUsers as $user) {
        echo "  - {$user['name']} ({$user['email']}) - Last IP: {$user['last_login_ip']}\n";
    }
    
    echo "\n";
    
    // Query all users with JSON data
    $allUsers = SimpleQuery::create()
        ->select(['name', 'email', 'preferences', 'is_active'])
        ->from('quick_start_users')
        ->execute($db);
    
    echo "All users with preferences:\n";
    foreach ($allUsers as $user) {
        $preferences = json_decode($user['preferences'], true);
        $status = $user['is_active'] ? 'Active' : 'Inactive';
        echo "  - {$user['name']}: Theme: {$preferences['theme']}, Notifications: " . 
             ($preferences['notifications'] ? 'On' : 'Off') . " [$status]\n";
    }
    
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== 🎉 Quick Start Complete! ===\n\n";

echo "Congratulations! You've successfully:\n";
echo "✅ Connected to your database\n";
echo "✅ Created a table with modern data types (JSON, IP addresses, booleans)\n";
echo "✅ Inserted and queried data safely\n";
echo "✅ Used enterprise features (timestamps, unique constraints)\n\n";

echo "🚀 What's Next?\n";
echo "================\n\n";

echo "1. Explore Advanced Data Types (10 minutes):\n";
echo "   php examples/enhanced_backup_example.php\n\n";

echo "2. Try Intelligent Migrations (15 minutes):\n";
echo "   Read: TESTING.md for migration examples\n\n";

echo "3. Enterprise Backup System (20 minutes):\n";
echo "   php examples/complete_backup_example.php\n\n";

echo "4. Full Documentation:\n";
echo "   See: README.md for complete feature reference\n\n";

echo "💡 Pro Tips:\n";
echo "- All SimpleMDB features use the same simple, fluent API\n";
echo "- Every feature includes comprehensive error handling\n";
echo "- 100% SQL injection protection built-in\n";
echo "- Laravel-like syntax for familiar developer experience\n\n";

echo "🌟 You're now ready to build enterprise-grade applications with SimpleMDB!\n";

// Optional: Clean up (comment out if you want to keep the table for exploration)
echo "\n🧹 Cleaning up test table...\n";
try {
    $schema->dropTable('quick_start_users');
    echo "✅ Test table removed\n";
} catch (Exception $e) {
    echo "⚠️ Cleanup warning: " . $e->getMessage() . "\n";
}

echo "\nQuick start example completed successfully! 🎊\n"; 