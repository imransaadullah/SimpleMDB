# SimpleMDB

A modern PHP-8+ database toolkit that unifies query building, schema management, batch operations, caching, profiling, debugging and sanitisation for MySQL-compatible databases.

## Why SimpleMDB?

* Keep full control of SQL while removing boiler-plate.
* Fluent, type-safe API – no magic strings sprinkled through your codebase.
* Works with PDO **or** MySQLi; swap drivers without touching application code.
* Completely modular – pull in only the helpers you need.

## Feature Matrix

| Area | Highlights |
|------|------------|
| Connection | Factory pattern, SSL, transactions, unified `DatabaseInterface` |
| **Connection Pooling** | **Read/write splitting, health checks, load balancing, automatic failover** |
| Query Builder (`SimpleQuery`) | SELECT/INSERT/UPDATE/DELETE, CTEs, window functions, CASE, expressions, unions, conditional clauses |
| **Expression Builder** | **Raw SQL expressions, EXISTS/NOT EXISTS, CASE statements with fluent API** |
| Schema (`SchemaBuilder` & `TableAlter`) | Create & alter tables/columns/indexes/FKs/PKs, engine, charset, collation |
| **Migrations** | **Database migrations with up/down, rollback, status tracking, auto-discovery** |
| **Seeding** | **Database seeding with comprehensive fake data generation (names, emails, addresses, etc.)** |
| Bulk Ops (`BatchOperations`) | Bulk insert/update/delete, UPSERT, transactional batches |
| Caching (`CacheManager`) | In-memory / file backend, tag based invalidation |
| **Advanced Caching** | **Redis, Memcached backends with connection pooling and failover** |
| **Retry Logic** | **Automatic retry with exponential backoff for transient failures** |
| Profiling (`QueryProfiler`) | EXPLAIN JSON auto analysis, timing, memory usage |
| Debugging (`QueryDebugger`) | Pretty SQL formatter, duplicate / slow query detector, stack trace, optional log file |
| Sanitisation (`QuerySanitizer`) | Validation & cleansing helpers to stop bad data _before_ the DB sees it |
| Events (PSR-14) | `BeforeQuery` / `AfterQuery` / `QueryError` hooks – plug-in listeners anywhere |
| Logging (PSR-3) | Pluggable logger (`setLogger`), query & error logging out-of-the-box |

## Classic / Low-Level Capabilities (unchanged)

> The modern features above build on top of the original Simple-MySQLi API — nothing was removed.

### Dual Drivers – PDO **and** MySQLi
Call `DatabaseFactory::create()` with driver parameter `'pdo'` or `'mysqli'`. Both implement the same `DatabaseInterface`, so code is portable.

### SSL & Secure Connections
Factory accepts a `sslOptions` array (`key`, `cert`, `ca`, `verify_cert`) for both drivers.

### Low-Level Query Helpers
| Method | Purpose |
|--------|---------|
| `query( $sql , $params = [] )` | prepare + execute in one call |
| `prepare()->execute()` | classic two-step version |
| `whereIn( [$ids] )` | returns `?, ?, ? …` placeholder list |
| `read_data()` / `read_data_all()` | shorthand SELECT helpers |
| `write_data()` / `update()` / `delete()` | shorthand DML helpers |
| `exportDatabase()` | mysqldump wrapper |

### Fetch-Type Matrix
| Name | Driver(s) | Result |
|------|-----------|--------|
| `assoc` | both | `[ 'col' => 'val' ]` |
| `num`   | both | `[0 => val]` |
| `obj`   | both | `stdClass` or custom class |
| `col`   | both | scalar list |
| `keyPair` | PDO | `[k => v]` |
| `keyPairArr` | PDO | `[k => [v1,v2]]` |
| `group` / `groupCol` / `groupObj` | PDO | nested arrays / objects |

### Exception Types
• `SimpleMDBException` — base exception class with context support  
• `ConnectionException` — connection failures  
• `QueryException` — SQL execution errors  
• `SchemaException` — schema operation failures  
• `SimplePDOException` — PDO-specific errors  

