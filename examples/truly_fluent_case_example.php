<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\CaseBuilder\MySQL\FluentMySQLCaseBuilder;
use SimpleMDB\CaseBuilder\PostgreSQL\FluentPostgreSQLCaseBuilder;

echo "✨ Truly Fluent CASE Building\n";
echo "============================\n\n";

echo "🎯 1. Natural Language Syntax\n";
echo "-----------------------------\n";

// MySQL fluent syntax - reads like English!
$userStatus = FluentMySQLCaseBuilder::when('status')
    ->equals(1)->thenShow('Active')
    ->when('status')
    ->equals(0)->thenShow('Inactive')
    ->when('status')
    ->equals(2)->thenShow('Pending')
    ->else('Unknown')
    ->end('user_status');

echo "MySQL User Status: $userStatus\n";

// Even more fluent with semantic methods
$accountType = FluentMySQLCaseBuilder::when('is_premium')
    ->isTrue()->thenShow('Premium Account')
    ->when('is_trial')
    ->isTrue()->thenShow('Trial Account')
    ->else('Basic Account')
    ->end('account_type');

echo "Account Type: $accountType\n\n";

echo "🚀 2. PostgreSQL Advanced Fluent Syntax\n";
echo "---------------------------------------\n";

// PostgreSQL with advanced features
$userTier = FluentPostgreSQLCaseBuilder::when('preferences')
    ->hasJsonbKey('premium')->thenShow('Premium User')
    ->when('skills')
    ->containsArrayValue('PostgreSQL')->thenShow('Database Expert')
    ->when('last_login')
    ->isNotNull()->thenShow('Active User')
    ->else('Basic User')
    ->end('user_tier');

echo "PostgreSQL User Tier: $userTier\n";

// JSONB and array operations
$contentType = FluentPostgreSQLCaseBuilder::when('metadata')
    ->containsJsonb(['featured' => true])->thenShow('Featured Content')
    ->when('tags')
    ->arrayHasLength(0)->thenShow('Untagged Content')
    ->when('tags')
    ->containsArrayValue('premium')->thenShow('Premium Content')
    ->else('Regular Content')
    ->end('content_classification');

echo "Content Type: $contentType\n\n";

echo "🎨 3. Business Logic Examples\n";
echo "----------------------------\n";

// Customer segmentation with fluent syntax
$customerSegment = FluentMySQLCaseBuilder::when('total_orders')
    ->greaterThan(50)->thenShow('VIP Customer')
    ->when('total_orders')
    ->between(10, 50)->thenShow('Loyal Customer')
    ->when('total_orders')
    ->greaterThan(0)->thenShow('Regular Customer')
    ->else('Prospect')
    ->end('customer_segment');

echo "Customer Segment: $customerSegment\n";

// Order priority with multiple conditions
$orderPriority = FluentPostgreSQLCaseBuilder::when('order_value')
    ->greaterThan(1000)->thenShow('High Priority')
    ->when('customer_type')
    ->equals('VIP')->thenShow('VIP Priority')
    ->when('days_pending')
    ->greaterThan(7)->thenShow('Overdue')
    ->else('Normal Priority')
    ->end('processing_priority');

echo "Order Priority: $orderPriority\n";

// Employee performance rating
$performanceRating = FluentMySQLCaseBuilder::when('score')
    ->greaterThan(90)->thenShow('Excellent')
    ->when('score')
    ->between(80, 90)->thenShow('Very Good')
    ->when('score')
    ->between(70, 79)->thenShow('Good')
    ->when('score')
    ->between(60, 69)->thenShow('Satisfactory')
    ->else('Needs Improvement')
    ->end('performance_rating');

echo "Performance Rating: $performanceRating\n\n";

echo "🏆 4. Comparison: Before vs After\n";
echo "--------------------------------\n";

echo "❌ Before (Not Fluent):\n";
echo "\$case->whenEquals('status', 1, 'Active')\n";
echo "     ->whenEquals('status', 0, 'Inactive')\n";
echo "     ->else('Unknown');\n\n";

echo "✅ After (Truly Fluent):\n";
echo "FluentMySQLCaseBuilder::when('status')\n";
echo "    ->equals(1)->thenShow('Active')\n";
echo "    ->when('status')\n";
echo "    ->equals(0)->thenShow('Inactive')\n";
echo "    ->else('Unknown');\n\n";

echo "🎯 5. Natural Language Reading\n";
echo "-----------------------------\n";

echo "The fluent syntax reads like natural language:\n\n";

echo "\"When status equals 1, then show 'Active'\"\n";
echo "FluentMySQLCaseBuilder::when('status')->equals(1)->thenShow('Active')\n\n";

echo "\"When price is greater than 100, then show 'Expensive'\"\n";
echo "FluentMySQLCaseBuilder::when('price')->greaterThan(100)->thenShow('Expensive')\n\n";

echo "\"When preferences has JSONB key 'theme', then show 'Customized'\"\n";
echo "FluentPostgreSQLCaseBuilder::when('preferences')->hasJsonbKey('theme')->thenShow('Customized')\n\n";

echo "🎉 Truly Fluent CASE Building Complete!\n";
echo "=======================================\n";

echo "✨ Key Fluent Features:\n";
echo "✅ Natural language syntax that reads like English\n";
echo "✅ Chainable method calls with semantic meaning\n";
echo "✅ Database-specific optimizations (MySQL vs PostgreSQL)\n";
echo "✅ Advanced features (JSONB, arrays, ILIKE)\n";
echo "✅ Semantic method names (thenShow, isActive, hasJsonbKey)\n";
echo "✅ Type-safe parameter binding\n";
echo "✅ Intuitive builder pattern\n";

echo "\n🚀 This is now a truly fluent, expressive CASE building API!\n";
echo "Developers can write complex conditional logic that reads like natural language.\n";
?>

