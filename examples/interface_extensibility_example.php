<?php

/**
 * Interface Extensibility Example
 * 
 * This example demonstrates how to use SimpleMDB's interface-based architecture
 * to create custom implementations and enhance extensibility.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\SimpleMySQLi;
use SimpleMDB\DatabaseInterface;
use SimpleMDB\Interfaces\{
    SchemaBuilderInterface,
    QueryBuilderInterface,
    BatchOperationsInterface,
    CacheInterface,
    CacheManagerInterface,
    ConnectionPoolInterface,
    RetryPolicyInterface,
    QueryDebuggerInterface,
    QueryProfilerInterface,
    SeederInterface,
    MigrationInterface,
    TableAlterInterface,
    DatabaseObjectManagerInterface
};

// Custom implementations that demonstrate extensibility

/**
 * Custom Query Builder for MongoDB-style queries
 */
class MongoStyleQueryBuilder implements QueryBuilderInterface
{
    private array $pipeline = [];
    private string $collection = '';
    private array $filters = [];
    private array $projection = [];
    private array $sort = [];
    private int $limit = 0;
    private int $skip = 0;

    public static function create(): self
    {
        return new self();
    }

    public function select(array $fields): self
    {
        $this->projection = $fields;
        return $this;
    }

    public function from(string $table): self
    {
        $this->collection = $table;
        return $this;
    }

    public function where(string $condition, array $params = []): self
    {
        $this->filters[] = ['condition' => $condition, 'params' => $params];
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->sort[$field] = $direction === 'ASC' ? 1 : -1;
        return $this;
    }

    public function limit(int $count, int $offset = 0): self
    {
        $this->limit = $count;
        $this->skip = $offset;
        return $this;
    }

    public function execute(DatabaseInterface $db, string $fetchType = 'assoc')
    {
        // Convert MongoDB-style query to SQL
        $sql = $this->toSql();
        return $db->query($sql)->fetchAll($fetchType);
    }

