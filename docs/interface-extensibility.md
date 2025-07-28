# Interface-Based Extensibility Architecture

SimpleMDB follows a **comprehensive interface-first design philosophy** that maximizes extensibility, testability, and maintainability. This document explains the interface architecture and how it enables powerful customization capabilities.

## 🏗️ Interface Architecture Overview

### Core Interface Hierarchy

```
DatabaseInterface (Base Database Operations)
├── SchemaBuilderInterface (Schema Management)
├── QueryBuilderInterface (Query Building)
├── BatchOperationsInterface (Batch Processing)
├── CacheInterface (Caching)
├── CacheManagerInterface (Cache Management)
├── ConnectionPoolInterface (Connection Pooling)
├── RetryPolicyInterface (Retry Logic)
├── QueryDebuggerInterface (Query Debugging)
├── QueryProfilerInterface (Query Profiling)
├── SeederInterface (Database Seeding)
├── MigrationInterface (Database Migrations)
├── TableAlterInterface (Table Alterations)
└── DatabaseObjectManagerInterface (Object Management)
    ├── FunctionInterface
    ├── ProcedureInterface
    ├── ViewInterface
    ├── EventInterface
    └── TriggerInterface
```

## 🎯 Benefits of Interface-Based Architecture

### 1. **Extensibility**
- Easy to swap implementations
- Custom implementations possible
- Plugin architecture support
- Framework integration ready

### 2. **Testability**
- Mock objects for testing
- Unit test isolation
- Integration test flexibility
- Behavior verification

### 3. **Maintainability**
- Clear contracts
- Separation of concerns
- Dependency injection
- Loose coupling

### 4. **Flexibility**
- Multiple database support
- Custom query builders
- Alternative caching strategies
- Different connection pooling

## 📋 Interface Details

### SchemaBuilderInterface

**Purpose**: Abstract schema building operations for different database engines.

```php
interface SchemaBuilderInterface
{
    public static function create(DatabaseInterface $db): self;
    
    // Column methods
    public function integer(string $name, bool $unsigned = false): self;
    public function string(string $name, int $length = 255): self;
    public function datetime(string $name): self;
    
    // Table operations
    public function createTable(string $tableName): bool;
    public function dropTable(string $tableName): bool;
    public function hasTable(string $tableName): bool;
}
```

**Benefits**:
- Support for different database engines (MySQL, PostgreSQL, SQLite)
- Custom schema builders for specific use cases
- Testing with mock schema builders

### QueryBuilderInterface

**Purpose**: Abstract query building for different SQL dialects and ORMs.

```php
interface QueryBuilderInterface
{
    public static function create(): self;
    
    // Query building
    public function select(array $fields): self;
    public function from(string $table): self;
    public function where(string $condition, array $params = []): self;
    
    // Execution
    public function execute(DatabaseInterface $db, string $fetchType = 'assoc');
    public function toSql(): string;
}
```

**Benefits**:
- Support for different SQL dialects
- Integration with existing ORMs
- Custom query builders for specific domains
- Testing with mock query builders

### BatchOperationsInterface

**Purpose**: Abstract batch processing for different optimization strategies.

```php
interface BatchOperationsInterface
{
    public static function create(DatabaseInterface $db, int $batchSize = 1000): self;
    
    // Batch operations
    public function batchInsert(string $table, array $columns, array $records): array;
    public function batchUpdate(string $table, array $data, array $conditions, array $records): array;
    public function batchDelete(string $table, array $conditions): array;
    public function upsert(string $table, array $columns, array $records, array $uniqueColumns): array;
}
```

**Benefits**:
- Different batch processing strategies
- Custom optimization algorithms
- Integration with external batch processors
- Testing with controlled batch operations

### CacheInterface & CacheManagerInterface

**Purpose**: Abstract caching for different cache backends.

```php
interface CacheInterface
{
    public function get(string $key);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}

interface CacheManagerInterface
{
    public static function create(CacheInterface $cache): self;
    public function set(string $key, $value, array $tags = [], int $ttl = 3600): bool;
    public function invalidateTag(string $tag): void;
}
```

**Benefits**:
- Support for Redis, Memcached, APCu, etc.
- Custom cache implementations
- Tag-based cache invalidation
- Testing with mock caches

### ConnectionPoolInterface

**Purpose**: Abstract connection pooling for different strategies.

```php
interface ConnectionPoolInterface
{
    public static function create(array $config): self;
    
    // Connection management
    public function getWriteConnection(): DatabaseInterface;
    public function getReadConnection(): DatabaseInterface;
    public function executeQuery(string $sql, array $params = []): mixed;
    
    // Health monitoring
    public function healthCheck(): array;
    public function isHealthy(): bool;
}
```

**Benefits**:
- Different pooling strategies
- Custom load balancing
- Health monitoring integration
- Testing with controlled connections

### RetryPolicyInterface

**Purpose**: Abstract retry logic for different failure scenarios.