Usage example:
```php
try {
    $db->query('SELECT * FROM not_there');
} catch (SimpleMDB\Exceptions\QueryException $e) {
    // Handle query-specific errors
    echo $e->getDetailedMessage(); // includes SQL, params, context
} catch (SimpleMDB\Exceptions\SimpleMDBException $e) {
    // Handle all SimpleMDB errors
} catch (Exception $e) {
    // generic fallback
}
```

---

All earlier conveniences remain 100% compatible — the new layers are additive.

## Installation

```bash
composer require simple-mysqli/simple-mysqli
```

Requires **PHP 8.0+** and either the PDO _or_ MySQLi extension.

---

## Quick Start

### 1  Connect

```php
use SimpleMDB\DatabaseFactory;

/* ---------------------------------------------------------
 | PDO DRIVER (recommended)
 |----------------------------------------------------------*/
$pdoDb = DatabaseFactory::create(
    'pdo',              // driver: 'pdo' or 'mysqli'
    '127.0.0.1',        // host
    'root',             // username
    'secret',           // password
    'demo',             // database name
    'utf8mb4',          // charset (optional)
    'assoc'             // default fetch type (optional)
);

/* ---------------------------------------------------------
 | MYSQLI DRIVER (if you prefer native MySQLi)
 |----------------------------------------------------------*/
$mysqliDb = DatabaseFactory::create(
    'mysqli',
    'localhost',
    'root',
    'secret',
    'demo'
);

/* ---------------------------------------------------------
 | SECURE CONNECTION – SSL/TLS (works with both drivers)
 |----------------------------------------------------------*/
$secureDb = DatabaseFactory::create(
    'pdo',
    'db.company.internal',
    'app',
    'super-secret',
    'corp',
    'utf8mb4',
    'assoc',
    [                           // SSL options array
        'enable'      => true,
        'ca'          => '/etc/ssl/certs/ca.pem',
        'cert'        => '/etc/ssl/client-cert.pem',
        'key'         => '/etc/ssl/client-key.pem',
        'verify_cert' => true,
    ]
);
```

### 2  Run a fluent query
```php
use SimpleMDB\SimpleQuery;

$users = SimpleQuery::create()
        ->select(['id','name','email'])
        ->from('users')
        ->where('status = ?', ['active'])
        ->orderBy('name')
        ->limit(20)
        ->execute($db);   // array of rows
```

---

## Core Components and Recipes

### Query Builder Highlights
```php
// Window function + pagination example
$report = SimpleQuery::create()
     ->select([
         'department',
         'salary',
         SimpleQuery::create()->rowNumber(['department'], ['salary DESC'])->getExpression().' AS rank'
     ])
     ->from('employees')
     ->where('hire_date >= ?', ['2023-01-01'])
     ->orderBy('department')
     ->paginate(50, $page * 50)
     ->execute($db);
```

### Caching with Tags
```php
use SimpleMDB\CacheManager;
use SimpleMDB\MemoryCache;

$cache = new CacheManager(new MemoryCache());
$key   = 'users-active';

$users = $cache->has($key)
        ? $cache->get($key)
        : $cache->set(
              $key,
              SimpleQuery::create()
                  ->select(['id','name'])
                  ->from('users')->where('status=?',['active'])
                  ->execute($db),
              ['users'],   // tags
              600          // ttl
          );

// Invalidate by tag
$cache->invalidateTag('users');
```

### Advanced Caching with Redis/Memcached
```php
use SimpleMDB\CacheManager;
use SimpleMDB\Cache\RedisCache;
use SimpleMDB\Cache\MemcachedCache;

// Redis backend
$redisCache = new CacheManager(new RedisCache(
    '127.0.0.1',    // host
    6379,           // port
    '',             // password
    0,              // database
    'myapp:',       // prefix
    3600            // default TTL
));

// Memcached backend
$memcachedCache = new CacheManager(new MemcachedCache(
    [['127.0.0.1', 11211], ['127.0.0.1', 11212]], // servers
    'myapp:',       // prefix
    3600,           // default TTL
    []              // options
));
```