    public function toSql(): string
    {
        $sql = "SELECT ";
        
        if (!empty($this->projection)) {
            $sql .= implode(', ', $this->projection);
        } else {
            $sql .= "*";
        }
        
        $sql .= " FROM {$this->collection}";
        
        if (!empty($this->filters)) {
            $conditions = array_map(fn($filter) => $filter['condition'], $this->filters);
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if (!empty($this->sort)) {
            $sortClauses = array_map(fn($field, $dir) => "$field " . ($dir === 1 ? 'ASC' : 'DESC'), array_keys($this->sort), $this->sort);
            $sql .= " ORDER BY " . implode(', ', $sortClauses);
        }
        
        if ($this->limit > 0) {
            $sql .= " LIMIT {$this->skip}, {$this->limit}";
        }
        
        return $sql;
    }

    public function getParams(): array
    {
        $params = [];
        foreach ($this->filters as $filter) {
            $params = array_merge($params, $filter['params']);
        }
        return $params;
    }

    // Implement other required methods...
    public function selectWithAlias(array $fieldsWithAliases): self { return $this; }
    public function count(string $field = '*', string $alias = 'count'): self { return $this; }
    public function sum(string $field, string $alias = 'sum'): self { return $this; }
    public function avg(string $field, string $alias = 'avg'): self { return $this; }
    public function fromWithAlias(string $table, string $alias): self { return $this; }
    public function addCondition(bool $condition, callable $callback): self { return $this; }
    public function join(string $table, string $condition, string $type = 'INNER'): self { return $this; }
    public function leftJoin(string $table, string $condition): self { return $this; }
    public function rightJoin(string $table, string $condition): self { return $this; }
    public function fullJoin(string $table, string $condition): self { return $this; }
    public function groupBy(array $fields): self { return $this; }
    public function having(string $condition, array $params = []): self { return $this; }
    public function paginate(int $limit, int $offset = 0): self { return $this; }
    public function union(QueryBuilderInterface $query, bool $all = false): self { return $this; }
    public function subquery(QueryBuilderInterface $query, string $alias): self { return $this; }
    public function with(string $name, self $query): self { return $this; }
    public function window(string $name, array $partitionBy = [], array $orderBy = []): self { return $this; }
    public function over(?string $windowName = null, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function rowNumber(array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function rank(array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function denseRank(array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function lag(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function lead(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function firstValue(string $column, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function lastValue(string $column, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
    public function insert(array $data): self { return $this; }
    public function into(string $table): self { return $this; }
    public function update(): self { return $this; }
    public function set(array $data): self { return $this; }
    public function table(string $table): self { return $this; }
    public function delete(): self { return $this; }
    public function executeInTransaction(DatabaseInterface $db, string $fetchType = 'assoc') { return null; }
    public function enableCache(bool $enable = true): self { return $this; }
    public static function escapeIdentifier(string $identifier): string { return $identifier; }
}

/**
 * Redis Cache Implementation
 */
class RedisCache implements CacheInterface
{
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function get(string $key)
    {
        $value = $this->redis->get($key);
        return $value ? unserialize($value) : null;
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

    public function getMultiple(array $keys): array
    {
        $values = $this->redis->mget($keys);
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] ? unserialize($values[$i]) : null;
        }
        return $result;
    }

    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        $pipeline = $this->redis->multi();
        foreach ($values as $key => $value) {
            $pipeline->setex($key, $ttl, serialize($value));
        }
        $results = $pipeline->exec();
        return !in_array(false, $results);
    }

    public function deleteMultiple(array $keys): bool
    {
        return $this->redis->del($keys) > 0;
    }

    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($key, $value);
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->redis->decrBy($key, $value);
    }

    public function getStats(): array
    {
        return $this->redis->info();
    }

    public function getSize(): int
    {
        return $this->redis->dbSize();
    }

    public function getKeys(): array
    {
        return $this->redis->keys('*');
    }

    public function isEmpty(): bool
    {
        return $this->redis->dbSize() === 0;
    }

    public function getConfig(): array
    {
        return [
            'type' => 'redis',
            'host' => $this->redis->getHost(),
            'port' => $this->redis->getPort(),
        ];
    }
}

/**
 * Custom Retry Policy with Circuit Breaker
 */
class CircuitBreakerRetryPolicy implements RetryPolicyInterface
{
    private int $maxRetries;
    private int $baseDelayMs;
    private float $backoffMultiplier;
    private int $maxDelayMs;
    private array $retryableExceptions;
    private array $retryableErrorCodes;
    private array $failureCounts = [];
    private array $lastFailureTimes = [];
    private int $circuitBreakerThreshold = 5;
    private int $circuitBreakerTimeout = 60;

    public static function create(int $maxRetries = 3, int $baseDelayMs = 100, float $backoffMultiplier = 2.0, int $maxDelayMs = 5000): self
    {
        return new self($maxRetries, $baseDelayMs, $backoffMultiplier, $maxDelayMs);
    }

    public function __construct(int $maxRetries, int $baseDelayMs, float $backoffMultiplier, int $maxDelayMs)
    {
        $this->maxRetries = $maxRetries;
        $this->baseDelayMs = $baseDelayMs;
        $this->backoffMultiplier = $backoffMultiplier;
        $this->maxDelayMs = $maxDelayMs;
        $this->retryableExceptions = [
            \SimpleMDB\Exceptions\ConnectionException::class,
            \SimpleMDB\Exceptions\QueryException::class
        ];
        $this->retryableErrorCodes = [1205, 1213, 2006, 2013, 1040, 1203];
    }

    public function execute(callable $operation, array $args = []): mixed
    {
        $operationKey = $this->getOperationKey($operation);
        
        // Check circuit breaker
        if ($this->isCircuitOpen($operationKey)) {
            throw new \Exception("Circuit breaker is open for operation: $operationKey");
        }

        $lastException = null;
        
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $result = $operation(...$args);
                $this->recordSuccess($operationKey);
                return $result;
            } catch (\Exception $e) {
                $lastException = $e;
                $this->recordFailure($operationKey);
                
                if ($attempt >= $this->maxRetries) {
                    break;
                }
                
                if (!$this->isRetryableException($e)) {
                    break;
                }
                
                $delayMs = $this->calculateDelay($attempt);
                usleep($delayMs * 1000);
            }
        }
        
        throw $lastException;
    }

    private function getOperationKey(callable $operation): string
    {
        if (is_array($operation)) {
            return implode('::', $operation);
        }
        return 'anonymous';
    }

    private function isCircuitOpen(string $operationKey): bool
    {
        if (!isset($this->failureCounts[$operationKey])) {
            return false;
        }

        $failureCount = $this->failureCounts[$operationKey];
        $lastFailureTime = $this->lastFailureTimes[$operationKey] ?? 0;
        
        if ($failureCount >= $this->circuitBreakerThreshold) {
            if (time() - $lastFailureTime < $this->circuitBreakerTimeout) {
                return true;
            } else {
                // Reset circuit breaker
                $this->failureCounts[$operationKey] = 0;
                return false;
            }
        }
        
        return false;
    }

    private function recordSuccess(string $operationKey): void
    {
        $this->failureCounts[$operationKey] = 0;
    }

    private function recordFailure(string $operationKey): void
    {
        $this->failureCounts[$operationKey] = ($this->failureCounts[$operationKey] ?? 0) + 1;
        $this->lastFailureTimes[$operationKey] = time();
    }

    public function executeWithSettings(callable $operation, array $args = [], int $maxRetries = null, int $baseDelayMs = null): mixed
    {
        $originalMaxRetries = $this->maxRetries;
        $originalBaseDelayMs = $this->baseDelayMs;
        
        if ($maxRetries !== null) {
            $this->maxRetries = $maxRetries;
        }
        if ($baseDelayMs !== null) {
            $this->baseDelayMs = $baseDelayMs;
        }
        
        try {
            return $this->execute($operation, $args);
        } finally {
            $this->maxRetries = $originalMaxRetries;
            $this->baseDelayMs = $originalBaseDelayMs;
        }
    }

    public function addRetryableException(string $exceptionClass): self
    {
        $this->retryableExceptions[] = $exceptionClass;
        return $this;
    }

    public function addRetryableErrorCode(int $errorCode): self
    {
        $this->retryableErrorCodes[] = $errorCode;
        return $this;
    }

    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    public function setBaseDelay(int $baseDelayMs): self
    {
        $this->baseDelayMs = $baseDelayMs;
        return $this;
    }

    public function setBackoffMultiplier(float $multiplier): self
    {
        $this->backoffMultiplier = $multiplier;
        return $this;
    }

    public function setMaxDelay(int $maxDelayMs): self
    {
        $this->maxDelayMs = $maxDelayMs;
        return $this;
    }

    public function getMaxRetries(): int { return $this->maxRetries; }
    public function getBaseDelay(): int { return $this->baseDelayMs; }
    public function getBackoffMultiplier(): float { return $this->backoffMultiplier; }
    public function getMaxDelay(): int { return $this->maxDelayMs; }
    public function getRetryableExceptions(): array { return $this->retryableExceptions; }
    public function getRetryableErrorCodes(): array { return $this->retryableErrorCodes; }

    public function isRetryableException(\Exception $e): bool
    {
        foreach ($this->retryableExceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }
        return $this->isTransientDatabaseError($e);
    }

    public function isTransientDatabaseError(\Exception $e): bool
    {
        if ($e instanceof \SimpleMDB\Exceptions\QueryException) {
            $errorCode = $e->getCode();
            return in_array($errorCode, $this->retryableErrorCodes);
        }
        return false;
    }

    public function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelayMs * pow($this->backoffMultiplier, $attempt);
        return min($delay, $this->maxDelayMs);
    }

    public function reset(): self
    {
        $this->failureCounts = [];
        $this->lastFailureTimes = [];
        return $this;
    }

    public function getRetryStats(): array
    {
        return [
            'failure_counts' => $this->failureCounts,
            'last_failure_times' => $this->lastFailureTimes,
            'circuit_breaker_threshold' => $this->circuitBreakerThreshold,
            'circuit_breaker_timeout' => $this->circuitBreakerTimeout,
        ];
    }
}

/**
 * Factory for creating different implementations
 */
class DatabaseComponentFactory
{
    public static function createQueryBuilder(string $type = 'default'): QueryBuilderInterface
    {
        return match($type) {
            'mongo' => new MongoStyleQueryBuilder(),
            'default' => \SimpleMDB\SimpleQuery::create(),
            default => throw new \InvalidArgumentException("Unknown query builder type: $type")
        };
    }

    public static function createCache(string $type = 'default'): CacheInterface
    {
        return match($type) {
            'redis' => new RedisCache(new \Redis()),
            'memory' => new \SimpleMDB\MemoryCache(),
            'file' => new \SimpleMDB\FileCache('/tmp/cache'),
            default => throw new \InvalidArgumentException("Unknown cache type: $type")
        };
    }

    public static function createRetryPolicy(string $type = 'default'): RetryPolicyInterface
    {
        return match($type) {
            'circuit_breaker' => CircuitBreakerRetryPolicy::create(),
            'exponential' => \SimpleMDB\Retry\RetryPolicy::create(3, 100, 2.0, 5000),
            default => throw new \InvalidArgumentException("Unknown retry policy type: $type")
        };
    }
}

// Example usage
echo "🔧 Interface Extensibility Example\n";
echo "===================================\n\n";

try {
    // Create database connection
    $db = new SimpleMySQLi('localhost', 'root', '', 'test_db');
    
    echo "✅ Database connection established\n\n";

    // Example 1: Using custom query builder
    echo "📝 Example 1: Custom Query Builder (MongoDB-style)\n";
    echo "------------------------------------------------\n";
    
    $mongoQuery = DatabaseComponentFactory::createQueryBuilder('mongo');
    $mongoQuery->from('users')
               ->select(['id', 'name', 'email'])
               ->where('is_active = ?', [true])
               ->orderBy('created_at', 'DESC')
               ->limit(10);
    
    echo "Generated SQL: " . $mongoQuery->toSql() . "\n\n";

    // Example 2: Using custom cache implementation
    echo "📝 Example 2: Custom Cache Implementation (Redis)\n";
    echo "------------------------------------------------\n";
    
    try {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        
        $cache = DatabaseComponentFactory::createCache('redis');
        $cache->set('test_key', ['data' => 'test_value'], 3600);
        
        $cachedData = $cache->get('test_key');
        echo "Cached data: " . json_encode($cachedData) . "\n";
        
        $cache->delete('test_key');
        echo "Cache cleared\n\n";
    } catch (\Exception $e) {
        echo "Redis not available, using memory cache\n";
        $cache = DatabaseComponentFactory::createCache('memory');
        $cache->set('test_key', ['data' => 'test_value'], 3600);
        echo "Using memory cache instead\n\n";
    }

    // Example 3: Using custom retry policy
    echo "📝 Example 3: Custom Retry Policy (Circuit Breaker)\n";
    echo "--------------------------------------------------\n";
    
    $retryPolicy = DatabaseComponentFactory::createRetryPolicy('circuit_breaker');
    
    // Simulate a failing operation
    $failingOperation = function() {
        static $attempts = 0;
        $attempts++;
        if ($attempts < 3) {
            throw new \SimpleMDB\Exceptions\ConnectionException("Simulated connection failure");
        }
        return "Success after $attempts attempts";
    };
    
    try {
        $result = $retryPolicy->execute($failingOperation);
        echo "Operation result: $result\n";
    } catch (\Exception $e) {
        echo "Operation failed: " . $e->getMessage() . "\n";
    }
    
    echo "Retry stats: " . json_encode($retryPolicy->getRetryStats()) . "\n\n";

    // Example 4: Strategy pattern with interfaces
    echo "📝 Example 4: Strategy Pattern with Interfaces\n";
    echo "-----------------------------------------------\n";
    
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
                echo "Cache hit for query\n";
                return $cached;
            }
            
            echo "Cache miss, executing query\n";
            
            // Execute with retry
            $result = $this->retryPolicy->execute(
                fn() => $this->queryBuilder->execute($sql, $params)
            );
            
            // Cache result
            $this->cache->set($cacheKey, $result, 3600);
            
            return $result;
        }
    }
    
