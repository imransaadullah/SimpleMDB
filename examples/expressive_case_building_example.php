<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\CaseBuilderFactory;
use SimpleMDB\QueryBuilderFactory;

echo "✨ Expressive CASE Building Demo\n";
echo "===============================\n\n";

try {
    // Create database connections for demonstration
    $mysqlDb = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'testdb');
    $pgDb = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'testdb');

    echo "1. 🎯 Basic CASE Expressions\n";
    echo "---------------------------\n";

    // Simple CASE (MySQL)
    $mysqlCase = CaseBuilderFactory::createMySQL();
    $simpleCaseSQL = $mysqlCase->case('status')
        ->when('1', 'Active')
        ->when('0', 'Inactive')
        ->when('2', 'Pending')
        ->else('Unknown')
        ->end('status_label');
    
    echo "MySQL Simple CASE: $simpleCaseSQL\n";

    // Searched CASE (PostgreSQL)
    $pgCase = CaseBuilderFactory::createPostgreSQL();
    $searchedCaseSQL = $pgCase->case()
        ->whenGreaterThan('price', 100, 'Expensive')
        ->whenBetween('price', 50, 100, 'Moderate')
        ->else('Cheap')
        ->end('price_category');
    
    echo "PostgreSQL Searched CASE: $searchedCaseSQL\n\n";

    echo "2. 🚀 Expressive Condition Methods\n";
    echo "---------------------------------\n";

    $expressiveCase = CaseBuilderFactory::create($mysqlDb);
    
    // Rich condition methods
    $conditions = $expressiveCase->case()
        ->whenEquals('department', 'Engineering', 'Tech Team')
        ->whenIn('department', ['Marketing', 'Sales'], 'Business Team')
        ->whenLike('department', '%Support%', 'Customer Team')
        ->whenNull('department', 'Unassigned')
        ->elseValue('Other Team')
        ->end('team_category');
    
    echo "Expressive Conditions: $conditions\n";

    // Multiple conditions with logical operators
    $multiConditions = $expressiveCase->reset()->case()
        ->whenAll(['status' => 'active', 'verified' => 1], 'Verified User')
        ->whenAny(['status' => 'pending', 'status' => 'review'], 'Under Review')
        ->whenNotNull('banned_at', 'Banned')
        ->else('Unknown Status')
        ->end('user_status');
    
    echo "Multi-Conditions: $multiConditions\n\n";

    echo "3. 🎨 Quick Builder Patterns\n";
    echo "---------------------------\n";

    // Quick boolean conversion
    $booleanCase = CaseBuilderFactory::MySQLCaseBuilder::booleanToText('is_active', 'Yes', 'No');
    echo "Boolean to Text: $booleanCase\n";

    // Status mapping
    $statusMap = [
        '1' => 'Published',
        '2' => 'Draft', 
        '3' => 'Archived',
        '0' => 'Deleted'
    ];
    $statusCase = CaseBuilderFactory::MySQLCaseBuilder::statusLabels('post_status', $statusMap);
    echo "Status Labels: $statusCase\n";

    // Price categories
    $priceRanges = [
        '<10' => 'Budget',
        '10-50' => 'Standard',
        '50-100' => 'Premium',
        '>100' => 'Luxury'
    ];
    $priceCase = CaseBuilderFactory::MySQLCaseBuilder::priceCategory('price', $priceRanges);
    echo "Price Categories: $priceCase\n\n";

    echo "4. 🔮 PostgreSQL Advanced Features\n";
    echo "---------------------------------\n";

    $pgAdvanced = CaseBuilderFactory::createPostgreSQL();
    
    // JSONB operations
    $jsonbCase = $pgAdvanced->case()
        ->whenJsonbHasKey('preferences', 'theme', 'Customized')
        ->whenJsonbContains('preferences', ['notifications' => true], 'Active User')
        ->else('Basic User')
        ->end('user_type');
    
    echo "JSONB CASE: $jsonbCase\n";

    // Array operations
    $arrayCase = $pgAdvanced->reset()->case()
        ->whenArrayContains('tags', 'premium', 'Premium Content')
        ->whenArrayLength('tags', 0, 'Untagged')
        ->else('Regular Content')
        ->end('content_type');
    
    echo "Array CASE: $arrayCase\n";

    // PostgreSQL ILIKE (case-insensitive LIKE)
    $iLikeCase = $pgAdvanced->reset()->case()
        ->whenILike('name', '%admin%', 'Administrator')
        ->whenILike('name', '%manager%', 'Manager')
        ->else('Regular User')
        ->end('role_type');
    
    echo "ILIKE CASE: $iLikeCase\n\n";

    echo "5. 🏗️  Integration with Query Builder\n";
    echo "------------------------------------\n";

    // Integrate CASE with SELECT queries
    $query = QueryBuilderFactory::create($mysqlDb);
    
    $complexQuery = $query->select([
            'id',
            'name',
            'email',
            CaseBuilderFactory::create($mysqlDb)->case('status')
                ->whenEquals('status', 1, 'Active')
                ->whenEquals('status', 0, 'Inactive')
                ->else('Unknown')
                ->end('status_text')
        ])
        ->from('users')
        ->where('created_at > ?', ['2024-01-01'])
        ->toSql();
    
    echo "Query with CASE: $complexQuery\n\n";

    echo "6. 🎭 Real-World Examples\n";
    echo "------------------------\n";

    // User permission levels
    $permissionCase = CaseBuilderFactory::create($mysqlDb)->case()
        ->whenEquals('role', 'admin', 'Full Access')
        ->whenEquals('role', 'moderator', 'Limited Access')
        ->whenEquals('role', 'user', 'Read Only')
        ->whenNull('role', 'No Access')
        ->else('Custom Access')
        ->end('permission_level');
    
    echo "Permission Levels: $permissionCase\n";

    // Order priority
    $priorityCase = CaseBuilderFactory::create($pgDb)->case()
        ->whenGreaterThan('total', 1000, 'High Priority')
        ->whenBetween('total', 500, 1000, 'Medium Priority')
        ->whenGreaterThan('total', 0, 'Low Priority')
        ->else('Invalid Order')
        ->end('order_priority');
    
    echo "Order Priority: $priorityCase\n";

    // Customer segment
    $segmentCase = CaseBuilderFactory::create($pgDb)->case()
        ->whenAll(['orders_count' => '>10', 'total_spent' => '>1000'], 'VIP Customer')
        ->whenAny(['orders_count' => '>5', 'total_spent' => '>500'], 'Regular Customer')
        ->whenGreaterThan('orders_count', 0, 'New Customer')
        ->else('Prospect')
        ->end('customer_segment');
    
    echo "Customer Segment: $segmentCase\n\n";

    echo "7. 🎨 Expressive API Comparison\n";
    echo "------------------------------\n";
    
    echo "Before (basic):\n";
    echo "\$case->when('status = 1', 'Active')->when('status = 0', 'Inactive')->else('Unknown');\n\n";
    
    echo "After (expressive):\n";
    echo "\$case->whenEquals('status', 1, 'Active')\n";
    echo "     ->whenEquals('status', 0, 'Inactive')\n";
    echo "     ->elseValue('Unknown');\n\n";
    
    echo "Even better (quick builders):\n";
    echo "CaseBuilderFactory::MySQLCaseBuilder::booleanToText('is_active');\n";
    echo "CaseBuilderFactory::MySQLCaseBuilder::statusLabels('status', \$statusMap);\n\n";

    echo "🎉 Expressive CASE Building Complete!\n";
    echo "====================================\n";
    echo "✅ Rich condition methods (whenEquals, whenBetween, whenIn, etc.)\n";
    echo "✅ Logical operators (whenAll, whenAny)\n";
    echo "✅ Quick builder patterns (booleanToText, statusLabels, priceCategory)\n";
    echo "✅ Database-specific features (JSONB, arrays, ILIKE)\n";
    echo "✅ Fluent, readable syntax\n";
    echo "✅ Proper parameter binding and SQL injection prevention\n";
    echo "✅ Integration with query builders\n";
    echo "\n🚀 CASE building is now highly expressive and developer-friendly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 This demo showcases the expressive CASE API even without database connections.\n";
}
?>