### Connection Pooling
```php
use SimpleMDB\Connection\PooledDatabaseFactory;

// Create connection pool with read/write splitting
$pool = PooledDatabaseFactory::createSimplePool(
    // Write connection
    [
        'driver' => 'pdo',
        'host' => 'master.db.example.com',
        'username' => 'app',
        'password' => 'secret',
        'database' => 'myapp'
    ],
    // Read replicas
    [
        [
            'driver' => 'pdo',
            'host' => 'replica1.db.example.com',
            'username' => 'app_readonly',
            'password' => 'secret',
            'database' => 'myapp'
        ],
        [
            'driver' => 'pdo',
            'host' => 'replica2.db.example.com',
            'username' => 'app_readonly',
            'password' => 'secret',
            'database' => 'myapp'
        ]
    ],
    // Pool options
    [
        'max_connections' => 20,
        'min_connections' => 5,
        'health_checks' => true,
        'health_check_interval' => 60
    ]
);

// Use the pool - queries are automatically routed
$writeConn = $pool->getWriteConnection();  // for INSERT/UPDATE/DELETE
$readConn = $pool->getReadConnection();    // for SELECT (load balanced)

// Auto-routing based on query type
$result = $pool->executeQuery('SELECT * FROM users');  // uses read replica
$pool->executeQuery('INSERT INTO logs VALUES (?)');    // uses write connection
```

### Database Migrations
```php
use SimpleMDB\Migrations\MigrationManager;
use SimpleMDB\Migrations\Migration;

// Set up migration manager
$migrations = new MigrationManager($db, 'database/migrations');

// Create a new migration
$migrationFile = $migrations->create('CreateUsersTable');

// Example migration class
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema
            ->integer('id', unsigned: true, autoIncrement: true)
            ->primaryKey('id')
            ->string('name', 255)
            ->string('email', 255)->unique()
            ->timestamp('email_verified_at')->nullable()
            ->string('password')
            ->timestamps()
            ->createTable('users');
    }

    public function down(): void
    {
        $this->dropTable('users');
    }
}

// Run migrations
$migrations->migrate();              // Run all pending
$migrations->migrate(3);             // Run next 3 migrations

// Rollback
$migrations->rollback(1);            // Rollback last migration
$migrations->reset();                // Rollback all migrations

// Check status
$status = $migrations->status();
foreach ($status as $migration) {
    echo "{$migration['migration']}: {$migration['status']}\n";
}
```

### Database Seeding
```php
use SimpleMDB\Seeding\Seeder;
use SimpleMDB\Seeding\FakeDataGenerator;

class UserSeeder extends Seeder
{
    protected string $table = 'users';
    protected bool $truncateFirst = true;

    public function run(): void
    {
        $faker = new FakeDataGenerator();
        $users = [];

        for ($i = 0; $i < 100; $i++) {
            $users[] = [
                'name' => $faker->name(),
                'email' => $faker->email(),
                'phone' => $faker->phone(),
                'company' => $faker->company(),
                'job_title' => $faker->jobTitle(),
                'address' => $faker->address(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'zip_code' => $faker->zipCode(),
                'created_at' => $faker->dateBetween('-1 year', 'now')
            ];
        }

        // Bulk insert
        $batch = new BatchOperations($this->db);
        $batch->batchInsert('users', array_keys($users[0]), $users);
    }
}

// Fake data generation examples
$faker = new FakeDataGenerator();
echo $faker->name();          // "John Smith"
echo $faker->email();         // "jane123@gmail.com"
echo $faker->company();       // "TechCorp"
echo $faker->address();       // "123 Main Street"
echo $faker->text(100);       // Lorem ipsum text
echo $faker->uuid();          // UUID v4
echo $faker->ipAddress();     // "192.168.1.1"
echo $faker->price(10, 100);  // Random price between $10-$100
```