    $strategy = new QueryExecutionStrategy(
        DatabaseComponentFactory::createQueryBuilder('default'),
        DatabaseComponentFactory::createRetryPolicy('circuit_breaker'),
        DatabaseComponentFactory::createCache('memory')
    );
    
    echo "Strategy pattern implemented successfully\n\n";

    // Example 5: Interface-based testing
    echo "📝 Example 5: Interface-Based Testing\n";
    echo "--------------------------------------\n";
    
    class MockQueryBuilder implements QueryBuilderInterface
    {
        private array $expectedResults = [];
        
        public function expectResult(string $sql, mixed $result): self
        {
            $this->expectedResults[$sql] = $result;
            return $this;
        }
        
        public function execute(DatabaseInterface $db, string $fetchType = 'assoc')
        {
            $sql = $this->toSql();
            return $this->expectedResults[$sql] ?? [];
        }
        
        // Implement other methods...
        public static function create(): self { return new self(); }
        public function select(array $fields): self { return $this; }
        public function from(string $table): self { return $this; }
        public function where(string $condition, array $params = []): self { return $this; }
        public function toSql(): string { return "SELECT * FROM test"; }
        public function getParams(): array { return []; }
        public function selectWithAlias(array $fieldsWithAliases): self { return $this; }
        public function count(string $field = '*', string $alias = 'count'): self { return $this; }
        public function sum(string $field, string $alias = 'sum'): self { return $this; }
        public function avg(string $field, string $alias = 'avg'): self { return $this; }
        public function fromWithAlias(string $table, string $alias): self { return $this; }
        public function addCondition(bool $condition, callable $callback): self { return $this; }
        public function join(string $table, string $condition, string $type = 'INNER'): self { return $this; }
        public function leftJoin(string $table, string $condition): self { return $this; }
        public function rightJoin(string $table, string $condition): self { return $this; }
        public function fullJoin(string $table, string $condition): self { return $this; }
        public function groupBy(array $fields): self { return $this; }
        public function having(string $condition, array $params = []): self { return $this; }
        public function orderBy(string $field, string $direction = 'ASC'): self { return $this; }
        public function limit(int $count, int $offset = 0): self { return $this; }
        public function paginate(int $limit, int $offset = 0): self { return $this; }
        public function union(self $query, bool $all = false): self { return $this; }
        public function subquery(self $query, string $alias): self { return $this; }
        public function with(string $name, QueryBuilderInterface $query): self { return $this; }
        public function window(string $name, array $partitionBy = [], array $orderBy = []): self { return $this; }
        public function over(?string $windowName = null, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function rowNumber(array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function rank(array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function denseRank(array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function lag(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function lead(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function firstValue(string $column, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function lastValue(string $column, array $partitionBy = [], array $orderBy = []): \SimpleMDB\Expression { return new \SimpleMDB\Expression(''); }
        public function insert(array $data): self { return $this; }
        public function into(string $table): self { return $this; }
        public function update(): self { return $this; }
        public function set(array $data): self { return $this; }
        public function table(string $table): self { return $this; }
        public function delete(): self { return $this; }
        public function executeInTransaction(DatabaseInterface $db, string $fetchType = 'assoc') { return null; }
        public function enableCache(bool $enable = true): self { return $this; }
        public static function escapeIdentifier(string $identifier): string { return $identifier; }
    }
    
    $mockQueryBuilder = new MockQueryBuilder();
    $mockQueryBuilder->expectResult("SELECT * FROM test", [['id' => 1, 'name' => 'Test']]);
    
    $result = $mockQueryBuilder->execute($db);
    echo "Mock test result: " . json_encode($result) . "\n\n";

    echo "✅ All interface extensibility examples completed successfully!\n";
    echo "🎯 The interface-based architecture enables:\n";
    echo "   - Easy swapping of implementations\n";
    echo "   - Custom implementations for specific needs\n";
    echo "   - Excellent testability with mocks\n";
    echo "   - Framework integration capabilities\n";
    echo "   - Plugin architecture support\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 