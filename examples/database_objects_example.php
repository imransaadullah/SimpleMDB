<?php
/**
 * SimpleMDB Database Objects Example
 * 
 * This example demonstrates the comprehensive database object management capabilities
 * including functions, procedures, views, events, and triggers.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\DatabaseObjects\DatabaseObjectManager;
use SimpleMDB\SchemaBuilder;

// Connect to database
$db = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'simplemdb_demo');

// Initialize the database object manager
$objects = new DatabaseObjectManager($db);

echo "🚀 SimpleMDB Database Objects Demo\n";
echo "==================================\n\n";

// Create some test tables first
$schema = new SchemaBuilder($db);

// Users table
$schema->increments('id')
       ->string('name', 100)
       ->string('email', 150)->unique()
       ->decimal('balance', 10, 2)->default(0.00)
       ->boolean('is_active')->default(true)
       ->timestamps()
       ->createTable('users');

// Orders table
$schema->increments('id')
       ->integer('user_id')->unsigned()
       ->decimal('total', 10, 2)
       ->enum('status', ['pending', 'paid', 'shipped', 'delivered'])
       ->timestamps()
       ->foreignKey('user_id', 'users', 'id')
       ->createTable('orders');

// Audit log table
$schema->increments('id')
       ->string('table_name', 50)
       ->string('action', 20)
       ->integer('record_id')->unsigned()
       ->json('old_data')->nullable()
       ->json('new_data')->nullable()
       ->timestamp('created_at')->default('CURRENT_TIMESTAMP')
       ->createTable('audit_log');

echo "✅ Test tables created\n\n";

// ============================================================================
// 1. FUNCTIONS
// ============================================================================

echo "📊 1. Database Functions\n";
echo "------------------------\n";

// Create a function to calculate user's total order value
$objects->function('get_user_total_orders')
    ->inParameter('user_id', 'INT')
    ->returns('DECIMAL(10,2)')
    ->deterministic()
    ->readsSqlData()
    ->comment('Calculate total order value for a user')
    ->body("
        DECLARE total_amount DECIMAL(10,2) DEFAULT 0.00;
        SELECT COALESCE(SUM(total), 0.00) INTO total_amount 
        FROM orders 
        WHERE user_id = user_id AND status != 'cancelled';
        RETURN total_amount;
    ")
    ->create();

echo "✅ Function 'get_user_total_orders' created\n";

// Create a function to format currency
$objects->function('format_currency')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->inParameter('currency_code', 'VARCHAR(3)')
    ->returns('VARCHAR(50)')
    ->deterministic()
    ->noSql()
    ->comment('Format amount as currency')
    ->body("
        DECLARE formatted VARCHAR(50);
        SET formatted = CONCAT(currency_code, ' ', FORMAT(amount, 2));
        RETURN formatted;
    ")
    ->create();

echo "✅ Function 'format_currency' created\n";

// Test the functions
$result = $db->query("SELECT get_user_total_orders(1) as total");
echo "📈 Test function result: " . $result->fetchColumn(0) . "\n";

$result = $db->query("SELECT format_currency(1234.56, 'USD') as formatted");
echo "💰 Test function result: " . $result->fetchColumn(0) . "\n\n";

// ============================================================================
// 2. PROCEDURES
// ============================================================================

echo "📋 2. Database Procedures\n";
echo "-------------------------\n";

// Create a procedure to process user registration
$objects->procedure('register_user')
    ->inParameter('user_name', 'VARCHAR(100)')
    ->inParameter('user_email', 'VARCHAR(150)')
    ->outParameter('user_id', 'INT')
    ->outParameter('success', 'BOOLEAN')
    ->modifiesSqlData()
    ->comment('Register a new user and return the user ID')
    ->body("
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            SET success = FALSE;
            SET user_id = NULL;
        END;
        
        INSERT INTO users (name, email) VALUES (user_name, user_email);
        SET user_id = LAST_INSERT_ID();
        SET success = TRUE;
    ")
    ->create();

echo "✅ Procedure 'register_user' created\n";

// Create a procedure to update user balance
$objects->procedure('update_user_balance')
    ->inParameter('user_id', 'INT')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->inoutParameter('new_balance', 'DECIMAL(10,2)')
    ->modifiesSqlData()
    ->comment('Update user balance and return new balance')
    ->body("
        UPDATE users SET balance = balance + amount WHERE id = user_id;
        SELECT balance INTO new_balance FROM users WHERE id = user_id;
    ")
    ->create();

echo "✅ Procedure 'update_user_balance' created\n";

// Test the procedures
$objects->procedure('register_user')->call(['John Doe', 'john@example.com', null, null]);
echo "👤 Test procedure: User registered\n";

$objects->procedure('update_user_balance')->call([1, 100.00, 0.00]);
echo "💰 Test procedure: Balance updated\n\n";

// ============================================================================
// 3. VIEWS
// ============================================================================

echo "👁️  3. Database Views\n";
echo "---------------------\n";

// Create a view for active users with their order totals
$objects->view('active_users_summary')
    ->select("
        u.id,
        u.name,
        u.email,
        u.balance,
        get_user_total_orders(u.id) as total_orders,
        COUNT(o.id) as order_count,
        u.created_at
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.is_active = TRUE
    GROUP BY u.id
    ")
    ->comment('Summary view of active users with their order statistics')
    ->create();

echo "✅ View 'active_users_summary' created\n";

// Create a view for recent orders with user details
$objects->view('recent_orders')
    ->select("
        o.id as order_id,
        o.total,
        o.status,
        o.created_at,
        u.name as customer_name,
        u.email as customer_email,
        format_currency(o.total, 'USD') as formatted_total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY o.created_at DESC
    ")
    ->comment('Recent orders with customer details and formatted totals')
    ->create();

echo "✅ View 'recent_orders' created\n";

// Test the views
$result = $db->query("SELECT COUNT(*) FROM active_users_summary");
echo "📊 Active users count: " . $result->fetchColumn(0) . "\n";

$result = $db->query("SELECT COUNT(*) FROM recent_orders");
echo "📦 Recent orders count: " . $result->fetchColumn(0) . "\n\n";

// ============================================================================
// 4. EVENTS
// ============================================================================

echo "⏰ 4. Database Events\n";
echo "--------------------\n";

// Create an event to clean up old audit logs
$objects->event('cleanup_audit_logs')
    ->every('1 DAY')
    ->body("
        DELETE FROM audit_log 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ")
    ->comment('Clean up audit logs older than 90 days')
    ->create();

echo "✅ Event 'cleanup_audit_logs' created\n";

// Create an event to update user statistics
$objects->event('update_user_stats')
    ->every('1 HOUR')
    ->body("
        UPDATE users u 
        SET balance = (
            SELECT COALESCE(SUM(total), 0.00) 
            FROM orders 
            WHERE user_id = u.id AND status = 'paid'
        ) - (
            SELECT COALESCE(SUM(total), 0.00) 
            FROM orders 
            WHERE user_id = u.id AND status = 'pending'
        )
    ")
    ->comment('Update user balances based on order status')
    ->create();

echo "✅ Event 'update_user_stats' created\n";

// Test event creation
$events = $objects->getEvents();
echo "📅 Total events created: " . count($events) . "\n\n";

// ============================================================================
// 5. TRIGGERS
// ============================================================================

echo "🔧 5. Database Triggers\n";
echo "----------------------\n";

// Create a trigger to audit user changes
$objects->trigger('audit_users_changes')
    ->after()
    ->update()
    ->on('users')
    ->comment('Audit user table changes')
    ->body("
        INSERT INTO audit_log (table_name, action, record_id, old_data, new_data)
        VALUES (
            'users',
            'UPDATE',
            NEW.id,
            JSON_OBJECT('name', OLD.name, 'email', OLD.email, 'balance', OLD.balance),
            JSON_OBJECT('name', NEW.name, 'email', NEW.email, 'balance', NEW.balance)
        );
    ")
    ->create();

echo "✅ Trigger 'audit_users_changes' created\n";

// Create a trigger to validate order amounts
$objects->trigger('validate_order_amount')
    ->before()
    ->insert()
    ->on('orders')
    ->comment('Validate order amount before insertion')
    ->body("
        IF NEW.total <= 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Order total must be greater than 0';
        END IF;
    ")
    ->create();

echo "✅ Trigger 'validate_order_amount' created\n";

// Create a trigger to update user balance on order creation
$objects->trigger('update_balance_on_order')
    ->after()
    ->insert()
    ->on('orders')
    ->comment('Update user balance when order is created')
    ->body("
        UPDATE users 
        SET balance = balance - NEW.total 
        WHERE id = NEW.user_id;
    ")
    ->create();

echo "✅ Trigger 'update_balance_on_order' created\n";

// Test triggers
$triggers = $objects->getTriggers();
echo "🔧 Total triggers created: " . count($triggers) . "\n\n";

// ============================================================================
// 6. COMPREHENSIVE DEMO
// ============================================================================

echo "🎯 6. Comprehensive Demo\n";
echo "------------------------\n";

// Insert test data
$db->query("INSERT INTO users (name, email, balance) VALUES 
    ('Alice Johnson', 'alice@example.com', 500.00),
    ('Bob Smith', 'bob@example.com', 750.00),
    ('Carol Davis', 'carol@example.com', 300.00)");

$db->query("INSERT INTO orders (user_id, total, status) VALUES 
    (1, 150.00, 'paid'),
    (1, 75.50, 'pending'),
    (2, 200.00, 'shipped'),
    (3, 50.00, 'delivered')");

echo "✅ Test data inserted\n";

// Demonstrate function usage
echo "\n📊 Function Results:\n";
$result = $db->query("SELECT name, get_user_total_orders(id) as total FROM users");
$users = $result->fetchAll('assoc');
foreach ($users as $user) {
    echo "  - {$user['name']}: USD " . number_format($user['total'], 2) . "\n";
}

// Demonstrate view usage
echo "\n👁️  View Results:\n";
$result = $db->query("SELECT customer_name, formatted_total FROM recent_orders LIMIT 3");
$orders = $result->fetchAll('assoc');
foreach ($orders as $order) {
    echo "  - {$order['customer_name']}: {$order['formatted_total']}\n";
}

// Demonstrate procedure usage
echo "\n📋 Procedure Results:\n";
$objects->procedure('register_user')->call(['Demo User', 'demo@example.com', null, null]);
echo "  - New user registered via procedure\n";

// Get object statistics
echo "\n📈 Database Object Statistics:\n";
$counts = $objects->getObjectCounts();
foreach ($counts as $type => $count) {
    echo "  - {$type}: {$count}\n";
}

// ============================================================================
// 7. CLEANUP
// ============================================================================

echo "\n🧹 7. Cleanup\n";
echo "-------------\n";

// Drop all objects (in reverse dependency order)
$objects->dropAllTriggers();
$objects->dropAllViews();
$objects->dropAllEvents();
$objects->dropAllProcedures();
$objects->dropAllFunctions();

echo "✅ All database objects cleaned up\n";

// Drop test tables
$db->query("DROP TABLE IF EXISTS audit_log");
$db->query("DROP TABLE IF EXISTS orders");
$db->query("DROP TABLE IF EXISTS users");

echo "✅ Test tables cleaned up\n";

echo "\n🎉 Database Objects Demo Complete!\n";
echo "SimpleMDB now supports comprehensive database object management:\n";
echo "  ✅ Functions with parameters and return types\n";
echo "  ✅ Procedures with IN/OUT/INOUT parameters\n";
echo "  ✅ Views with complex queries and formatting\n";
echo "  ✅ Events with flexible scheduling\n";
echo "  ✅ Triggers for data integrity and auditing\n";
echo "  ✅ Unified management interface\n";
echo "  ✅ Comprehensive validation and error handling\n"; 