### Retry Logic & Fault Tolerance
```php
use SimpleMDB\Retry\RetryableQuery;
use SimpleMDB\Retry\RetryPolicy;

// Custom retry policy
$retryPolicy = new RetryPolicy(
    maxRetries: 5,
    baseDelayMs: 100,
    backoffMultiplier: 2.0,
    maxDelayMs: 5000
);

$retryable = new RetryableQuery($db, $retryPolicy);

// Queries with automatic retry
$result = $retryable->query('SELECT * FROM users WHERE id = ?', [1]);

// Bulk operations with retry
$retryable->executeBulk(function() use ($batch) {
    return $batch->batchInsert('logs', ['event', 'user_id'], $data);
});

// Transaction with retry
$retryable->executeInTransaction(function($db) {
    $db->query('UPDATE accounts SET balance = balance - ? WHERE id = ?', [100, 1]);
    $db->query('UPDATE accounts SET balance = balance + ? WHERE id = ?', [100, 2]);
});
```

### Expression Builder & Advanced SQL
```php
use SimpleMDB\Expression;
use SimpleMDB\CaseBuilder;

// Raw expressions
$expr = Expression::raw('CONCAT(first_name, " ", last_name)');

// EXISTS subqueries
$existsExpr = Expression::exists(
    SimpleQuery::create()
        ->select(['1'])
        ->from('orders')
        ->where('user_id = users.id')
);

// Complex CASE statements
$statusExpr = Expression::case()
    ->when('age < ?', 18)->then('Minor')
    ->when('age >= ? AND age < ?', [18, 65])->then('Adult')
    ->else('Senior')
    ->end();

// Use in queries
$users = SimpleQuery::create()
    ->select([
        'id',
        $expr->getExpression() . ' AS full_name',
        $statusExpr->getExpression() . ' AS age_group'
    ])
    ->from('users')
    ->where($existsExpr->getExpression())
    ->execute($db);
```

### Bulk Operations
```php
use SimpleMDB\BatchOperations;

$batch = new BatchOperations($db, 500);

$batch->batchInsert('audit_log',
    ['user_id','event'],
    [[1,'login'],[2,'logout']]
);
```

### Schema Creation & Alteration
```php
use SimpleMDB\SchemaBuilder;

$schema = new SchemaBuilder($db);

// ---- create table -------------------------------------------------------
$schema
    ->integer('id', unsigned:true, autoIncrement:true)->primaryKey('id')
    ->string('name',100)
    ->decimal('price',10,2)->nullable()
    ->timestamps()
    ->engine('InnoDB')
    ->charset('utf8mb4')
    ->createTable('products');

// ---- alter table --------------------------------------------------------
$schema->table('products')
       ->addColumn('status', ['type'=>'VARCHAR','length'=>20,'default'=>'active'])
       ->modifyColumn('price', ['type'=>'DECIMAL','precision'=>12,'scale'=>2,'unsigned'=>true])
       ->addIndex('status')
       ->addForeignKey('category_id','categories','id','products_category_fk','SET NULL')
       ->renameColumn('old_name','new_name',['type'=>'VARCHAR','length'=>150])
       ->dropColumn('obsolete')
       ->setEngine('InnoDB');
```

### Profiling & Debugging
```php
use SimpleMDB\QueryProfiler;
use SimpleMDB\QueryDebugger;

$profiler = new QueryProfiler($db);
$debugger = new QueryDebugger($db, __DIR__.'/sql.log');

$query = SimpleQuery::create()
           ->select(['*'])->from('big_table')
           ->setLogger(fn($sql,$params,$time) =>
                 $profiler->addQuery($sql,$params,$time));

$query->execute($db);

print_r($profiler->getReport());        // performance insights
print_r($debugger->getQueryStats());     // totals, slowest, duplicates
```

