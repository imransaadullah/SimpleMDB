# Interface-Based Architecture

SimpleMDB follows a **interface-first** design philosophy, ensuring flexibility, testability, and maintainability. This document explains the architecture and how database objects integrate with the expressive query system.

## 🏗️ Architecture Overview

### Interface Hierarchy

```
DatabaseObjectInterface (Base)
├── FunctionInterface
├── ProcedureInterface
├── ViewInterface
├── EventInterface
└── TriggerInterface
```

### Core Principles

1. **Interface-First Design**: All database objects implement interfaces
2. **Expressive Query Integration**: Database objects can use SimpleQuery patterns
3. **Dependency Injection**: Database connections are injected
4. **Fluent Interfaces**: Method chaining for expressive code
5. **Comprehensive Validation**: Type checking and error handling

---

## 📊 Function Architecture

### Interface Definition

```php
interface FunctionInterface extends DatabaseObjectInterface
{
    public function parameter(string $name, string $type, bool $isOut = false): self;
    public function inParameter(string $name, string $type): self;
    public function outParameter(string $name, string $type): self;
    public function returns(string $type): self;
    public function body(string $body): self;
    public function deterministic(): self;
    public function notDeterministic(): self;
    public function containsSql(): self;
    public function noSql(): self;
    public function readsSqlData(): self;
    public function modifiesSqlData(): self;
    public function definer(string $definer = ''): self;
    public function invoker(): self;
    public function comment(string $comment): self;
    public function ifNotExists(): self;
}
```

### Implementation with Expressive Queries

```php
class FunctionBuilder implements FunctionInterface
{
    private DatabaseInterface $db;
    private string $functionName;
    private array $parameters = [];
    private string $returnType = '';
    private string $body = '';
    // ... other properties

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function create(): bool
    {
        $sql = $this->generateCreateSql();
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to create function '{$this->functionName}': " . $e->getMessage());
        }
    }
}
```

### Expressive Query Integration

```php
// Function that uses expressive query patterns
$objects->function('get_user_balance')
    ->inParameter('user_id', 'INT')
    ->returns('DECIMAL(10,2)')
    ->deterministic()
    ->readsSqlData()
    ->body("
        DECLARE balance DECIMAL(10,2) DEFAULT 0.00;
        
        -- Expressive query pattern: SELECT with aggregation
        SELECT COALESCE(SUM(
            CASE 
                WHEN type = 'credit' THEN amount 
                WHEN type = 'debit' THEN -amount 
                ELSE 0 
            END
        ), 0.00) INTO balance
        FROM transactions 
        WHERE user_id = user_id;
        
        RETURN balance;
    ")
    ->create();
```

---

## 📋 Procedure Architecture

### Interface Definition

```php
interface ProcedureInterface extends DatabaseObjectInterface
{
    public function parameter(string $name, string $type, string $direction = 'IN'): self;
    public function inParameter(string $name, string $type): self;
    public function outParameter(string $name, string $type): self;
    public function inoutParameter(string $name, string $type): self;
    public function body(string $body): self;
    public function deterministic(): self;
    public function notDeterministic(): self;
    public function containsSql(): self;
    public function noSql(): self;
    public function readsSqlData(): self;
    public function modifiesSqlData(): self;
    public function definer(string $definer = ''): self;
    public function invoker(): self;
    public function comment(string $comment): self;
    public function ifNotExists(): self;
    public function call(array $parameters = []): mixed;
}
```

### Complex Procedure with Expressive Queries

```php
$objects->procedure('process_user_transaction')
    ->inParameter('user_id', 'INT')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->inParameter('type', 'VARCHAR(10)')
    ->outParameter('transaction_id', 'INT')
    ->outParameter('new_balance', 'DECIMAL(10,2)')
    ->outParameter('success', 'BOOLEAN')
    ->modifiesSqlData()
    ->body("
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            SET success = FALSE;
            SET transaction_id = NULL;
            SET new_balance = NULL;
            ROLLBACK;
        END;
        
        START TRANSACTION;
        
        -- Expressive INSERT pattern
        INSERT INTO transactions (user_id, amount, type, description) 
        VALUES (user_id, amount, type, description);
        
        SET transaction_id = LAST_INSERT_ID();
        
        -- Expressive UPDATE with subquery
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
        
        -- Expressive SELECT for result
        SELECT balance INTO new_balance 
        FROM users 
        WHERE id = user_id;
        
        COMMIT;
        SET success = TRUE;
    ")
    ->create();
```

