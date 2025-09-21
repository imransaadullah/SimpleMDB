<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder_PostgreSQL;
use SimpleMDB\SimpleQuery;

try {
    echo "🚀 SimpleMDB PostgreSQL Compatibility Demo\n";
    echo "==========================================\n\n";

    // Example 1: Create PostgreSQL connection
    echo "📡 Connecting to PostgreSQL...\n";
    $db = DatabaseFactory::create(
        'postgresql',           // Database type
        'localhost',           // Host
        'postgres',           // Username  
        'password',           // Password
        'testdb',             // Database name
        'UTF8',               // Charset (PostgreSQL default)
        'assoc',              // Default fetch type
        [                     // SSL options (optional)
            'sslmode' => 'prefer'
        ],
        5432                  // Port (PostgreSQL default)
    );
    
    echo "✅ Connected to PostgreSQL successfully!\n\n";

    // Example 2: Create a table using PostgreSQL-specific SchemaBuilder
    echo "🏗️  Creating PostgreSQL table with modern data types...\n";
    $schema = new SchemaBuilder_PostgreSQL($db);
    
    $sql = $schema
        ->increments('id')                                    // SERIAL PRIMARY KEY
        ->string('name', 100)->comment('User full name')     // VARCHAR(100)
        ->string('email', 150)->unique()                     // VARCHAR(150) UNIQUE
        ->boolean('is_active')->default(true)               // BOOLEAN DEFAULT true
        ->jsonb('preferences')->nullable()                   // JSONB (PostgreSQL-specific)
        ->inet('last_login_ip')->nullable()                  // INET (PostgreSQL IP type)
        ->uuidWithDefault('external_id')                     // UUID with gen_random_uuid()
        ->textArray('tags')->nullable()                      // TEXT[] (PostgreSQL array)
        ->timestamps()                                        // created_at, updated_at
        ->createTable('users');
    
    echo "✅ PostgreSQL table 'users' created with advanced data types!\n\n";

    // Example 3: Insert data using PostgreSQL syntax
    echo "📝 Inserting sample data...\n";
    $insertResult = $db->write_data('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_active' => true,
        'preferences' => json_encode(['theme' => 'dark', 'notifications' => true]),
        'last_login_ip' => '192.168.1.100',
        'tags' => '{programming,php,postgresql}', // PostgreSQL array syntax
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($insertResult) {
        echo "✅ Sample user inserted successfully! ID: " . $db->lastInsertId() . "\n\n";
    }

    // Example 4: Query data with PostgreSQL-specific features
    echo "🔍 Querying data with PostgreSQL features...\n";
    
    // Query using JSONB operators (PostgreSQL-specific)
    $users = $db->query("
        SELECT id, name, email, 
               preferences->>'theme' as theme,
               array_length(tags, 1) as tag_count,
               last_login_ip::text as ip_address
        FROM \"users\" 
        WHERE is_active = ? 
        AND preferences ? 'theme'
    ", [true])->fetchAll('assoc');
    
    foreach ($users as $user) {
        echo "👤 User: {$user['name']} ({$user['email']})\n";
        echo "   Theme: {$user['theme']}\n";
        echo "   Tags: {$user['tag_count']} tags\n";
        echo "   Last IP: {$user['ip_address']}\n\n";
    }

    // Example 5: Using SimpleQuery with PostgreSQL
    echo "🔧 Using SimpleQuery builder...\n";
    $queryBuilder = SimpleQuery::create()
        ->select(['id', 'name', 'email', 'is_active'])
        ->from('users')
        ->where('is_active = ?', [true])
        ->orderBy('created_at DESC')
        ->limit(10);
    
    $results = $queryBuilder->execute($db);
    echo "📊 Found " . count($results) . " active users\n\n";

    // Example 6: Demonstrate backward compatibility
    echo "🔄 Testing backward compatibility...\n";
    
    // All existing SimpleMDB methods work identically
    $userData = $db->read_data('users', ['name', 'email'], 'WHERE id = ?', [1]);
    if ($userData) {
        echo "✅ Backward compatibility confirmed: " . $userData['name'] . "\n";
    }

    // Example 7: Advanced PostgreSQL features
    echo "⚡ Advanced PostgreSQL features...\n";
    
    // Check if table exists
    if ($schema->hasTable('users')) {
        echo "✅ Table 'users' exists\n";
    }
    
    // Get table information
    $tableInfo = $schema->getTableInfo('users');
    echo "📋 Table has " . count($tableInfo) . " columns\n";
    
    // Transaction example
    echo "💾 Testing transactions...\n";
    $db->transaction(function($db) {
        $db->write_data('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_active' => true,
            'preferences' => json_encode(['theme' => 'light']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo "✅ Transaction completed successfully\n";
    });

    // Cleanup
    echo "\n🧹 Cleaning up...\n";
    $schema->dropTable('users');
    echo "✅ Test table dropped\n\n";

    echo "🎉 PostgreSQL compatibility demo completed successfully!\n";
    echo "SimpleMDB now supports both MySQL and PostgreSQL with 100% backward compatibility.\n\n";

    // Show connection stats
    $stats = $db->getConnectionStats();
    echo "📊 Connection Status: " . ($stats['status'] ?? 'connected') . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure PostgreSQL is running and accessible.\n";
    echo "💡 Install pdo_pgsql extension: apt-get install php-pgsql (Ubuntu) or brew install php-pgsql (macOS)\n";
    echo "💡 Create test database: createdb testdb\n";
}
?>

