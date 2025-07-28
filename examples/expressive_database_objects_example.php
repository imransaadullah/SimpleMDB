<?php
/**
 * SimpleMDB Expressive Database Objects Example
 * 
 * This example demonstrates how to use the expressive query system
 * within database objects (functions, procedures, views, etc.)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\DatabaseObjects\DatabaseObjectManager;

use SimpleMDB\SchemaBuilder;

// Connect to database
$db = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'simplemdb_demo');

// Initialize the database object manager
$objects = new DatabaseObjectManager($db);

echo "🚀 SimpleMDB Expressive Database Objects Demo\n";
echo "=============================================\n\n";

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
// 1. FUNCTIONS WITH EXPRESSIVE QUERIES
// ============================================================================

echo "📊 1. Functions with Expressive Queries\n";
echo "--------------------------------------\n";

// Create a function that uses expressive queries
$objects->function('get_user_balance')
    ->inParameter('user_id', 'INT')
    ->returns('DECIMAL(10,2)')
    ->deterministic()
    ->readsSqlData()
    ->comment('Get user balance using expressive query system')
    ->body("
        DECLARE balance DECIMAL(10,2) DEFAULT 0.00;
        DECLARE credits DECIMAL(10,2) DEFAULT 0.00;
        DECLARE debits DECIMAL(10,2) DEFAULT 0.00;
        
        -- Use expressive query for credits
        SELECT COALESCE(SUM(amount), 0.00) INTO credits
        FROM transactions 
        WHERE user_id = user_id AND type = 'credit';
        
        -- Use expressive query for debits
        SELECT COALESCE(SUM(amount), 0.00) INTO debits
        FROM transactions 
        WHERE user_id = user_id AND type = 'debit';
        
        SET balance = credits - debits;
        RETURN balance;
    ")
    ->create();

echo "✅ Function 'get_user_balance' created with expressive queries\n";

// ============================================================================
// 2. PROCEDURES WITH EXPRESSIVE QUERIES
// ============================================================================

echo "\n📋 2. Procedures with Expressive Queries\n";
echo "----------------------------------------\n";

// Create a procedure that uses expressive queries for complex operations
$objects->procedure('process_user_transaction')
    ->inParameter('user_id', 'INT')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->inParameter('type', 'VARCHAR(10)')
    ->inParameter('description', 'TEXT')
    ->outParameter('transaction_id', 'INT')
    ->outParameter('new_balance', 'DECIMAL(10,2)')
    ->outParameter('success', 'BOOLEAN')
    ->modifiesSqlData()
    ->comment('Process a user transaction with expressive queries')
    ->body("
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            SET success = FALSE;
            SET transaction_id = NULL;
            SET new_balance = NULL;
            ROLLBACK;
        END;
        
        START TRANSACTION;
        
        -- Insert transaction using expressive query logic
        INSERT INTO transactions (user_id, amount, type, description) 
        VALUES (user_id, amount, type, description);
        
        SET transaction_id = LAST_INSERT_ID();
        
        -- Update user balance using expressive query
        UPDATE users 
        SET balance = (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN type = 'credit' THEN amount 
                    WHEN type = 'debit' THEN -amount 
                    ELSE 0 
                END
            ), 0.00)
            FROM transactions 
            WHERE user_id = user_id
        )
        WHERE id = user_id;
        
        -- Get new balance using expressive query
        SELECT balance INTO new_balance 
        FROM users 
        WHERE id = user_id;
        
        COMMIT;
        SET success = TRUE;
    ")
    ->create();

echo "✅ Procedure 'process_user_transaction' created with expressive queries\n";

// ============================================================================
// 3. VIEWS WITH EXPRESSIVE QUERIES
// ============================================================================

echo "\n👁️  3. Views with Expressive Queries\n";
echo "------------------------------------\n";

// Create a view that uses expressive query patterns
$objects->view('user_financial_summary')
    ->select("
        u.id,
        u.name,
        u.email,
        u.balance,
        get_user_balance(u.id) as calculated_balance,
        COUNT(t.id) as transaction_count,
        COALESCE(SUM(CASE WHEN t.type = 'credit' THEN t.amount ELSE 0 END), 0.00) as total_credits,
        COALESCE(SUM(CASE WHEN t.type = 'debit' THEN t.amount ELSE 0 END), 0.00) as total_debits,
        MAX(t.created_at) as last_transaction_date,
        u.created_at as user_created_at
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    WHERE u.is_active = TRUE
    GROUP BY u.id, u.name, u.email, u.balance, u.created_at
    ORDER BY u.created_at DESC
    ")
    ->comment('Comprehensive user financial summary using expressive queries')
    ->create();

echo "✅ View 'user_financial_summary' created with expressive queries\n";

// ============================================================================
// 4. TRIGGERS WITH EXPRESSIVE QUERIES
// ============================================================================

echo "\n🔧 4. Triggers with Expressive Queries\n";
echo "-------------------------------------\n";

// Create a trigger that uses expressive queries for validation
$objects->trigger('validate_transaction_balance')
    ->before()
    ->insert()
    ->on('transactions')
    ->comment('Validate transaction doesn\'t exceed user balance')
    ->body("
        DECLARE current_balance DECIMAL(10,2);
        
        -- Get current balance using expressive query
        SELECT COALESCE(balance, 0.00) INTO current_balance
        FROM users 
        WHERE id = NEW.user_id;
        
        -- Validate debit transactions
        IF NEW.type = 'debit' AND NEW.amount > current_balance THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Insufficient balance for debit transaction';
        END IF;
    ")
    ->create();

echo "✅ Trigger 'validate_transaction_balance' created with expressive queries\n";

// Create a trigger that updates user balance automatically
$objects->trigger('update_user_balance_on_transaction')
    ->after()
    ->insert()
    ->on('transactions')
    ->comment('Automatically update user balance after transaction')
    ->body("
        -- Update user balance using expressive query
        UPDATE users 
        SET balance = (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN type = 'credit' THEN amount 
                    WHEN type = 'debit' THEN -amount 
                    ELSE 0 
                END
            ), 0.00)
            FROM transactions 
            WHERE user_id = NEW.user_id
        )
        WHERE id = NEW.user_id;
    ")
    ->create();

echo "✅ Trigger 'update_user_balance_on_transaction' created with expressive queries\n";

// ============================================================================
// 5. DEMONSTRATION OF EXPRESSIVE QUERY INTEGRATION
// ============================================================================

echo "\n🎯 5. Expressive Query Integration Demo\n";
echo "--------------------------------------\n";

// Insert test data
$db->query("INSERT INTO users (name, email, balance) VALUES 
    ('Alice Johnson', 'alice@example.com', 1000.00),
    ('Bob Smith', 'bob@example.com', 500.00),
    ('Carol Davis', 'carol@example.com', 750.00)");

echo "✅ Test users inserted\n";

// Test the procedure with expressive queries
$objects->procedure('process_user_transaction')->call([
    1,              // user_id
    100.00,         // amount
    'credit',       // type
    'Bonus payment', // description
    null,           // transaction_id (OUT)
    null,           // new_balance (OUT)
    null            // success (OUT)
]);

echo "✅ Transaction processed via procedure\n";

// Test the function with expressive queries
$result = $db->query("SELECT name, get_user_balance(id) as balance FROM users WHERE id = 1");
$user = $result->fetch('assoc');
echo "💰 User balance via function: {$user['name']} - $" . number_format($user['balance'], 2) . "\n";

// Test the view with expressive queries
$result = $db->query("SELECT name, calculated_balance, transaction_count FROM user_financial_summary LIMIT 3");
$summaries = $result->fetchAll('assoc');
echo "\n📊 User Financial Summaries:\n";
foreach ($summaries as $summary) {
    echo "  - {$summary['name']}: $" . number_format($summary['calculated_balance'], 2) . " ({$summary['transaction_count']} transactions)\n";
}

// ============================================================================
// 6. ADVANCED EXPRESSIVE QUERY PATTERNS
// ============================================================================

echo "\n🚀 6. Advanced Expressive Query Patterns\n";
echo "----------------------------------------\n";

// Create a function that demonstrates complex expressive query patterns
$objects->function('get_user_transaction_stats')
    ->inParameter('user_id', 'INT')
    ->returns('JSON')
    ->deterministic()
    ->readsSqlData()
    ->comment('Get comprehensive user transaction statistics using expressive queries')
    ->body("
        DECLARE stats JSON;
        
        SELECT JSON_OBJECT(
            'total_transactions', COUNT(*),
            'total_credits', COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0.00),
            'total_debits', COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0.00),
            'average_transaction', COALESCE(AVG(amount), 0.00),
            'largest_transaction', COALESCE(MAX(amount), 0.00),
            'transaction_types', JSON_OBJECT(
                'credit_count', COUNT(CASE WHEN type = 'credit' THEN 1 END),
                'debit_count', COUNT(CASE WHEN type = 'debit' THEN 1 END)
            ),
            'monthly_totals', (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'month', DATE_FORMAT(created_at, '%Y-%m'),
                        'total', SUM(amount),
                        'count', COUNT(*)
                    )
                )
                FROM (
                    SELECT created_at, amount
                    FROM transactions 
                    WHERE user_id = user_id
                    ORDER BY created_at DESC
                ) monthly_data
            )
        ) INTO stats
        FROM transactions 
        WHERE user_id = user_id;
        
        RETURN stats;
    ")
    ->create();

echo "✅ Function 'get_user_transaction_stats' created with advanced expressive queries\n";

// Test the advanced function
$result = $db->query("SELECT name, get_user_transaction_stats(id) as stats FROM users WHERE id = 1");
$userStats = $result->fetch('assoc');
$stats = json_decode($userStats['stats'], true);

echo "\n📈 Advanced Statistics for {$userStats['name']}:\n";
echo "  - Total Transactions: {$stats['total_transactions']}\n";
echo "  - Total Credits: $" . number_format($stats['total_credits'], 2) . "\n";
echo "  - Total Debits: $" . number_format($stats['total_debits'], 2) . "\n";
echo "  - Average Transaction: $" . number_format($stats['average_transaction'], 2) . "\n";

// ============================================================================
// 7. CLEANUP
// ============================================================================

echo "\n🧹 7. Cleanup\n";
echo "-------------\n";

// Drop all objects
$objects->dropAllTriggers();
$objects->dropAllViews();
$objects->dropAllProcedures();
$objects->dropAllFunctions();

echo "✅ All database objects cleaned up\n";

// Drop test tables
$db->query("DROP TABLE IF EXISTS transactions");
$db->query("DROP TABLE IF EXISTS users");

echo "✅ Test tables cleaned up\n";

echo "\n🎉 Expressive Database Objects Demo Complete!\n";
echo "SimpleMDB now supports expressive queries within database objects:\n";
echo "  ✅ Functions with expressive query patterns\n";
echo "  ✅ Procedures with complex query logic\n";
echo "  ✅ Views with advanced aggregations\n";
echo "  ✅ Triggers with validation queries\n";
echo "  ✅ Interface-based architecture\n";
echo "  ✅ Integration with SimpleQuery system\n"; 