---

## 👁️ View Architecture

### Interface Definition

```php
interface ViewInterface extends DatabaseObjectInterface
{
    public function select(string $query): self;
    public function columns(array $columns): self;
    public function algorithm(string $algorithm): self;
    public function undefined(): self;
    public function merge(): self;
    public function temptable(): self;
    public function definer(string $definer = ''): self;
    public function invoker(): self;
    public function comment(string $comment): self;
    public function ifNotExists(): self;
    public function orReplace(): self;
    public function getDefinition(): ?string;
    public function update(): bool;
}
```

### View with Expressive Queries

```php
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
        MAX(t.created_at) as last_transaction_date
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    WHERE u.is_active = TRUE
    GROUP BY u.id, u.name, u.email, u.balance
    ORDER BY u.created_at DESC
    ")
    ->comment('Comprehensive user financial summary using expressive queries')
    ->create();
```

---

## 🔧 Trigger Architecture

### Interface Definition

```php
interface TriggerInterface extends DatabaseObjectInterface
{
    public function before(): self;
    public function after(): self;
    public function insert(): self;
    public function update(): self;
    public function delete(): self;
    public function on(string $table): self;
    public function body(string $body): self;
    public function definer(string $definer): self;
    public function comment(string $comment): self;
    public function ifNotExists(): self;
    public function orReplace(): self;
    public function getInfo(): ?array;
    public static function getTableTriggers(DatabaseInterface $db, string $tableName): array;
}
```

### Trigger with Expressive Queries

```php
$objects->trigger('validate_transaction_balance')
    ->before()
    ->insert()
    ->on('transactions')
    ->comment('Validate transaction doesn\'t exceed user balance')
    ->body("
        DECLARE current_balance DECIMAL(10,2);
        
        -- Expressive SELECT with COALESCE
        SELECT COALESCE(balance, 0.00) INTO current_balance
        FROM users 
        WHERE id = NEW.user_id;
        
        -- Validation logic
        IF NEW.type = 'debit' AND NEW.amount > current_balance THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Insufficient balance for debit transaction';
        END IF;
    ")
    ->create();
```

---

## ⏰ Event Architecture

### Interface Definition

```php
interface EventInterface extends DatabaseObjectInterface
{
    public function schedule(string $schedule): self;
    public function at(string $datetime): self;
    public function every(string $interval): self;
    public function everyStarting(string $interval, string $startTime): self;
    public function everyEnding(string $interval, string $endTime): self;
    public function everyBetween(string $interval, string $startTime, string $endTime): self;
    public function body(string $body): self;
    public function enable(): self;
    public function disable(): self;
    public function disableOnSlave(): self;
    public function comment(string $comment): self;
    public function ifNotExists(): self;
    public function orReplace(): self;
    public function alterStatus(string $status): bool;
    public function alterEnable(): bool;
    public function alterDisable(): bool;
    public function getInfo(): ?array;
}
```

### Event with Expressive Queries

```php
$objects->event('update_user_statistics')
    ->every('1 HOUR')
    ->body("
        -- Expressive UPDATE with complex subquery
        UPDATE users u 
        SET 
            total_transactions = (
                SELECT COUNT(*) 
                FROM transactions 
                WHERE user_id = u.id
            ),
            total_spent = (
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN type = 'debit' THEN amount 
                        ELSE 0 
                    END
                ), 0.00)
                FROM transactions 
                WHERE user_id = u.id
            ),
            last_activity = (
                SELECT MAX(created_at) 
                FROM transactions 
                WHERE user_id = u.id
            )
        WHERE EXISTS (
            SELECT 1 
            FROM transactions 
            WHERE user_id = u.id
        );
    ")
    ->comment('Update user statistics every hour')
    ->create();
```

---

## 🎯 Expressive Query Integration

### Using SimpleQuery to Build SQL for Database Objects

```php
// Build complex queries using SimpleQuery, then use in database objects
$balanceQuery = SimpleQuery::create()
    ->select(['COALESCE(SUM(amount), 0.00) as balance'])
    ->from('transactions')
    ->where('user_id = ?', [$user_id])
    ->where('type = ?', ['credit'])
    ->toSql();

// Use the generated SQL in function body
$objects->function('get_balance')
    ->body("
        DECLARE balance DECIMAL(10,2);
        SELECT balance INTO balance FROM ({$balanceQuery}) as balance_query;
        RETURN balance;
    ")
    ->create();
```