### Validation / Sanitisation
```php
use SimpleMDB\QuerySanitizer;

$san = new QuerySanitizer();
$cleanEmail = $san->sanitize($_POST['email']??'', ['trim','email']);
if (!$san->validate($cleanEmail,'email')) {
    exit('Bad email');
}
```

### Event Hooks & Logging (PSR-14 / PSR-3)
```php
use SimpleMDB\Events\{BeforeQueryEvent,AfterQueryEvent,QueryErrorEvent};
use SimpleMDB\DatabaseFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

$dispatcher = new class implements EventDispatcherInterface {
    private array $listeners = [];
    public function dispatch(object $event): object {
        foreach ($this->listeners[get_class($event)] ?? [] as $listener) {
            $listener($event);
        }
        return $event;
    }
    public function addListener(string $eventClass, callable $listener): void {
        $this->listeners[$eventClass][] = $listener;
    }
};

$logger = new class implements LoggerInterface {
    use \Psr\Log\LoggerTrait;
    public function log($level, $message, array $context = []): void {
        echo "[$level] $message".PHP_EOL;
    }
};

$db = DatabaseFactory::create('pdo', 'localhost', 'root', 'secret', 'demo')
        ->setEventDispatcher($dispatcher)
        ->setLogger($logger);

$dispatcher->addListener(BeforeQueryEvent::class, function(BeforeQueryEvent $e) use ($logger) {
    $logger->info('About to execute SQL', ['sql'=>$e->sql,'params'=>$e->params]);
});
```
---

## API Reference (Cheat-Sheet)

### SimpleQuery (builder)
```php
select(array|Expression)    count() sum() avg()
from(string|subquery)       join() leftJoin() ...
where(cond, params[])       having()
orderBy() groupBy()
limit()/paginate()          union()
CTE: with(name, query)      Window: window()/rowNumber()/...
INSERT/UPDATE/DELETE helpers: insert()->into(), update()->set(), delete()
execute(DatabaseInterface)
```

### SchemaBuilder
```php
createTable() / dropTable()
column helpers: integer() string() text() decimal() ... nullable() default()
index() unique() foreignKey()
timestamps() softDeletes()
->table('name') returns TableAlter
```

### TableAlter
```php
addColumn() dropColumn() modifyColumn() renameColumn()
addIndex() dropIndex()
addPrimaryKey() dropPrimaryKey()
addForeignKey() dropForeignKey()
renameTable() setEngine() setCharset() setCollation()
```

### BatchOperations
```php
batchInsert() batchUpdate() batchDelete() upsert() transaction()
```

### CacheManager
```php
set(key,value,tags[],ttl) get() has() invalidateTag() invalidateTags() clear()
```

### QueryProfiler & QueryDebugger
See examples above; both expose `getReport()` / `getQueryStats()` plus specialised helpers.

### Connection Pooling
```php
PooledDatabaseFactory::createSimplePool() createMasterSlavePool() createFromEnv()
getWriteConnection() getReadConnection() executeQuery() transaction()
getStats() performHealthChecks() closeAll()
```

### Migrations
```php
MigrationManager: migrate() rollback() reset() status() create()
Migration: up() down() getName() getVersion() getDescription()
Schema helpers: createTable() dropTable() hasTable() hasColumn()
```

### Seeding & Fake Data
```php
Seeder: run() getName() getTable() getDependencies()
FakeDataGenerator: name() email() phone() company() address() text()
dateBetween() uuid() ipAddress() price() boolean() numberBetween()
```

### Retry Logic
```php
RetryPolicy: execute() executeWithSettings() isRetryable()
RetryableQuery: query() executeQuery() executeBulk() executeInTransaction()
```

### Expression Builder
```php
Expression: raw() case() exists() notExists() getExpression() getBindings()
CaseBuilder: when() else() end()
```

### Advanced Caching
```php
RedisCache: connect() get() set() delete() clear() has()
MemcachedCache: addServer() get() set() delete() clear() has()
```

---

## Testing