```php
interface RetryPolicyInterface
{
    public static function create(int $maxRetries = 3, int $baseDelayMs = 100): self;
    
    // Retry execution
    public function execute(callable $operation, array $args = []): mixed;
    public function executeWithSettings(callable $operation, array $args = [], int $maxRetries = null): mixed;
    
    // Configuration
    public function addRetryableException(string $exceptionClass): self;
    public function addRetryableErrorCode(int $errorCode): self;
}
```

**Benefits**:
- Different retry strategies
- Custom backoff algorithms
- Integration with circuit breakers
- Testing with controlled failures

### QueryDebuggerInterface & QueryProfilerInterface

**Purpose**: Abstract query analysis for different profiling needs.

```php
interface QueryDebuggerInterface
{
    public static function create(DatabaseInterface $db, ?string $logFile = null): self;
    
    // Debugging
    public function addQuery(string $sql, array $params, float $executionTime): void;
    public function getQueries(): array;
    public function explainQuery(SimpleQuery $query): array;
}

interface QueryProfilerInterface
{
    public static function create(DatabaseInterface $db): self;
    
    // Profiling
    public function analyzeQuery(SimpleQuery $query): array;
    public function getReport(): array;
    public function getQueryStats(): array;
}
```

**Benefits**:
- Different debugging strategies
- Custom profiling tools
- Integration with APM systems
- Testing with controlled profiling

### SeederInterface & MigrationInterface

**Purpose**: Abstract database seeding and migration operations.

```php
interface SeederInterface
{
    public static function create(DatabaseInterface $db): self;
    public function run(): void;
    public function getName(): string;
    public function getDependencies(): array;
}

interface MigrationInterface
{
    public static function create(DatabaseInterface $db): self;
    public function up(): void;
    public function down(): void;
    public function getName(): string;
    public function getVersion(): string;
}
```

**Benefits**:
- Different seeding strategies
- Custom migration systems
- Integration with existing tools
- Testing with controlled data

### TableAlterInterface

**Purpose**: Abstract table alteration operations.

```php
interface TableAlterInterface
{
    public static function create(DatabaseInterface $db, string $tableName): self;
    
    // Column operations
    public function addColumn(string $name, array $definition): self;
    public function modifyColumn(string $name, array $definition): self;
    public function dropColumn(string $name): self;
    
    // Index operations
    public function addIndex(array $columns, ?string $name = null): self;
    public function dropIndex(string $name): self;
    
    // Execution
    public function execute(): bool;
    public function toSql(): string;
}
```

**Benefits**:
- Different alteration strategies
- Custom schema evolution
- Integration with migration tools
- Testing with controlled alterations

### DatabaseObjectManagerInterface

**Purpose**: Abstract database object management.

```php
interface DatabaseObjectManagerInterface
{
    public static function create(DatabaseInterface $db): self;
    
    // Object builders
    public function function(string $functionName): FunctionInterface;
    public function procedure(string $procedureName): ProcedureInterface;
    public function view(string $viewName): ViewInterface;
    public function event(string $eventName): EventInterface;
    public function trigger(string $triggerName): TriggerInterface;
    
    // Object management
    public function getAllObjects(): array;
    public function dropAllObjects(): bool;
    public function hasObjects(): bool;
}
```

**Benefits**:
- Different object management strategies
- Custom object builders
- Integration with existing systems
- Testing with controlled objects

## 🔧 Implementation Examples

### Custom Query Builder

```php
class CustomQueryBuilder implements QueryBuilderInterface
{
    private array $conditions = [];
    private string $table = '';
    
    public static function create(): self
    {
        return new self();
    }
    
    public function select(array $fields): self
    {
        // Custom implementation
        return $this;
    }
    
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    
    public function where(string $condition, array $params = []): self
    {
        $this->conditions[] = ['condition' => $condition, 'params' => $params];
        return $this;
    }
    
    public function execute(DatabaseInterface $db, string $fetchType = 'assoc')
    {
        // Custom execution logic
        $sql = $this->toSql();
        return $db->query($sql)->fetchAll($fetchType);
    }
    
    public function toSql(): string
    {
        // Custom SQL generation
        return "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $this->conditions);
    }
}
```

### Custom Cache Implementation

```php
class RedisCache implements CacheInterface
{
    private Redis $redis;
    
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }
    
    public function get(string $key)
    {
        return $this->redis->get($key);
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        return $this->redis->setex($key, $ttl, serialize($value));
    }
    
    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }
    
    public function clear(): bool
    {
        return $this->redis->flushDB();
    }
    
    public function has(string $key): bool
    {
        return $this->redis->exists($key);
    }
}
```

### Custom Retry Policy

