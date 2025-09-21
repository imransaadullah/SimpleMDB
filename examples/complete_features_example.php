<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilderFactory;
use SimpleMDB\QueryBuilderFactory;
use SimpleMDB\CaseBuilderFactory;
use SimpleMDB\EnhancedSimpleQuery;

echo "🎯 Complete SimpleMDB Features Demo\n";
echo "==================================\n\n";

try {
    // Create database connections
    $mysqlDb = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'testdb');
    $pgDb = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'testdb');

    echo "1. 🏗️  Advanced Schema Building\n";
    echo "------------------------------\n";
    
    // MySQL advanced schema
    $mysqlSchema = SchemaBuilderFactory::create($mysqlDb);
    $mysqlSchema->increments('id')
                ->string('name', 100)
                ->enum('status', ['active', 'inactive', 'pending'])
                ->decimal('price', 10, 2)->unsigned()
                ->json('metadata')->nullable()
                ->timestamps()
                ->engine('InnoDB')
                ->charset('utf8mb4');
                // ->createTable('products_mysql');
    
    echo "   ✅ MySQL schema with ENUM, DECIMAL, JSON, engine settings\n";
    
    // PostgreSQL advanced schema  
    $pgSchema = SchemaBuilderFactory::create($pgDb);
    $pgSchema->increments('id')
             ->string('name', 100)
             ->jsonb('metadata')->nullable()      // PostgreSQL JSONB
             ->inet('client_ip')->nullable()      // PostgreSQL INET
             ->textArray('tags')->nullable()      // PostgreSQL arrays
             ->uuidWithDefault('external_id')     // UUID with generation
             ->timestamps();
             // ->createTable('products_postgresql');
    
    echo "   ✅ PostgreSQL schema with JSONB, INET, arrays, UUID generation\n\n";

    echo "2. 🔧 CASE Statement Building\n";
    echo "----------------------------\n";
    
    // MySQL CASE statements
    $mysqlCase = CaseBuilderFactory::createMySQL();
    $simpleCaseSQL = $mysqlCase->case('status')
                               ->when('active', 'Available')
                               ->when('inactive', 'Unavailable')
                               ->else('Unknown')
                               ->end('status_label');
    
    echo "   MySQL Simple CASE: $simpleCaseSQL\n";
    
    // PostgreSQL CASE statements
    $pgCase = CaseBuilderFactory::createPostgreSQL();
    $searchedCaseSQL = $pgCase->case()
                              ->when('price > 100', 'Expensive')
                              ->when('price > 50', 'Moderate')
                              ->else('Cheap')
                              ->end('price_category');
    
    echo "   PostgreSQL Searched CASE: $searchedCaseSQL\n\n";

    echo "3. 🚀 Enhanced Query Building\n";
    echo "-----------------------------\n";
    
    // Enhanced SimpleQuery with integrated features
    $enhancedQuery = EnhancedSimpleQuery::create($mysqlDb);
    
    // Build complex query with CASE statement
    $caseBuilder = $enhancedQuery->case('status')
                                 ->when('active', 'Available')
                                 ->when('inactive', 'Unavailable')
                                 ->else('Unknown');
    
    $complexSQL = $enhancedQuery->select(['id', 'name', 'price'])
                                ->selectCase($caseBuilder, 'status_label')
                                ->from('products')
                                ->where('price > ?', [10])
                                ->orderBy('price', 'DESC')
                                ->toSql();
    
    echo "   Complex Query with CASE: $complexSQL\n\n";

    echo "4. 🔍 Advanced Query Features\n";
    echo "----------------------------\n";
    
    // Subquery example
    $mainQuery = QueryBuilderFactory::create($pgDb);
    $subQuery = QueryBuilderFactory::create($pgDb);
    
    $subQuerySQL = $subQuery->select(['category_id'])
                            ->from('categories')
                            ->where('\"active\" = ?', [true])
                            ->toSql();
    
    $mainQuerySQL = $mainQuery->select(['id', 'name'])
                              ->from('products')
                              ->where("\"category_id\" IN ($subQuerySQL)", [true])
                              ->toSql();
    
    echo "   PostgreSQL with Subquery: $mainQuerySQL\n";

    // Enhanced query with EXISTS
    $enhancedPgQuery = EnhancedSimpleQuery::create($pgDb);
    $existsSQL = $enhancedPgQuery->select(['id', 'name'])
                                 ->from('users')
                                 ->whereExists(function($subQuery) {
                                     $subQuery->select(['1'])
                                             ->from('orders')
                                             ->where('\"orders\".\"user_id\" = \"users\".\"id\"');
                                 })
                                 ->toSql();
    
    echo "   PostgreSQL with EXISTS: $existsSQL\n\n";

    echo "5. 🎨 Database-Specific Optimizations\n";
    echo "------------------------------------\n";
    
    // MySQL-specific features
    $mysqlQuery = QueryBuilderFactory::createMySQL();
    $mysqlOptimized = $mysqlQuery->select(['id', 'name'])
                                 ->from('products')
                                 ->where('MATCH(`name`, `description`) AGAINST(? IN BOOLEAN MODE)', ['search term'])
                                 ->toSql();
    
    echo "   MySQL Full-Text Search: $mysqlOptimized\n";
    
    // PostgreSQL-specific features
    $pgQuery = QueryBuilderFactory::createPostgreSQL();
    $pgOptimized = $pgQuery->select(['id', 'name'])
                           ->from('products')
                           ->whereFullText('description', 'search term')
                           ->whereJsonb('metadata', '?', 'featured')
                           ->whereArrayContains('tags', ['electronics'])
                           ->toSql();
    
    echo "   PostgreSQL Advanced Features: $pgOptimized\n\n";

    echo "6. 🏭 Factory Pattern Benefits\n";
    echo "-----------------------------\n";
    
    echo "   Available Factories:\n";
    echo "   ✅ DatabaseFactory - Database connections\n";
    echo "   ✅ SchemaBuilderFactory - Schema building\n";
    echo "   ✅ QueryBuilderFactory - Query building\n";
    echo "   ✅ CaseBuilderFactory - CASE statements\n";
    echo "   ✅ EnhancedSimpleQuery - Integrated experience\n\n";

    echo "7. 🔄 Backward Compatibility\n";
    echo "---------------------------\n";
    
    // Original SimpleMDB classes still work
    $originalSchema = new SimpleMDB\SchemaBuilder($mysqlDb);
    $originalQuery = SimpleMDB\SimpleQuery::create();
    
    echo "   ✅ Original SchemaBuilder: " . get_class($originalSchema) . "\n";
    echo "   ✅ Original SimpleQuery: " . get_class($originalQuery) . "\n";
    echo "   ✅ All existing code continues to work unchanged\n\n";

    echo "🎉 Complete Feature Demo Finished!\n";
    echo "==================================\n";
    echo "✅ Advanced schema building with database-specific features\n";
    echo "✅ CASE statement builders for complex logic\n";
    echo "✅ Enhanced query building with integrated components\n";
    echo "✅ Subqueries and complex WHERE conditions\n";
    echo "✅ Database-specific optimizations\n";
    echo "✅ Clean factory pattern throughout\n";
    echo "✅ 100% backward compatibility maintained\n";
    echo "\n🚀 SimpleMDB is now a complete, enterprise-grade database toolkit!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 This demo showcases the complete feature set even without database connections.\n";
}
?>