```bash
composer test           # PHPUnit
composer cs             # phpcs
composer static-analysis  # phpstan + psalm
```

---

## Contributing
Pull requests are welcome! Please run coding-standards & static analysis before submitting.

---

## License
MIT

## Comprehensive Usage Cookbook

Below is a method-by-method cheat-sheet.  Copy & paste any block as a starting point.

> All code assumes `$db` is an instance returned by `DatabaseFactory::create()`.

### Classic `DatabaseInterface` helpers
```php
// prepare → execute
$db->prepare('SELECT * FROM users WHERE id = ?')->execute([1])->fetch('assoc');

// one-liner query()
$row = $db->query('SELECT * FROM users WHERE id = ?', 1)->fetch('assoc');

// write_data / update / delete
$db->write_data('users', ['name'=>'Alice','email'=>'a@example.com']);
$db->update('users', ['status'=>'inactive'], 'WHERE id = ?', [3]);
$db->delete('users', 'id = ?', [3]);

// helper read
$single  = $db->read_data('users', ['name','email'], 'WHERE id = ?', [1]);
$list    = $db->read_data_all('users', ['id','name']);

// transaction helper
$db->transaction(function($db) {
    $db->write_data('logs',['event'=>'signup']);
});

// export (mysqldump) – returns bool success
SimpleMDB\SimplePDO::exportDatabase('localhost','root','secret','demo','/backups');
```

### `SimpleQuery` – full feature matrix
```php
$q = SimpleQuery::create()
        ->select(['u.id','u.name','d.avg_score'])
        ->with('scores', SimpleQuery::create()
                ->select(['user_id','AVG(score) AS avg_score'])
                ->from('ratings')
                ->groupBy(['user_id']) )
        ->from('users u')
        ->join('scores d', 'd.user_id = u.id')
        ->where('u.created_at >= ?', ['2023-01-01'])
        ->orderBy('d.avg_score','DESC')
        ->limit(10)
        ->execute($db);

// INSERT helper
SimpleQuery::create()
    ->insert(['name'=>'Eve','email'=>'eve@ex.com'])
    ->into('users')
    ->execute($db);

// UPDATE helper
SimpleQuery::create()
    ->update()->table('users')
    ->set(['status'=>'banned'])
    ->where('id = ?', [5])
    ->execute($db);

// DELETE helper
SimpleQuery::create()->delete()->from('users')
    ->where('last_login < ?', ['2022-01-01'])
    ->execute($db);

// Window fn & CASE
use SimpleMDB\Expression;

$ranked = SimpleQuery::create()
    ->select([
        'name',
        Expression::case()
            ->when('salary > ?', 80000)->then('High')
            ->when('salary > ?', 50000)->then('Mid')
            ->else('Low')->end()->getExpression().' AS band',
        Expression::rowNumber(['department'], ['salary DESC'])->getExpression().' AS rnk'
    ])
    ->from('employees')->execute($db);
```

### `BatchOperations`
```php
$batch = new BatchOperations($db);

// bulk insert
$batch->batchInsert('tags',['name'],[['php'],['mysql'],['redis']]);

// bulk update by PK
$batch->batchUpdate('users', ['status'], ['id'], [
    ['id'=>1,'status'=>'active'],
    ['id'=>2,'status'=>'inactive'],
]);

// upsert
$batch->upsert('settings', ['key','value'], [
    ['theme','dark'],
    ['lang','en']
], ['key']);
```

### `SchemaBuilder` & `TableAlter`
```php
$schema = new SchemaBuilder($db);
// Create fresh table
$schema->integer('id', unsigned:true, autoIncrement:true)->primaryKey('id')
       ->string('title',200)
       ->text('body')->nullable()
       ->timestamps()
       ->engine('InnoDB')->createTable('posts');

// Alter existing table
$schema->table('posts')
       ->addColumn('status',['type'=>'ENUM','values'=>['draft','pub'],'default'=>'draft'])
       ->addIndex('status')
       ->addForeignKey('author_id','users','id','posts_author_fk','CASCADE');
```

