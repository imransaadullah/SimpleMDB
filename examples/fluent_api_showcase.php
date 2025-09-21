<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\CaseBuilder\MySQL\FluentMySQLCaseBuilder;
use SimpleMDB\CaseBuilder\PostgreSQL\FluentPostgreSQLCaseBuilder;

echo "🎨 Truly Fluent API Showcase\n";
echo "===========================\n\n";

echo "✨ This is what FLUENT really means:\n\n";

echo "🎯 1. Natural Language Syntax\n";
echo "-----------------------------\n";

// Reads like English sentences!
$userStatus = FluentMySQLCaseBuilder::column('status')
    ->equals(1)->thenShow('Active')
    ->column('verified')
    ->isTrue()->thenShow('Verified User')
    ->column('last_login')
    ->isNull()->thenShow('Never Logged In')
    ->else('Unknown Status')
    ->end('user_classification');

echo "User Status (reads like English):\n";
echo "  When status equals 1, then show 'Active'\n";
echo "  When verified is true, then show 'Verified User'\n";
echo "  When last_login is null, then show 'Never Logged In'\n";
echo "  Else 'Unknown Status'\n";
echo "\nSQL: $userStatus\n\n";

echo "🚀 2. Semantic Method Names\n";
echo "--------------------------\n";

$accountType = FluentMySQLCaseBuilder::when('subscription_type')
    ->equals('premium')->thenShow('Premium Account')
    ->when('is_trial')
    ->isActive()->thenActive()
    ->when('payment_status')
    ->equals('paid')->thenShow('Paid Account')
    ->else('Free Account')
    ->end('account_classification');

echo "Account Type (semantic methods):\n";
echo "SQL: $accountType\n\n";

echo "🔮 3. PostgreSQL Advanced Fluent Features\n";
echo "-----------------------------------------\n";

$advancedClassification = FluentPostgreSQLCaseBuilder::when('user_preferences')
    ->hasJsonbKey('premium_features')->thenShow('Premium User')
    ->when('skills_array')
    ->containsArrayValue('PostgreSQL')->thenShow('Database Expert')
    ->when('user_preferences')
    ->containsJsonb(['notifications' => true])->thenShow('Active Subscriber')
    ->when('tags_array')
    ->arrayHasLength(0)->thenShow('Untagged User')
    ->else('Regular User')
    ->end('user_tier');

echo "Advanced PostgreSQL Classification:\n";
echo "SQL: $advancedClassification\n\n";

echo "🎨 4. Complex Business Logic Made Simple\n";
echo "---------------------------------------\n";

// Customer loyalty program
$loyaltyTier = FluentMySQLCaseBuilder::when('total_spent')
    ->greaterThan(10000)->thenShow('Platinum')
    ->when('total_spent')
    ->between(5000, 9999)->thenShow('Gold')
    ->when('total_spent')
    ->between(1000, 4999)->thenShow('Silver')
    ->when('total_orders')
    ->greaterThan(0)->thenShow('Bronze')
    ->else('New Customer')
    ->end('loyalty_tier');

echo "Loyalty Program:\n";
echo "SQL: $loyaltyTier\n\n";

// Content moderation workflow
$moderationAction = FluentPostgreSQLCaseBuilder::when('report_flags')
    ->containsArrayValue('spam')->thenShow('Auto-Block')
    ->when('content_metadata')
    ->hasJsonbKey('ai_reviewed')->thenShow('AI Approved')
    ->when('user_reputation')
    ->greaterThan(1000)->thenShow('Auto-Approve')
    ->when('content_length')
    ->lessThan(50)->thenShow('Manual Review')
    ->else('Standard Review')
    ->end('moderation_decision');

echo "Content Moderation:\n";
echo "SQL: $moderationAction\n\n";

echo "🏆 5. Comparison with Industry Standards\n";
echo "---------------------------------------\n";

echo "❌ Laravel Eloquent (not fluent for CASE):\n";
echo "DB::raw('CASE WHEN status = 1 THEN \"Active\" ELSE \"Inactive\" END')\n\n";

echo "❌ Doctrine DBAL (verbose):\n";
echo "\$expr->case()\n";
echo "     ->when(\$expr->eq('status', 1))\n";
echo "     ->then(\$expr->literal('Active'))\n";
echo "     ->else(\$expr->literal('Inactive'))\n\n";

echo "✅ SimpleMDB (truly fluent):\n";
echo "FluentMySQLCaseBuilder::when('status')\n";
echo "    ->equals(1)->thenShow('Active')\n";
echo "    ->else('Inactive')\n\n";

echo "🎊 Fluent API Benefits:\n";
echo "======================\n";
echo "✅ Reads like natural language\n";
echo "✅ Self-documenting code\n";
echo "✅ Reduced cognitive load\n";
echo "✅ Fewer errors (semantic methods)\n";
echo "✅ Better IDE support\n";
echo "✅ More maintainable\n";
echo "✅ Intuitive for new developers\n";

echo "\n🚀 SimpleMDB now has the most fluent CASE building API in the PHP ecosystem!\n";
?>