### Direct Expressive Patterns in SQL

```php
// Use expressive patterns directly in database object SQL
$objects->function('get_user_balance')
    ->body("
        DECLARE balance DECIMAL(10,2);
        
        -- Expressive SELECT with aggregation and CASE
        SELECT COALESCE(SUM(
            CASE 
                WHEN type = 'credit' THEN amount 
                WHEN type = 'debit' THEN -amount 
                ELSE 0 
            END
        ), 0.00) INTO balance
        FROM transactions 
        WHERE user_id = user_id;
        
        RETURN balance;
    ")
    ->create();
```

---

## 🏗️ Unified Management Architecture

### DatabaseObjectManager Interface

```php
interface DatabaseObjectManagerInterface
{
    public function function(string $functionName): FunctionInterface;
    public function procedure(string $procedureName): ProcedureInterface;
    public function view(string $viewName): ViewInterface;
    public function event(string $eventName): EventInterface;
    public function trigger(string $triggerName): TriggerInterface;
    
    public function getFunctions(): array;
    public function getProcedures(): array;
    public function getViews(): array;
    public function getEvents(): array;
    public function getTriggers(): array;
    public function getAllObjects(): array;
    
    public function dropAllFunctions(): bool;
    public function dropAllProcedures(): bool;
    public function dropAllViews(): bool;
    public function dropAllEvents(): bool;
    public function dropAllTriggers(): bool;
    public function dropAllObjects(): bool;
    
    public function hasObjects(): bool;
    public function getObjectCounts(): array;
}
```

### Implementation

```php
class DatabaseObjectManager implements DatabaseObjectManagerInterface
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function function(string $functionName): FunctionInterface
    {
        return new FunctionBuilder($this->db, $functionName);
    }

    public function procedure(string $procedureName): ProcedureInterface
    {
        return new ProcedureBuilder($this->db, $procedureName);
    }

    // ... other methods
}
```

---

## 🛡️ Benefits of Interface-Based Architecture

### 1. **Flexibility**
- Easy to swap implementations
- Mock objects for testing
- Multiple database support

### 2. **Testability**
```php
// Easy to mock for testing
$mockFunction = $this->createMock(FunctionInterface::class);
$mockFunction->expects($this->once())
    ->method('create')
    ->willReturn(true);
```

### 3. **Extensibility**
```php
// Custom implementation
class CustomFunctionBuilder implements FunctionInterface
{
    // Custom implementation
}
```

### 4. **Type Safety**
- Compile-time checking
- IDE autocomplete
- Clear contracts

### 5. **Integration**
- Seamless SimpleQuery integration
- Consistent API patterns
- Unified error handling

---

## 📚 Summary

SimpleMDB's interface-based architecture provides:

- **Interface-First Design**: All objects implement interfaces
- **Expressive Query Integration**: Database objects use SimpleQuery patterns
- **Unified Management**: Single interface for all objects
- **Type Safety**: Compile-time checking and IDE support
- **Testability**: Easy mocking and testing
- **Extensibility**: Custom implementations possible
- **Consistency**: Fluent interfaces throughout

This architecture makes SimpleMDB a truly flexible and enterprise-ready database toolkit that can adapt to any project's needs while maintaining the expressive query capabilities that make development enjoyable and efficient.

## 🎯 Key Integration Points

### SimpleQuery for PHP Code
```php
// Use SimpleQuery for building queries in PHP
$query = SimpleQuery::create()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->orderBy('created_at DESC')
    ->execute($db);
```

### Raw SQL for Database Objects
```php
// Use expressive SQL patterns directly in database objects
$objects->function('get_active_users')
    ->body("
        SELECT id, name, email 
        FROM users 
        WHERE is_active = TRUE 
        ORDER BY created_at DESC;
    ")
    ->create();
```

### Hybrid Approach
```php
// Build complex queries with SimpleQuery, then use in database objects
$complexQuery = SimpleQuery::create()
    ->select(['u.id', 'u.name', 'COUNT(o.id) as order_count'])
    ->from('users u')
    ->leftJoin('orders o', 'u.id = o.user_id')
    ->where('u.is_active = ?', [true])
    ->groupBy(['u.id', 'u.name'])
    ->toSql();

$objects->view('active_users_summary')
    ->select($complexQuery)
    ->create();
```

This approach gives us the best of both worlds: expressive query building in PHP code and direct SQL control in database objects where it matters most. 