### `CacheManager`
```php
use SimpleMDB\CacheManager;
use SimpleMDB\FileCache;

$cache = new CacheManager(new FileCache(__DIR__.'/tmp/cache'));
$rows  = $cache->has('all_posts') ? $cache->get('all_posts')
             : $cache->set('all_posts', $db->query('SELECT * FROM posts')->fetchAll(), ['posts'], 600);
$cache->invalidateTag('posts');
```

### `QueryProfiler` & `QueryDebugger`
```php
$prof = new QueryProfiler($db);
$debug= new QueryDebugger($db);

SimpleQuery::create()->select(['*'])->from('huge')
    ->setLogger(fn($sql,$p,$t)=>$prof->addQuery($sql,$p,$t))
    ->execute($db);

print_r($prof->getReport());
print_r($debug->getQueryStats());
```

### `QuerySanitizer`
```php
$san = new QuerySanitizer();
$cleanEmail = $san->sanitize($_POST['email']??'', ['trim','email']);
if (!$san->validate($cleanEmail,'email')) {
    exit('Bad email');
}
```

### `Migrations` & `Seeding`
```php
// Create and run migrations
$mgr = new MigrationManager($db, 'migrations/');
$mgr->create('AddUserRoles');        // Create migration file
$mgr->migrate();                     // Run pending migrations
$mgr->rollback(2);                   // Rollback 2 migrations
$mgr->status();                      // Show migration status

// Seed database with fake data
class ProductSeeder extends Seeder {
    public function run(): void {
        $faker = new FakeDataGenerator();
        $products = [];
        for($i = 0; $i < 50; $i++) {
            $products[] = [
                'name' => $faker->company() . ' ' . rand(1000,9999),
                'price' => $faker->price(10, 500),
                'description' => $faker->text(200),
                'created_at' => $faker->dateBetween('-6 months')
            ];
        }
        BatchOperations($this->db)->batchInsert('products', array_keys($products[0]), $products);
    }
}
```

### `Connection Pooling` & `Retry Logic`
```php
// Advanced connection pooling
$pool = PooledDatabaseFactory::createFromEnv(['max_connections' => 50]);
$stats = $pool->getStats();          // Get pool statistics
$health = $pool->performHealthChecks(); // Check connection health

// Retry logic for fault tolerance
$retry = new RetryableQuery($db, new RetryPolicy(maxRetries: 5));
$result = $retry->executeWithCustomRetry(
    fn() => $db->query('SELECT * FROM flaky_table'),
    [],
    3,      // max retries
    200     // base delay ms
);
```

### `Expression Builder` & `Advanced SQL`
```php
// Complex expressions
$fullName = Expression::raw('CONCAT(first_name, " ", last_name)');
$ageGroup = Expression::case()
    ->when('age < 18', 'Minor')
    ->when('age BETWEEN 18 AND 64', 'Adult')
    ->else('Senior')->end();

$hasOrders = Expression::exists(
    SimpleQuery::create()->select(['1'])->from('orders')->where('user_id = users.id')
);

// Use in complex queries
SimpleQuery::create()
    ->select(['id', $fullName->getExpression().' AS name', $ageGroup->getExpression().' AS group'])
    ->from('users')
    ->where($hasOrders->getExpression())
    ->execute($db);
```

### `Advanced Caching Strategies`
```php
// Multi-tier caching with Redis
$redis = new CacheManager(new RedisCache('localhost', 6379, '', 0, 'app:'));
$multi = new CacheManager(new MemoryCache()); // L1 cache

// Cache with sophisticated invalidation
$userCache = $redis->set('user:'.$id, $userData, ['users', 'user:'.$id], 3600);
$redis->invalidateTag('users');      // Invalidate all user caches
$redis->invalidateTags(['users', 'orders']); // Multiple tags
```


> Tip: Each helper class is **independent**. You can import just the pieces you need.
