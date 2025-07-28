<?php
/**
 * SimpleMDB Database Objects Execution & Modification Example
 * 
 * This example demonstrates how to execute and modify database objects
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

echo "🚀 SimpleMDB Execution & Modification Demo\n";
echo "==========================================\n\n";

// Create test tables
$schema = new SchemaBuilder($db);

$schema->increments('id')
       ->string('name', 100)
       ->string('email', 150)->unique()
       ->decimal('balance', 10, 2)->default(0.00)
       ->boolean('is_active')->default(true)
       ->timestamps()
       ->createTable('users');

$schema->increments('id')
       ->integer('user_id')->unsigned()
       ->decimal('amount', 10, 2)
       ->enum('type', ['credit', 'debit'])
       ->text('description')->nullable()
       ->timestamps()
       ->foreignKey('user_id', 'users', 'id')
       ->createTable('transactions');

echo "✅ Test tables created\n\n";

// ============================================================================
// 1. FUNCTION EXECUTION & MODIFICATION
// ============================================================================

echo "📊 1. Function Execution & Modification\n";
echo "--------------------------------------\n";

// Create a function
$functionBuilder = $objects->function('calculate_total')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->inParameter('tax_rate', 'DECIMAL(5,2)')
    ->returns('DECIMAL(10,2)')
    ->deterministic()
    ->comment('Calculate total with tax')
    ->body("
        DECLARE total DECIMAL(10,2);
        SET total = amount + (amount * tax_rate / 100);
        RETURN total;
    ");

$functionBuilder->create();
$function = $objects->function('calculate_total');

echo "✅ Function 'calculate_total' created\n";

// Execute the function
$result = $function->execute([100.00, 8.5]);
echo "💰 Function result: $" . number_format($result, 2) . "\n";

// Get function information
$info = $function->getInfo();
echo "📋 Function info: {$info['routine_name']} returns {$info['data_type']}\n";

// Get function parameters
$params = $function->getParameters();
echo "🔧 Parameters: " . count($params) . " parameters\n";

// Get function definition
$definition = $function->getDefinition();
echo "📝 Definition length: " . strlen($definition) . " characters\n";

// Modify function comment
$function->alterComment('Calculate total with tax (updated)');
echo "✅ Function comment updated\n";

// Rename function
$function->rename('calculate_total_with_tax');
echo "✅ Function renamed to 'calculate_total_with_tax'\n";

// Execute renamed function
$result = $objects->function('calculate_total_with_tax')->execute([200.00, 10.0]);
echo "💰 Renamed function result: $" . number_format($result, 2) . "\n\n";

// ============================================================================
// 2. PROCEDURE EXECUTION & MODIFICATION
// ============================================================================

echo "📋 2. Procedure Execution & Modification\n";
echo "----------------------------------------\n";

// Create a procedure
$procedure = $objects->procedure('process_payment')
    ->inParameter('user_id', 'INT')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->outParameter('transaction_id', 'INT')
    ->outParameter('new_balance', 'DECIMAL(10,2)')
    ->outParameter('success', 'BOOLEAN')
    ->modifiesSqlData()
    ->comment('Process a payment and update balance')
    ->body("
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            SET success = FALSE;
            SET transaction_id = NULL;
            SET new_balance = NULL;
        END;
        
        INSERT INTO transactions (user_id, amount, type, description) 
        VALUES (user_id, amount, 'debit', 'Payment processed');
        
        SET transaction_id = LAST_INSERT_ID();
        
        UPDATE users 
        SET balance = balance - amount 
        WHERE id = user_id;
        
        SELECT balance INTO new_balance FROM users WHERE id = user_id;
        SET success = TRUE;
    ")
    ->create();

echo "✅ Procedure 'process_payment' created\n";

// Execute the procedure
$procedure->call([1, 50.00, null, null, null]);
echo "✅ Procedure executed\n";

// Get procedure information
$info = $procedure->getInfo();
echo "📋 Procedure info: {$info['routine_name']} with {$info['parameter_count']} parameters\n";

// Get procedure definition
$definition = $procedure->getDefinition();
echo "📝 Definition length: " . strlen($definition) . " characters\n";

// Modify procedure comment
$procedure->alterComment('Process payment with balance update (enhanced)');
echo "✅ Procedure comment updated\n\n";

// ============================================================================
// 3. VIEW EXECUTION & MODIFICATION
// ============================================================================

echo "👁️  3. View Execution & Modification\n";
echo "------------------------------------\n";

// Create a view
$view = $objects->view('user_summary')
    ->select("
        u.id,
        u.name,
        u.email,
        u.balance,
        COUNT(t.id) as transaction_count,
        COALESCE(SUM(CASE WHEN t.type = 'credit' THEN t.amount ELSE 0 END), 0.00) as total_credits,
        COALESCE(SUM(CASE WHEN t.type = 'debit' THEN t.amount ELSE 0 END), 0.00) as total_debits
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    GROUP BY u.id, u.name, u.email, u.balance
    ")
    ->comment('User summary with transaction statistics')
    ->create();

echo "✅ View 'user_summary' created\n";

// Query the view
$result = $db->query("SELECT * FROM user_summary LIMIT 3");
$summaries = $result->fetchAll('assoc');
echo "📊 View query results: " . count($summaries) . " records\n";

// Get view definition
$definition = $view->getDefinition();
echo "📝 View definition length: " . strlen($definition) . " characters\n";

// Get view columns
$columns = $view->getColumns();
echo "🔧 View columns: " . count($columns) . " columns\n";

// Check if view is updatable
$isUpdatable = $view->isUpdatable();
echo "🔄 View updatable: " . ($isUpdatable ? 'Yes' : 'No') . "\n";

// Update the view
$view->select("
    u.id,
    u.name,
    u.email,
    u.balance,
    COUNT(t.id) as transaction_count,
    COALESCE(SUM(t.amount), 0.00) as total_amount
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.name, u.email, u.balance
")->update();

echo "✅ View updated with simplified query\n\n";

// ============================================================================
// 4. EVENT EXECUTION & MODIFICATION
// ============================================================================

echo "⏰ 4. Event Execution & Modification\n";
echo "-----------------------------------\n";

// Create an event
$event = $objects->event('update_user_stats')
    ->every('1 HOUR')
    ->body("
        UPDATE users u 
        SET balance = (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN type = 'credit' THEN amount 
                    WHEN type = 'debit' THEN -amount 
                    ELSE 0 
                END
            ), 0.00)
            FROM transactions 
            WHERE user_id = u.id
        )
        WHERE EXISTS (
            SELECT 1 
            FROM transactions 
            WHERE user_id = u.id
        );
    ")
    ->comment('Update user balances every hour')
    ->create();

echo "✅ Event 'update_user_stats' created\n";

// Get event information
$info = $event->getInfo();
echo "📋 Event info: {$info['event_name']} - {$info['interval_value']} {$info['interval_field']}\n";

// Get event schedule
$schedule = $event->getSchedule();
echo "📅 Event schedule: {$schedule}\n";

// Get event status
$status = $event->getStatus();
echo "🔄 Event status: {$status}\n";

// Execute event manually
$event->execute();
echo "✅ Event executed manually\n";

// Disable the event
$event->alterDisable();
echo "✅ Event disabled\n";

// Re-enable the event
$event->alterEnable();
echo "✅ Event re-enabled\n\n";

// ============================================================================
// 5. TRIGGER EXECUTION & MODIFICATION
// ============================================================================

echo "🔧 5. Trigger Execution & Modification\n";
echo "-------------------------------------\n";

// Create a trigger
$trigger = $objects->trigger('audit_user_changes')
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

echo "✅ Trigger 'audit_user_changes' created\n";

// Get trigger information
$info = $trigger->getInfo();
echo "📋 Trigger info: {$info['trigger_name']} - {$info['action_timing']} {$info['event_manipulation']}\n";

// Get trigger timing and event
$timing = $trigger->getTiming();
$event = $trigger->getEvent();
$table = $trigger->getTable();
echo "🔧 Trigger: {$timing} {$event} ON {$table}\n";

// Check if trigger is enabled
$isEnabled = $trigger->isEnabled();
echo "🔄 Trigger enabled: " . ($isEnabled ? 'Yes' : 'No') . "\n";

// Test the trigger
$db->query("UPDATE users SET balance = balance + 10 WHERE id = 1");
echo "✅ Trigger tested with user update\n";

// Get all triggers for users table
$tableTriggers = $objects->trigger('')->getTableTriggers($db, 'users');
echo "🔧 Table triggers: " . count($tableTriggers) . " triggers on users table\n\n";

// ============================================================================
// 6. COMPREHENSIVE EXECUTION DEMO
// ============================================================================

echo "🎯 6. Comprehensive Execution Demo\n";
echo "---------------------------------\n";

// Insert test data
$db->query("INSERT INTO users (name, email, balance) VALUES 
    ('Alice Johnson', 'alice@example.com', 1000.00),
    ('Bob Smith', 'bob@example.com', 500.00)");

echo "✅ Test data inserted\n";

// Execute function with different parameters
$function = $objects->function('calculate_total_with_tax');
$results = [];
for ($i = 1; $i <= 5; $i++) {
    $amount = $i * 100;
    $tax = $i * 2;
    $result = $function->execute([$amount, $tax]);
    $results[] = "Amount: \${$amount}, Tax: {$tax}%, Total: \${$result}";
}

echo "💰 Function execution results:\n";
foreach ($results as $result) {
    echo "  - {$result}\n";
}

// Execute procedure multiple times
$procedure = $objects->procedure('process_payment');
for ($i = 1; $i <= 3; $i++) {
    $procedure->call([1, 25.00, null, null, null]);
    echo "  - Payment {$i} processed\n";
}

// Query updated view
$result = $db->query("SELECT name, balance, transaction_count FROM user_summary WHERE id = 1");
$user = $result->fetch('assoc');
echo "📊 Updated user summary: {$user['name']} - Balance: \${$user['balance']}, Transactions: {$user['transaction_count']}\n";

// ============================================================================
// 7. BULK OPERATIONS
// ============================================================================

echo "\n🔄 7. Bulk Operations\n";
echo "--------------------\n";

// Get all objects
$allObjects = $objects->getAllObjects();
$counts = $objects->getObjectCounts();

echo "📊 Database object counts:\n";
foreach ($counts as $type => $count) {
    echo "  - {$type}: {$count}\n";
}

// Get detailed information for each object type
foreach ($allObjects as $type => $objectList) {
    if (!empty($objectList)) {
        echo "\n📋 {$type} details:\n";
        foreach ($objectList as $object) {
            $name = $object['routine_name'] ?? $object['table_name'] ?? $object['event_name'] ?? $object['trigger_name'];
            echo "  - {$name}\n";
        }
    }
}

// ============================================================================
// 8. CLEANUP
// ============================================================================

echo "\n🧹 8. Cleanup\n";
echo "-------------\n";

// Drop all objects
$objects->dropAllTriggers();
$objects->dropAllViews();
$objects->dropAllEvents();
$objects->dropAllProcedures();
$objects->dropAllFunctions();

echo "✅ All database objects cleaned up\n";

// Drop test tables
$db->query("DROP TABLE IF EXISTS transactions");
$db->query("DROP TABLE IF EXISTS users");

echo "✅ Test tables cleaned up\n";

echo "\n🎉 Execution & Modification Demo Complete!\n";
echo "SimpleMDB now supports comprehensive database object management:\n";
echo "  ✅ Function execution and modification\n";
echo "  ✅ Procedure execution with OUT parameters\n";
echo "  ✅ View querying and updating\n";
echo "  ✅ Event scheduling and manual execution\n";
echo "  ✅ Trigger testing and management\n";
echo "  ✅ Bulk operations and information retrieval\n";
echo "  ✅ Interface-based architecture\n";
echo "  ✅ Complete lifecycle management\n"; 