```php
class ExponentialBackoffRetryPolicy implements RetryPolicyInterface
{
    private int $maxRetries;
    private int $baseDelayMs;
    
    public static function create(int $maxRetries = 3, int $baseDelayMs = 100): self
    {
        return new self($maxRetries, $baseDelayMs);
    }
    
    public function execute(callable $operation, array $args = []): mixed
    {
        $lastException = null;
        
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $operation(...$args);
            } catch (Exception $e) {
                $lastException = $e;
                
                if ($attempt >= $this->maxRetries) {
                    break;
                }
                
                $delay = $this->baseDelayMs * pow(2, $attempt);
                usleep($delay * 1000);
            }
        }
        
        throw $lastException;
    }
}
```

## 🧪 Testing with Interfaces

### Mock Testing

```php
class DatabaseTest extends TestCase
{
    public function testQueryExecution()
    {
        // Create mock query builder
        $mockQueryBuilder = $this->createMock(QueryBuilderInterface::class);
        $mockQueryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn(['result' => 'test']);
        
        // Create mock database
        $mockDb = $this->createMock(DatabaseInterface::class);
        
        // Test with mocks
        $result = $mockQueryBuilder->execute($mockDb);
        $this->assertEquals(['result' => 'test'], $result);
    }
}
```

### Integration Testing

```php
class CacheIntegrationTest extends TestCase
{
    public function testCacheOperations()
    {
        // Use real cache implementation
        $cache = new RedisCache(new Redis());
        $cacheManager = CacheManager::create($cache);
        
        // Test cache operations
        $cacheManager->set('test', 'value', ['tag1'], 3600);
        $this->assertEquals('value', $cacheManager->get('test'));
        
        $cacheManager->invalidateTag('tag1');
        $this->assertNull($cacheManager->get('test'));
    }
}
```

## 🚀 Advanced Usage Patterns

### Factory Pattern

```php
class DatabaseComponentFactory
{
    public static function createQueryBuilder(string $type = 'default'): QueryBuilderInterface
    {
        return match($type) {
            'custom' => new CustomQueryBuilder(),
            'orm' => new OrmQueryBuilder(),
            default => new SimpleQuery()
        };
    }
    
    public static function createCache(string $type = 'default'): CacheInterface
    {
        return match($type) {
            'redis' => new RedisCache(new Redis()),
            'memcached' => new MemcachedCache(new Memcached()),
            default => new MemoryCache()
        };
    }
}
```

### Strategy Pattern

```php
class QueryExecutionStrategy
{
    private QueryBuilderInterface $queryBuilder;
    private RetryPolicyInterface $retryPolicy;
    private CacheInterface $cache;
    
    public function __construct(
        QueryBuilderInterface $queryBuilder,
        RetryPolicyInterface $retryPolicy,
        CacheInterface $cache
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->retryPolicy = $retryPolicy;
        $this->cache = $cache;
    }
    
    public function execute(string $sql, array $params = []): mixed
    {
        $cacheKey = md5($sql . serialize($params));
        
        // Try cache first
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // Execute with retry
        $result = $this->retryPolicy->execute(
            fn() => $this->queryBuilder->execute($sql, $params)
        );
        
        // Cache result
        $this->cache->set($cacheKey, $result, 3600);
        
        return $result;
    }
}
```

## 📊 Performance Benefits

### 1. **Lazy Loading**
Interfaces enable lazy loading of components, reducing memory usage.

### 2. **Caching Strategies**
Different cache implementations can be swapped based on performance needs.

### 3. **Connection Pooling**
Custom connection pooling strategies can optimize for specific workloads.

### 4. **Query Optimization**
Custom query builders can implement specific optimizations.

## 🔒 Security Benefits

### 1. **Input Validation**
Custom implementations can add additional security layers.

### 2. **SQL Injection Prevention**
Custom query builders can implement stricter sanitization.

### 3. **Access Control**
Custom cache managers can implement role-based access.

## 🎯 Best Practices

### 1. **Interface Segregation**
Keep interfaces focused and cohesive.

### 2. **Dependency Injection**
Use interfaces for all dependencies.

### 3. **Factory Methods**
Provide static factory methods for easy instantiation.

### 4. **Comprehensive Testing**
Test all interface implementations thoroughly.

### 5. **Documentation**
Document all interface contracts clearly.

## 📈 Future Extensibility

The interface-based architecture enables:

- **Plugin Systems**: Easy integration of third-party plugins
- **Framework Integration**: Seamless integration with popular frameworks
- **Cloud Services**: Integration with cloud database services
- **Microservices**: Distributed database operations
- **AI/ML Integration**: Smart query optimization
- **Real-time Features**: WebSocket and event-driven architectures

## 🏆 Conclusion

SimpleMDB's comprehensive interface-based architecture provides:

- **Maximum Extensibility**: Easy to customize and extend
- **Excellent Testability**: Mock objects and controlled testing
- **High Maintainability**: Clear contracts and separation of concerns
- **Great Flexibility**: Support for different implementations
- **Future-Proof Design**: Ready for emerging technologies

This architecture makes SimpleMDB a truly enterprise-ready database toolkit that can adapt to any project's needs while maintaining the expressive query capabilities that make development enjoyable and efficient. 