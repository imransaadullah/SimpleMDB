<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilderFactory;
use SimpleMDB\QueryBuilderFactory;

echo "🎨 Improved API Design Demo\n";
echo "===========================\n\n";

try {
    // 1. Clean database connection creation
    echo "1. Creating database connections...\n";
    $mysqlDb = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'testdb');
    $postgresDb = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'testdb');
    echo "   ✅ Clean database adapter names (no more SimplePDO_PostgreSQL!)\n";
    echo "   ✅ PostgreSQL adapter: " . get_class($postgresDb) . "\n\n";

    // 2. Intuitive factory API - auto-detection (preferred)
    echo "2. Intuitive factory API with auto-detection...\n";
    
    // Schema builders auto-detect database type
    $mysqlSchema = SchemaBuilderFactory::create($mysqlDb);  // Auto-detects MySQL
    $pgSchema = SchemaBuilderFactory::create($postgresDb);  // Auto-detects PostgreSQL
    
    echo "   ✅ MySQL schema builder: " . get_class($mysqlSchema) . "\n";
    echo "   ✅ PostgreSQL schema builder: " . get_class($pgSchema) . "\n";
    
    // Query builders auto-detect database type
    $mysqlQuery = QueryBuilderFactory::create($mysqlDb);   // Auto-detects MySQL
    $pgQuery = QueryBuilderFactory::create($postgresDb);   // Auto-detects PostgreSQL
    
    echo "   ✅ MySQL query builder: " . get_class($mysqlQuery) . "\n";
    echo "   ✅ PostgreSQL query builder: " . get_class($pgQuery) . "\n\n";

    // 3. Specific engine methods as fallback
    echo "3. Specific engine methods (fallback when needed)...\n";
    
    $specificMysqlQuery = QueryBuilderFactory::createMySQL();
    $specificPgQuery = QueryBuilderFactory::createPostgreSQL();
    
    echo "   ✅ Explicit MySQL query builder: " . get_class($specificMysqlQuery) . "\n";
    echo "   ✅ Explicit PostgreSQL query builder: " . get_class($specificPgQuery) . "\n\n";

    // 4. Demonstrate the clean API in action
    echo "4. Clean API in action...\n";
    
    // Same code, different engines automatically
    function createUserTable($db, $name) {
        $schema = SchemaBuilderFactory::create($db); // Auto-detects!
        return $schema->increments('id')
                      ->string('name', 100)
                      ->string('email', 150)->unique()
                      ->timestamps()
                      ->createTable($name);
    }
    
    function getUserQuery($db) {
        $query = QueryBuilderFactory::create($db); // Auto-detects!
        return $query->select(['id', 'name', 'email'])
                     ->from('users')
                     ->where('active = ?', [true])
                     ->toSql();
    }
    
    // This works for both MySQL and PostgreSQL automatically!
    echo "   MySQL SQL: " . getUserQuery($mysqlDb) . "\n";
    echo "   PostgreSQL SQL: " . getUserQuery($postgresDb) . "\n";
    echo "   ✅ Same code, different SQL syntax automatically!\n\n";

    // 5. Show the improved developer experience
    echo "5. Developer Experience Comparison...\n";
    echo "   Before (ugly):\n";
    echo "   \$pgDb = new SimplePDO_PostgreSQL(\$host, \$user, \$pass, \$db, 'UTF8', 'assoc', [], 5432);\n";
    echo "   \$schema = new SchemaBuilder_PostgreSQL(\$pgDb);\n\n";
    
    echo "   After (clean):\n";
    echo "   \$pgDb = DatabaseFactory::create('postgresql', \$host, \$user, \$pass, \$db);\n";
    echo "   \$schema = SchemaBuilderFactory::create(\$pgDb); // Auto-detects PostgreSQL!\n\n";

    // 6. Override auto-detection when needed
    echo "6. Override auto-detection (when needed)...\n";
    
    // Force specific type even with different database connection
    $forcedMysqlQuery = QueryBuilderFactory::create($postgresDb, 'mysql'); // Force MySQL syntax
    echo "   ✅ Forced MySQL syntax on PostgreSQL connection (edge case)\n";
    echo "   SQL: " . $forcedMysqlQuery->select(['id'])->from('test')->toSql() . "\n\n";

    echo "🎉 Improved API Design Complete!\n";
    echo "================================\n";
    echo "✅ Clean class names (PostgreSQLDatabase vs SimplePDO_PostgreSQL)\n";
    echo "✅ Intuitive factory methods with auto-detection\n";
    echo "✅ Specific engine methods as fallback\n";
    echo "✅ Same code works across different databases\n";
    echo "✅ Override capability when needed\n";
    echo "✅ Better developer experience\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 This demo shows the API design even without actual database connections.\n";
}
?>

