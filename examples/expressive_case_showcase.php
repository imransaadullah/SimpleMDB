<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\CaseBuilderFactory;

echo "✨ Expressive CASE Building Showcase\n";
echo "===================================\n\n";

echo "🎯 1. Simple vs Expressive Syntax\n";
echo "---------------------------------\n";

// Before: Basic syntax
echo "Before (basic):\n";
echo "\$case->when('status = 1', 'Active')->when('status = 0', 'Inactive');\n\n";

// After: Expressive syntax
echo "After (expressive):\n";
$expressiveCase = CaseBuilderFactory::createMySQL()
    ->case()
    ->whenEquals('status', 1, 'Active')
    ->whenEquals('status', 0, 'Inactive')
    ->elseValue('Unknown')
    ->end('status_text');

echo "\$case->whenEquals('status', 1, 'Active')\n";
echo "     ->whenEquals('status', 0, 'Inactive')\n";
echo "     ->elseValue('Unknown');\n";
echo "Result: $expressiveCase\n\n";

echo "🚀 2. Rich Condition Methods\n";
echo "---------------------------\n";

// Comparison operations
$comparisonCase = CaseBuilderFactory::createMySQL()
    ->case()
    ->whenGreaterThan('score', 90, 'Excellent')
    ->whenBetween('score', 70, 89, 'Good')
    ->whenLessThan('score', 70, 'Needs Improvement')
    ->end('performance');

echo "Comparison Case: $comparisonCase\n";

// IN and LIKE operations  
$patternCase = CaseBuilderFactory::createMySQL()
    ->case()
    ->whenIn('department', ['HR', 'Finance', 'Legal'], 'Support')
    ->whenLike('department', '%Engineering%', 'Technical')
    ->whenNull('department', 'Unassigned')
    ->else('Other')
    ->end('division');

echo "Pattern Case: $patternCase\n";

// Logical operations
$logicalCase = CaseBuilderFactory::createMySQL()
    ->case()
    ->whenAll(['active' => 1, 'verified' => 1], 'Active Verified')
    ->whenAny(['trial' => 1, 'demo' => 1], 'Trial User')
    ->else('Regular User')
    ->end('user_type');

echo "Logical Case: $logicalCase\n\n";

echo "🔮 3. PostgreSQL Advanced Features\n";
echo "---------------------------------\n";

// JSONB operations
$jsonbCase = CaseBuilderFactory::createPostgreSQL()
    ->case()
    ->whenJsonbHasKey('preferences', 'theme', 'Customized')
    ->whenJsonbContains('preferences', ['premium' => true], 'Premium User')
    ->else('Basic User')
    ->end('user_tier');

echo "JSONB Case: $jsonbCase\n";

// Array operations
$arrayCase = CaseBuilderFactory::createPostgreSQL()
    ->case()
    ->whenArrayContains('skills', 'PHP', 'PHP Developer')
    ->whenArrayLength('skills', 0, 'No Skills Listed')
    ->else('Multi-Skill Developer')
    ->end('developer_type');

echo "Array Case: $arrayCase\n";

// PostgreSQL ILIKE (case-insensitive)
$iLikeCase = CaseBuilderFactory::createPostgreSQL()
    ->case()
    ->whenILike('name', '%admin%', 'Administrator')
    ->whenILike('name', '%manager%', 'Manager')
    ->else('Employee')
    ->end('role_level');

echo "ILIKE Case: $iLikeCase\n\n";

echo "🎨 4. Quick Builder Patterns\n";
echo "---------------------------\n";

// Boolean to text conversion
$booleanCase = CaseBuilderFactory::MySQLCaseBuilder::booleanToText('is_published', 'Published', 'Draft');
echo "Boolean to Text: $booleanCase\n";

// Status label mapping
$statusMap = [
    '1' => 'Active',
    '2' => 'Pending', 
    '3' => 'Suspended',
    '0' => 'Inactive'
];
$statusCase = CaseBuilderFactory::MySQLCaseBuilder::statusLabels('user_status', $statusMap);
echo "Status Labels: $statusCase\n";

// Price category ranges
$priceRanges = [
    '<25' => 'Budget',
    '25-100' => 'Standard',
    '100-500' => 'Premium',
    '>500' => 'Luxury'
];
$priceCase = CaseBuilderFactory::MySQLCaseBuilder::priceCategory('product_price', $priceRanges);
echo "Price Categories: $priceCase\n\n";

echo "🏆 5. Real-World Business Logic\n";
echo "------------------------------\n";

// Customer segmentation
$customerSegment = CaseBuilderFactory::createMySQL()
    ->case()
    ->whenAll(['orders_count' => '>10', 'total_spent' => '>1000'], 'VIP')
    ->whenGreaterThan('orders_count', 5, 'Loyal')
    ->whenGreaterThan('orders_count', 0, 'Customer')
    ->else('Prospect')
    ->end('segment');

echo "Customer Segmentation: $customerSegment\n";

// Order urgency
$orderUrgency = CaseBuilderFactory::createPostgreSQL()
    ->case()
    ->whenEquals('priority', 'urgent', 'Process Immediately')
    ->whenAll(['priority' => 'high', 'value' => '>500'], 'High Priority Queue')
    ->whenBetween('days_pending', 7, 14, 'Follow Up Required')
    ->whenGreaterThan('days_pending', 14, 'Overdue')
    ->else('Normal Processing')
    ->end('action_required');

echo "Order Urgency: $orderUrgency\n";

// Content moderation
$contentModeration = CaseBuilderFactory::createPostgreSQL()
    ->case()
    ->whenArrayContains('flags', 'spam', 'Auto-Reject')
    ->whenJsonbHasKey('metadata', 'reviewed', 'Manual Review')
    ->whenGreaterThan('reports_count', 3, 'Needs Review')
    ->else('Auto-Approve')
    ->end('moderation_action');

echo "Content Moderation: $contentModeration\n\n";

echo "🎉 Expressive CASE Building Showcase Complete!\n";
echo "==============================================\n";

echo "✨ Key Improvements:\n";
echo "✅ Rich, readable condition methods\n";
echo "✅ Database-specific optimizations\n";
echo "✅ Quick builder patterns for common use cases\n";
echo "✅ Advanced features (JSONB, arrays, ILIKE)\n";
echo "✅ Logical operators (AND, OR combinations)\n";
echo "✅ Proper parameter binding for security\n";
echo "✅ Fluent, chainable API\n";
echo "✅ Real-world business logic support\n";

echo "\n🚀 CASE building is now highly expressive and production-ready!\n";
echo "Developers can build complex conditional logic with readable, maintainable code.\n";
?>

