# Database Objects Management

SimpleMDB provides comprehensive support for creating, managing, and executing database objects including functions, procedures, views, events, and triggers.

## Table of Contents

- [Functions](#functions)
- [Procedures](#procedures)
- [Views](#views)
- [Events](#events)
- [Triggers](#triggers)
- [Unified Management](#unified-management)
- [Execution & Modification](#execution--modification)
- [Real-World Examples](#real-world-examples)
- [Best Practices](#best-practices)

## Functions

### Creating Functions

```php
use SimpleMDB\DatabaseObjects\DatabaseObjectManager;

$objects = new DatabaseObjectManager($db);

// Create a simple function
$function = $objects->function('calculate_tax')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->inParameter('rate', 'DECIMAL(5,2)')
    ->returns('DECIMAL(10,2)')
    ->deterministic()
    ->comment('Calculate tax amount')
    ->body("
        DECLARE tax DECIMAL(10,2);
        SET tax = amount * rate / 100;
        RETURN tax;
    ")
    ->create();
```

### Executing Functions

```php
// Execute function with parameters
$tax = $function->execute([100.00, 8.5]);
echo "Tax: $" . number_format($tax, 2);

// Alternative call method
$tax = $function->call([100.00, 8.5]);

// Get function information
$info = $function->getInfo();
$params = $function->getParameters();
$returnType = $function->getReturnType();
$definition = $function->getDefinition();
```

### Modifying Functions

```php
// Rename function
$function->rename('calculate_tax_amount');

// Modify characteristics
$function->alterDeterministic();
$function->alterNotDeterministic();
$function->alterComment('Updated tax calculation function');
```

## Procedures

### Creating Procedures

```php
// Create a procedure with OUT parameters
$procedure = $objects->procedure('process_payment')
    ->inParameter('user_id', 'INT')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->outParameter('transaction_id', 'INT')
    ->outParameter('new_balance', 'DECIMAL(10,2)')
    ->outParameter('success', 'BOOLEAN')
    ->modifiesSqlData()
    ->comment('Process payment and update balance')
    ->body("
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            SET success = FALSE;
            ROLLBACK;
        END;
        
        START TRANSACTION;
        
        INSERT INTO transactions (user_id, amount, type) 
        VALUES (user_id, amount, 'debit');
        
        SET transaction_id = LAST_INSERT_ID();
        
        UPDATE users 
        SET balance = balance - amount 
        WHERE id = user_id;
        
        SELECT balance INTO new_balance FROM users WHERE id = user_id;
        SET success = TRUE;
        
        COMMIT;
    ")
    ->create();
```

### Executing Procedures

```php
// Call procedure with parameters
$procedure->call([1, 50.00, null, null, null]);

// Get procedure results (OUT parameters)
$results = $procedure->getResults();

// Get procedure information
$info = $procedure->getInfo();
$params = $procedure->getParameters();
$definition = $procedure->getDefinition();
```

### Modifying Procedures

```php
// Rename procedure
$procedure->rename('process_user_payment');

// Modify characteristics
$procedure->alterDeterministic();
$procedure->alterComment('Enhanced payment processing');
```

## Views

### Creating Views

```php
// Create a view
$view = $objects->view('user_summary')
    ->select("
        u.id,
        u.name,
        u.email,
        u.balance,
        COUNT(t.id) as transaction_count,
        COALESCE(SUM(t.amount), 0.00) as total_amount
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    GROUP BY u.id, u.name, u.email, u.balance
    ")
    ->comment('User summary with transaction statistics')
    ->create();
```

### Querying Views

```php
// Query the view
$result = $db->query("SELECT * FROM user_summary WHERE balance > 100");

// Use view's query method
$data = $view->query("SELECT * FROM user_summary LIMIT 10");

// Get view information
$info = $view->getInfo();
$columns = $view->getColumns();
$isUpdatable = $view->isUpdatable();
$definition = $view->getDefinition();
```

### Modifying Views

```php
// Update view definition
$view->select("
    u.id,
    u.name,
    u.balance,
    COUNT(t.id) as transaction_count
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.name, u.balance
")->update();

// Rename view
$view->rename('user_transaction_summary');

// Alter view properties
$view->alterAlgorithm('MERGE');
$view->alterDefiner('app_user@localhost');
```

## Events

### Creating Events

```php
// Create a recurring event
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
            SELECT 1 FROM transactions WHERE user_id = u.id
        );
    ")
    ->comment('Update user balances every hour')
    ->create();

// Create a one-time event
$oneTimeEvent = $objects->event('cleanup_old_data')
    ->at('2024-01-01 00:00:00')
    ->body("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);")
    ->create();
```

### Managing Events

```php
// Execute event manually
$event->execute();

// Get event information
$info = $event->getInfo();
$schedule = $event->getSchedule();
$status = $event->getStatus();
$nextExecution = $event->getNextExecution();
$lastExecution = $event->getLastExecution();

// Modify event
$event->alterEnable();
$event->alterDisable();
$event->alterSchedule('EVERY 30 MINUTE');
$event->alterBody('NEW BODY HERE');
$event->rename('update_stats_hourly');
```

## Triggers

### Creating Triggers

```php
// Create an audit trigger
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
            JSON_OBJECT('name', OLD.name, 'email', OLD.email),
            JSON_OBJECT('name', NEW.name, 'email', NEW.email)
        );
    ")
    ->create();

// Create a validation trigger
$validationTrigger = $objects->trigger('validate_transaction')
    ->before()
    ->insert()
    ->on('transactions')
    ->body("
        IF NEW.amount <= 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaction amount must be positive';
        END IF;
    ")
    ->create();
```

### Managing Triggers

```php
// Get trigger information
$info = $trigger->getInfo();
$timing = $trigger->getTiming();
$event = $trigger->getEvent();
$table = $trigger->getTable();
$isEnabled = $trigger->isEnabled();

// Enable/disable triggers
$trigger->enable();
$trigger->disable();

// Test trigger
$trigger->test(['test_data' => 'value']);

// Get all triggers for a table
$tableTriggers = $objects->trigger('')->getTableTriggers($db, 'users');

// Modify trigger
$trigger->alterDefiner('app_user@localhost');
$trigger->alterComment('Enhanced audit logging');
$trigger->rename('audit_user_updates');
```

## Unified Management

### DatabaseObjectManager

```php
$objects = new DatabaseObjectManager($db);

// Factory methods
$function = $objects->function('my_function');
$procedure = $objects->procedure('my_procedure');
$view = $objects->view('my_view');
$event = $objects->event('my_event');
$trigger = $objects->trigger('my_trigger');

// Bulk operations
$allObjects = $objects->getAllObjects();
$counts = $objects->getObjectCounts();

// List specific objects
$functions = $objects->listFunctions();
$procedures = $objects->listProcedures();
$views = $objects->listViews();
$events = $objects->listEvents();
$triggers = $objects->listTriggers();

// Drop all objects
$objects->dropAllFunctions();
$objects->dropAllProcedures();
$objects->dropAllViews();
$objects->dropAllEvents();
$objects->dropAllTriggers();
```

## Execution & Modification

### Function Execution

```php
// Execute with parameters
$result = $function->execute([100.00, 8.5]);

// Get function definition
$definition = $function->getDefinition();

// Modify function characteristics
$function->alterDeterministic();
$function->alterComment('Updated function');

// Rename function
$function->rename('new_function_name');
```

### Procedure Execution

```php
// Call with IN parameters
$procedure->call([1, 50.00, null, null, null]);

// Get OUT parameter results
$results = $procedure->getResults();

// Get procedure definition
$definition = $procedure->getDefinition();

// Modify procedure
$procedure->alterComment('Enhanced procedure');
$procedure->rename('new_procedure_name');
```

### View Operations

```php
// Query view
$data = $view->query("SELECT * FROM user_summary");

// Update view definition
$view->select("NEW SELECT QUERY")->update();

// Get view information
$columns = $view->getColumns();
$isUpdatable = $view->isUpdatable();

// Rename view
$view->rename('new_view_name');
```

### Event Management

```php
// Execute event manually
$event->execute();

// Get event schedule
$schedule = $event->getSchedule();
$status = $event->getStatus();

// Modify event
$event->alterEnable();
$event->alterSchedule('EVERY 30 MINUTE');
$event->rename('new_event_name');
```

### Trigger Management

```php
// Test trigger
$trigger->test(['test_data' => 'value']);

// Get trigger information
$timing = $trigger->getTiming();
$event = $trigger->getEvent();
$table = $trigger->getTable();

// Enable/disable trigger
$trigger->enable();
$trigger->disable();

// Get table triggers
$tableTriggers = $objects->trigger('')->getTableTriggers($db, 'users');
```

## Real-World Examples

### E-commerce System

```php
// Create inventory management function
$objects->function('check_stock')
    ->inParameter('product_id', 'INT')
    ->returns('BOOLEAN')
    ->body("
        DECLARE available_stock INT;
        SELECT quantity INTO available_stock 
        FROM inventory 
        WHERE product_id = product_id;
        RETURN available_stock > 0;
    ")
    ->create();

// Create order processing procedure
$objects->procedure('process_order')
    ->inParameter('user_id', 'INT')
    ->inParameter('product_id', 'INT')
    ->inParameter('quantity', 'INT')
    ->outParameter('order_id', 'INT')
    ->outParameter('success', 'BOOLEAN')
    ->body("
        DECLARE stock_available BOOLEAN;
        SET stock_available = check_stock(product_id);
        
        IF stock_available THEN
            INSERT INTO orders (user_id, product_id, quantity) 
            VALUES (user_id, product_id, quantity);
            SET order_id = LAST_INSERT_ID();
            SET success = TRUE;
        ELSE
            SET order_id = NULL;
            SET success = FALSE;
        END IF;
    ")
    ->create();

// Create order summary view
$objects->view('order_summary')
    ->select("
        o.id,
        u.name as customer_name,
        p.name as product_name,
        o.quantity,
        o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN products p ON o.product_id = p.id
    ")
    ->create();

// Create inventory update trigger
$objects->trigger('update_inventory_on_order')
    ->after()
    ->insert()
    ->on('orders')
    ->body("
        UPDATE inventory 
        SET quantity = quantity - NEW.quantity 
        WHERE product_id = NEW.product_id;
    ")
    ->create();

// Create daily inventory check event
$objects->event('daily_inventory_check')
    ->every('1 DAY')
    ->body("
        INSERT INTO inventory_alerts (product_id, message)
        SELECT product_id, 'Low stock alert'
        FROM inventory 
        WHERE quantity < 10;
    ")
    ->create();
```

### Banking System

```php
// Create balance calculation function
$objects->function('calculate_balance')
    ->inParameter('account_id', 'INT')
    ->returns('DECIMAL(15,2)')
    ->body("
        DECLARE balance DECIMAL(15,2);
        SELECT COALESCE(SUM(
            CASE 
                WHEN type = 'credit' THEN amount 
                WHEN type = 'debit' THEN -amount 
                ELSE 0 
            END
        ), 0.00) INTO balance
        FROM transactions 
        WHERE account_id = account_id;
        RETURN balance;
    ")
    ->create();

// Create transaction processing procedure
$objects->procedure('process_transaction')
    ->inParameter('from_account', 'INT')
    ->inParameter('to_account', 'INT')
    ->inParameter('amount', 'DECIMAL(15,2)')
    ->outParameter('transaction_id', 'INT')
    ->outParameter('success', 'BOOLEAN')
    ->body("
        DECLARE from_balance DECIMAL(15,2);
        DECLARE to_balance DECIMAL(15,2);
        
        START TRANSACTION;
        
        SELECT calculate_balance(from_account) INTO from_balance;
        
        IF from_balance >= amount THEN
            INSERT INTO transactions (account_id, amount, type, description)
            VALUES (from_account, amount, 'debit', 'Transfer out'),
                   (to_account, amount, 'credit', 'Transfer in');
            
            SET transaction_id = LAST_INSERT_ID();
            SET success = TRUE;
            COMMIT;
        ELSE
            SET transaction_id = NULL;
            SET success = FALSE;
            ROLLBACK;
        END IF;
    ")
    ->create();

// Create account summary view
$objects->view('account_summary')
    ->select("
        a.id,
        a.account_number,
        u.name as account_holder,
        calculate_balance(a.id) as current_balance,
        COUNT(t.id) as transaction_count
    FROM accounts a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN transactions t ON a.id = t.account_id
    GROUP BY a.id, a.account_number, u.name
    ")
    ->create();

// Create transaction audit trigger
$objects->trigger('audit_transaction')
    ->after()
    ->insert()
    ->on('transactions')
    ->body("
        INSERT INTO transaction_audit (
            transaction_id, account_id, amount, type, 
            old_balance, new_balance, timestamp
        )
        VALUES (
            NEW.id, NEW.account_id, NEW.amount, NEW.type,
            calculate_balance(NEW.account_id) - NEW.amount,
            calculate_balance(NEW.account_id),
            NOW()
        );
    ")
    ->create();
```

## Best Practices

### 1. Naming Conventions

```php
// Use descriptive names
$objects->function('calculate_monthly_revenue');
$objects->procedure('process_user_registration');
$objects->view('active_user_summary');
$objects->event('daily_data_cleanup');
$objects->trigger('audit_user_changes');
```

### 2. Error Handling

```php
// Always include error handling in procedures
$objects->procedure('safe_operation')
    ->body("
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            RESIGNAL;
        END;
        
        START TRANSACTION;
        -- Your operations here
        COMMIT;
    ")
    ->create();
```

### 3. Performance Considerations

```php
// Use appropriate characteristics
$objects->function('fast_calculation')
    ->deterministic()  // For functions that always return same result
    ->noSql()         // For functions that don't access data
    ->create();

// Use efficient view algorithms
$objects->view('optimized_view')
    ->algorithm('MERGE')  // For simple views
    ->create();
```

### 4. Security

```php
// Use appropriate security contexts
$objects->function('secure_function')
    ->invoker()  // Run with caller's privileges
    ->create();

$objects->procedure('admin_procedure')
    ->definer('admin@localhost')  // Run with definer's privileges
    ->create();
```

### 5. Documentation

```php
// Always add comments
$objects->function('complex_calculation')
    ->comment('Calculates complex business logic with multiple steps')
    ->create();

$objects->procedure('data_migration')
    ->comment('Migrates data from old format to new format')
    ->create();
```

### 6. Testing

```php
// Test functions with various inputs
$result1 = $function->execute([100, 5]);
$result2 = $function->execute([0, 0]);
$result3 = $function->execute([1000, 25]);

// Test procedures with different scenarios
$procedure->call([1, 50, null, null, null]);
$procedure->call([999, 1000, null, null, null]); // Test edge case

// Test triggers
$trigger->test(['test_data' => 'value']);
```

### 7. Maintenance

```php
// Regular cleanup of unused objects
$objects->dropAllFunctions();
$objects->dropAllProcedures();

// Update object definitions as needed
$function->alterComment('Updated for new business rules');
$view->select("NEW QUERY")->update();
```

This comprehensive database objects management system provides everything you need to build complex, enterprise-grade applications with SimpleMDB. 