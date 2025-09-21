<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilderFactory;
use SimpleMDB\QueryBuilderFactory;

echo "🏗️  SimpleMDB Interface-Based Architecture Demo\n";
echo "==============================================\n\n";

try {
    // Example 1: Using the new interface-based approach
    echo "📐 1. Interface-Based Schema Building\n";
    echo "-----------------------------------\n";

    // Create database connections
    $mysqlDb = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'testdb');
    $pgDb = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'testdb');

    // Create database-specific schema builders
    $mysqlSchema = SchemaBuilderFactory::createMySQL($mysqlDb);
    $pgSchema = SchemaBuilderFactory::createPostgreSQL($pgDb);

    echo "✅ Created MySQL and PostgreSQL schema builders\n\n";

    // Example 2: Same API, different implementations
    echo "🔄 2. Same API, Different Database Engines\n";
    echo "------------------------------------------\n";

    // MySQL table creation
    $mysqlSchema->increments('id')
                ->string('name', 100)
                ->string('email', 150)->unique()
                ->json('preferences')->nullable()
                ->timestamps()
                ->createTable('users_mysql');
    
    echo "✅ MySQL table created with backtick quoting and MySQL-specific features\n";

    // PostgreSQL table creation (same API!)
    $pgSchema->increments('id')
             ->string('name', 100)
             ->string('email', 150)->unique()
             ->jsonb('preferences')->nullable()  // PostgreSQL-specific JSONB
             ->inet('last_ip')->nullable()       // PostgreSQL-specific INET
             ->timestamps()
             ->createTable('users_postgresql');
    
    echo "✅ PostgreSQL table created with double-quote quoting and PostgreSQL-specific features\n\n";

    // Example 3: Database-specific features
    echo "🎯 3. Database-Specific Features\n";
    echo "-------------------------------\n";

    // PostgreSQL-specific features
    $pgSchema->reset()
             ->increments('id')
             ->textArray('tags')                    // PostgreSQL arrays
             ->uuidWithDefault('external_id')       // UUID with gen_random_uuid()
             ->macaddr('device_mac')                // PostgreSQL MAC address type
             ->createTable('advanced_postgresql');
    
    echo "✅ PostgreSQL advanced features: arrays, UUID generation, MAC addresses\n";

    // MySQL-specific features  
    $mysqlSchema->reset()
                ->increments('id')
                ->enum('status', ['active', 'inactive'])    // MySQL ENUM
                ->set('permissions', ['read', 'write'])     // MySQL SET
                ->engine('InnoDB')                          // MySQL engine
                ->charset('utf8mb4')                        // MySQL charset
                ->createTable('advanced_mysql');
    
    echo "✅ MySQL advanced features: ENUM, SET, engine, charset\n\n";

    // Example 4: Interface-based query building
    echo "🔍 4. Interface-Based Query Building\n";
    echo "-----------------------------------\n";

    // Create database-specific query builders
    $mysqlQuery = QueryBuilderFactory::createMySQL();
    $pgQuery = QueryBuilderFactory::createPostgreSQL();

    // MySQL query with backtick quoting
    $mysqlSql = $mysqlQuery->select(['id', 'name', 'email'])
                           ->from('users_mysql')
                           ->where('`status` = ?', ['active'])
                           ->orderBy('created_at', 'DESC')
                           ->limit(10)
                           ->toSql();
    
    echo "MySQL Query: $mysqlSql\n";

    // PostgreSQL query with double-quote quoting
    $pgSql = $pgQuery->select(['id', 'name', 'email'])
                     ->from('users_postgresql')
                     ->where('"status" = ?', ['active'])
                     ->orderBy('created_at', 'DESC')
                     ->limit(10)
                     ->toSql();
    
    echo "PostgreSQL Query: $pgSql\n";
    echo "✅ Same API generates database-specific SQL syntax\n\n";

    // Example 5: Advanced PostgreSQL query features
    echo "⚡ 5. Advanced PostgreSQL Query Features\n";
    echo "--------------------------------------\n";

    $advancedPgQuery = QueryBuilderFactory::createPostgreSQL();
    
    // Use PostgreSQL-specific methods
    $advancedSql = $advancedPgQuery->select(['name', 'preferences'])
                                   ->from('users_postgresql')
                                   ->whereJsonb('preferences', '?', 'theme')  // JSONB operator
                                   ->whereArrayContains('tags', ['php'])      // Array contains
                                   ->whereFullText('description', 'search term') // Full-text search
                                   ->toSql();
    
    echo "Advanced PostgreSQL Query: $advancedSql\n";
    echo "✅ PostgreSQL-specific operators: JSONB, arrays, full-text search\n\n";

    // Example 6: Backward compatibility
    echo "🔄 6. Backward Compatibility\n";
    echo "---------------------------\n";

    // Original SchemaBuilder still works (auto-detects database type)
    $originalSchema = new \SimpleMDB\SchemaBuilder($mysqlDb);
    echo "✅ Original SchemaBuilder constructor still works\n";

    // Factory method for auto-detection
    $autoSchema = SchemaBuilderFactory::create($pgDb); // Auto-detects PostgreSQL
    echo "✅ Auto-detection works: " . get_class($autoSchema) . "\n";

    // Original SimpleQuery still works
    $originalQuery = \SimpleMDB\SimpleQuery::create();
    echo "✅ Original SimpleQuery still works\n\n";

    // Example 7: Factory pattern benefits
    echo "🏭 7. Factory Pattern Benefits\n";
    echo "-----------------------------\n";

    echo "Available Schema Builders:\n";
    foreach (SchemaBuilderFactory::getAvailableTypes() as $type => $description) {
        $supported = SchemaBuilderFactory::isSupported($type) ? "✅" : "❌";
        echo "  $supported $type: $description\n";
    }

    echo "\nAvailable Query Builders:\n";
    foreach (QueryBuilderFactory::getAvailableTypes() as $type => $description) {
        $supported = QueryBuilderFactory::isSupported($type) ? "✅" : "❌";
        echo "  $supported $type: $description\n";
    }

    echo "\n🎉 Interface-Based Architecture Demo Completed!\n\n";

    // Example 8: Real-world usage comparison
    echo "🌍 8. Real-World Usage Comparison\n";
    echo "--------------------------------\n";

    echo "Before (Database-Specific Code):\n";
    echo "```php\n";
    echo "// MySQL-specific code\n";
    echo "\$mysqlSchema->createTable('users', function(\$table) {\n";
    echo "    \$table->increments('id');\n";
    echo "    \$table->json('data'); // MySQL JSON\n";
    echo "});\n\n";
    
    echo "// PostgreSQL-specific code (different API)\n";
    echo "\$pgSchema->createTable('users', function(\$table) {\n";
    echo "    \$table->serial('id');\n";
    echo "    \$table->jsonb('data'); // PostgreSQL JSONB\n";
    echo "});\n";
    echo "```\n\n";

    echo "After (Unified Interface):\n";
    echo "```php\n";
    echo "// Same code works for both databases!\n";
    echo "\$schema = SchemaBuilderFactory::create(\$db); // Auto-detects type\n";
    echo "\$schema->increments('id')\n";
    echo "       ->json('data')      // Becomes JSON or JSONB automatically\n";
    echo "       ->createTable('users');\n";
    echo "```\n\n";

    echo "✅ Unified interface eliminates database-specific code!\n";
    echo "✅ Same API works across different database engines!\n";
    echo "✅ Database-specific optimizations happen automatically!\n";
    echo "✅ Easy to switch between databases without code changes!\n\n";

    // Cleanup
    echo "🧹 Cleaning up...\n";
    try {
        $mysqlSchema->dropTable('users_mysql');
        $mysqlSchema->dropTable('advanced_mysql');
        echo "✅ MySQL tables cleaned up\n";
    } catch (Exception $e) {
        echo "ℹ️  MySQL cleanup skipped (tables may not exist)\n";
    }

    try {
        $pgSchema->dropTable('users_postgresql');
        $pgSchema->dropTable('advanced_postgresql');
        echo "✅ PostgreSQL tables cleaned up\n";
    } catch (Exception $e) {
        echo "ℹ️  PostgreSQL cleanup skipped (tables may not exist)\n";
    }

    echo "\n🎊 Interface-Based Architecture is Working Perfectly!\n";
    echo "SimpleMDB now supports multiple databases with a unified, clean architecture!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure both MySQL and PostgreSQL are running and accessible.\n";
    echo "💡 This demo showcases the architecture even if databases aren't available.\n";
}
?>

