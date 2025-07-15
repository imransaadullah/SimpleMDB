# Testing Guide

Complete guide to testing SimpleMDB applications. Learn how to write reliable tests, set up test databases, and validate your database operations with confidence.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [Test Environment Setup](#test-environment-setup)
- [Basic Testing Patterns](#basic-testing-patterns)
- [Schema Testing](#schema-testing)
- [Query Testing](#query-testing)
- [Migration Testing](#migration-testing)
- [Performance Testing](#performance-testing)
- [Integration Testing](#integration-testing)
- [Best Practices](#best-practices)

---

## ⚡ Quick Start

### Simple Connection Test

```php
<?php
// test_connection.php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Exceptions\ConnectionException;

function testConnection()
{
    try {
        // Test database connection
        $db = DatabaseFactory::create('pdo', 'localhost', 'root', '', 'test_db');
        
        if ($db->isConnected()) {
            echo "✅ Database connection successful\n";
            return true;
        } else {
            echo "❌ Database connection failed\n";
            return false;
        }
    } catch (ConnectionException $e) {
        echo "❌ Connection error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run test
if (testConnection()) {
    echo "🎉 SimpleMDB is working correctly!\n";
} else {
    echo "🚨 SimpleMDB setup needs attention.\n";
}
```

### Basic CRUD Test

```php
<?php
// test_crud.php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder;
use SimpleMDB\SimpleQuery;

function testCRUD()
{
    $db = DatabaseFactory::create('pdo', 'localhost', 'root', '', 'test_db');
    $schema = new SchemaBuilder($db);
    
    echo "Running CRUD tests...\n\n";
    
    // 1. CREATE - Schema Creation
    echo "1. Testing schema creation...\n";
    $schema->dropIfExists('test_users');
    $schema->increments('id')
           ->string('name', 100)
           ->string('email', 150)
           ->boolean('is_active')->default(true)
           ->timestamps()
           ->createTable('test_users');
    echo "   ✅ Table created successfully\n";
    
    // 2. CREATE - Data Insertion
    echo "2. Testing data insertion...\n";
    $userId = SimpleQuery::create()
        ->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true
        ])
        ->into('test_users')
        ->execute($db);
    echo "   ✅ User inserted with ID: $userId\n";
    
    // 3. READ - Data Selection
    echo "3. Testing data selection...\n";
    $users = SimpleQuery::create()
        ->select(['*'])
        ->from('test_users')
        ->where('id = ?', [$userId])
        ->execute($db);
    
    if (count($users) === 1 && $users[0]['name'] === 'John Doe') {
        echo "   ✅ Data retrieved successfully\n";
    } else {
        echo "   ❌ Data retrieval failed\n";
        return false;
    }
    
    // 4. UPDATE - Data Modification
    echo "4. Testing data update...\n";
    $affected = SimpleQuery::create()
        ->update('test_users')
        ->set(['name' => 'Jane Doe'])
        ->where('id = ?', [$userId])
        ->execute($db);
    
    if ($affected === 1) {
        echo "   ✅ Data updated successfully\n";
    } else {
        echo "   ❌ Data update failed\n";
        return false;
    }
    
    // 5. DELETE - Data Removal
    echo "5. Testing data deletion...\n";
    $deleted = SimpleQuery::create()
        ->delete()
        ->from('test_users')
        ->where('id = ?', [$userId])
        ->execute($db);
    
    if ($deleted === 1) {
        echo "   ✅ Data deleted successfully\n";
    } else {
        echo "   ❌ Data deletion failed\n";
        return false;
    }
    
    // Cleanup
    $schema->dropTable('test_users');
    echo "   ✅ Test table cleaned up\n";
    
    return true;
}

// Run test
if (testCRUD()) {
    echo "\n🎉 All CRUD operations working correctly!\n";
} else {
    echo "\n🚨 CRUD operations failed!\n";
}
```

---

## 🔧 Test Environment Setup

### Test Database Configuration

```php
<?php
// config/test_database.php

return [
    'test' => [
        'host' => 'localhost',
        'username' => 'test_user',
        'password' => 'test_password',
        'database' => 'simplemdb_test',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ],
    'ci' => [
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'database' => 'simplemdb_ci_test',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ]
];
```

### Test Database Setup Script

```php
<?php
// setup_test_db.php

require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;

function setupTestDatabase()
{
    echo "Setting up test database...\n";
    
    try {
        // Connect to MySQL without selecting database
        $db = DatabaseFactory::create('pdo', 'localhost', 'root', '');
        
        // Create test database
        $db->query("CREATE DATABASE IF NOT EXISTS simplemdb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Test database created\n";
        
        // Create test user
        $db->query("CREATE USER IF NOT EXISTS 'test_user'@'localhost' IDENTIFIED BY 'test_password'");
        $db->query("GRANT ALL PRIVILEGES ON simplemdb_test.* TO 'test_user'@'localhost'");
        $db->query("FLUSH PRIVILEGES");
        echo "✅ Test user created\n";
        
        return true;
    } catch (Exception $e) {
        echo "❌ Setup failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run setup
if (setupTestDatabase()) {
    echo "🎉 Test environment ready!\n";
} else {
    echo "🚨 Test environment setup failed!\n";
}
```

### PHPUnit Integration

```php
<?php
// tests/TestCase.php

use PHPUnit\Framework\TestCase as BaseTestCase;
use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder;

abstract class TestCase extends BaseTestCase
{
    protected $db;
    protected $schema;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load test configuration
        $config = require __DIR__ . '/../config/test_database.php';
        $testConfig = $config['test'];
        
        // Create database connection
        $this->db = DatabaseFactory::create(
            'pdo',
            $testConfig['host'],
            $testConfig['username'],
            $testConfig['password'],
            $testConfig['database'],
            $testConfig['options']
        );
        
        $this->schema = new SchemaBuilder($this->db);
        
        // Start transaction for test isolation
        $this->db->beginTransaction();
    }
    
    protected function tearDown(): void
    {
        // Rollback transaction to clean up test data
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        
        parent::tearDown();
    }
    
    /**
     * Create a test table for testing
     */
    protected function createTestTable(string $tableName = 'test_table'): void
    {
        $this->schema->dropIfExists($tableName);
        $this->schema->increments('id')
                     ->string('name', 100)
                     ->string('email', 150)
                     ->boolean('is_active')->default(true)
                     ->timestamps()
                     ->createTable($tableName);
    }
}
```

---

## 🧪 Basic Testing Patterns

### Testing Schema Creation

```php
<?php
// tests/SchemaTest.php

use SimpleMDB\SimpleQuery;

class SchemaTest extends TestCase
{
    public function testTableCreation()
    {
        // Create test table
        $this->createTestTable('users');
        
        // Assert table exists
        $this->assertTrue($this->schema->hasTable('users'));
        
        // Assert columns exist
        $this->assertTrue($this->schema->hasColumn('users', 'id'));
        $this->assertTrue($this->schema->hasColumn('users', 'name'));
        $this->assertTrue($this->schema->hasColumn('users', 'email'));
        $this->assertTrue($this->schema->hasColumn('users', 'is_active'));
    }
    
    public function testColumnTypes()
    {
        $this->schema->increments('id')
                     ->string('name', 100)
                     ->decimal('price', 10, 2)
                     ->boolean('is_active')
                     ->json('metadata')
                     ->timestamps()
                     ->createTable('products');
        
        $columns = $this->schema->getColumns('products');
        
        // Assert column types
        $this->assertEquals('int', $columns['id']['type']);
        $this->assertEquals('varchar', $columns['name']['type']);
        $this->assertEquals('decimal', $columns['price']['type']);
        $this->assertEquals('tinyint', $columns['is_active']['type']);
        $this->assertEquals('json', $columns['metadata']['type']);
    }
    
    public function testUniqueConstraints()
    {
        $this->schema->increments('id')
                     ->string('email', 150)->unique()
                     ->createTable('unique_test');
        
        // Insert first record
        $id1 = SimpleQuery::create()
            ->insert(['email' => 'test@example.com'])
            ->into('unique_test')
            ->execute($this->db);
        
        $this->assertIsInt($id1);
        
        // Try to insert duplicate - should fail
        $this->expectException(PDOException::class);
        
        SimpleQuery::create()
            ->insert(['email' => 'test@example.com'])
            ->into('unique_test')
            ->execute($this->db);
    }
}
```

### Testing Query Operations

```php
<?php
// tests/QueryTest.php

class QueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTable('users');
    }
    
    public function testInsertAndSelect()
    {
        // Insert test data
        $userId = SimpleQuery::create()
            ->insert([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'is_active' => true
            ])
            ->into('users')
            ->execute($this->db);
        
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        
        // Select and verify data
        $users = SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]['name']);
        $this->assertEquals('john@example.com', $users[0]['email']);
        $this->assertEquals(1, $users[0]['is_active']);
    }
    
    public function testUpdateOperation()
    {
        // Insert test data
        $userId = SimpleQuery::create()
            ->insert(['name' => 'John Doe', 'email' => 'john@example.com'])
            ->into('users')
            ->execute($this->db);
        
        // Update data
        $affected = SimpleQuery::create()
            ->update('users')
            ->set(['name' => 'Jane Doe'])
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertEquals(1, $affected);
        
        // Verify update
        $user = SimpleQuery::create()
            ->select(['name'])
            ->from('users')
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertEquals('Jane Doe', $user[0]['name']);
    }
    
    public function testDeleteOperation()
    {
        // Insert test data
        $userId = SimpleQuery::create()
            ->insert(['name' => 'John Doe', 'email' => 'john@example.com'])
            ->into('users')
            ->execute($this->db);
        
        // Delete data
        $deleted = SimpleQuery::create()
            ->delete()
            ->from('users')
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertEquals(1, $deleted);
        
        // Verify deletion
        $users = SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertCount(0, $users);
    }
    
    public function testComplexQueries()
    {
        // Insert multiple test records
        $userIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $userIds[] = SimpleQuery::create()
                ->insert([
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'is_active' => $i % 2 === 0
                ])
                ->into('users')
                ->execute($this->db);
        }
        
        // Test WHERE clause
        $activeUsers = SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('is_active = ?', [1])
            ->execute($this->db);
        
        $this->assertCount(2, $activeUsers);
        
        // Test ORDER BY
        $orderedUsers = SimpleQuery::create()
            ->select(['name'])
            ->from('users')
            ->orderBy('name ASC')
            ->execute($this->db);
        
        $this->assertEquals('User 1', $orderedUsers[0]['name']);
        
        // Test LIMIT
        $limitedUsers = SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->limit(2)
            ->execute($this->db);
        
        $this->assertCount(2, $limitedUsers);
    }
}
```

---

## 📊 Performance Testing

### Query Performance Test

```php
<?php
// tests/PerformanceTest.php

class PerformanceTest extends TestCase
{
    public function testBulkInsertPerformance()
    {
        $this->createTestTable('performance_test');
        
        $startTime = microtime(true);
        
        // Insert 1000 records
        for ($i = 0; $i < 1000; $i++) {
            SimpleQuery::create()
                ->insert([
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'is_active' => $i % 2 === 0
                ])
                ->into('performance_test')
                ->execute($this->db);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        echo "Bulk insert (1000 records): {$duration}s\n";
        
        // Assert reasonable performance (adjust threshold as needed)
        $this->assertLessThan(5.0, $duration, "Bulk insert took too long: {$duration}s");
    }
    
    public function testQueryPerformance()
    {
        $this->createTestTable('performance_test');
        
        // Insert test data
        for ($i = 0; $i < 100; $i++) {
            SimpleQuery::create()
                ->insert([
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'is_active' => $i % 2 === 0
                ])
                ->into('performance_test')
                ->execute($this->db);
        }
        
        $startTime = microtime(true);
        
        // Perform complex query
        $results = SimpleQuery::create()
            ->select(['*'])
            ->from('performance_test')
            ->where('is_active = ?', [1])
            ->orderBy('name ASC')
            ->limit(20)
            ->execute($this->db);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        echo "Complex query: {$duration}s\n";
        
        $this->assertLessThan(0.1, $duration, "Query took too long: {$duration}s");
        $this->assertCount(20, $results);
    }
}
```

### Memory Usage Test

```php
<?php
// tests/MemoryTest.php

class MemoryTest extends TestCase
{
    public function testMemoryUsage()
    {
        $this->createTestTable('memory_test');
        
        $initialMemory = memory_get_usage();
        
        // Insert and select large amount of data
        for ($i = 0; $i < 1000; $i++) {
            SimpleQuery::create()
                ->insert([
                    'name' => str_repeat("User $i ", 10), // Larger strings
                    'email' => "user$i@example.com"
                ])
                ->into('memory_test')
                ->execute($this->db);
        }
        
        $afterInsertMemory = memory_get_usage();
        
        // Select all data
        $results = SimpleQuery::create()
            ->select(['*'])
            ->from('memory_test')
            ->execute($this->db);
        
        $finalMemory = memory_get_usage();
        
        $insertMemoryIncrease = $afterInsertMemory - $initialMemory;
        $selectMemoryIncrease = $finalMemory - $afterInsertMemory;
        
        echo "Memory increase after inserts: " . number_format($insertMemoryIncrease) . " bytes\n";
        echo "Memory increase after select: " . number_format($selectMemoryIncrease) . " bytes\n";
        
        // Assert reasonable memory usage (adjust thresholds as needed)
        $this->assertLessThan(50 * 1024 * 1024, $insertMemoryIncrease, "Insert memory usage too high");
        $this->assertLessThan(20 * 1024 * 1024, $selectMemoryIncrease, "Select memory usage too high");
        
        $this->assertCount(1000, $results);
    }
}
```

---

## 🔧 Integration Testing

### Full Application Test

```php
<?php
// tests/IntegrationTest.php

class IntegrationTest extends TestCase
{
    public function testCompleteUserWorkflow()
    {
        // Create users table
        $this->schema->increments('id')
                     ->string('username', 50)->unique()
                     ->string('email', 150)->unique()
                     ->string('password_hash', 255)
                     ->boolean('is_active')->default(true)
                     ->datetime('last_login')->nullable()
                     ->timestamps()
                     ->createTable('users');
        
        // Create posts table
        $this->schema->increments('id')
                     ->string('title', 255)
                     ->text('content')
                     ->integer('user_id')->unsigned()
                     ->timestamps()
                     ->foreign('user_id')
                         ->references('id')
                         ->on('users')
                         ->onDelete('cascade')
                     ->createTable('posts');
        
        // 1. User Registration
        $userId = SimpleQuery::create()
            ->insert([
                'username' => 'johndoe',
                'email' => 'john@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'is_active' => true
            ])
            ->into('users')
            ->execute($this->db);
        
        $this->assertIsInt($userId);
        
        // 2. User Login (simulate)
        $loginUser = SimpleQuery::create()
            ->select(['id', 'username', 'password_hash'])
            ->from('users')
            ->where('email = ?', ['john@example.com'])
            ->execute($this->db);
        
        $this->assertCount(1, $loginUser);
        $this->assertTrue(password_verify('password123', $loginUser[0]['password_hash']));
        
        // Update last login
        SimpleQuery::create()
            ->update('users')
            ->set(['last_login' => date('Y-m-d H:i:s')])
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        // 3. Create Posts
        $postTitles = ['First Post', 'Second Post', 'Third Post'];
        $postIds = [];
        
        foreach ($postTitles as $title) {
            $postIds[] = SimpleQuery::create()
                ->insert([
                    'title' => $title,
                    'content' => "Content for $title",
                    'user_id' => $userId
                ])
                ->into('posts')
                ->execute($this->db);
        }
        
        $this->assertCount(3, $postIds);
        
        // 4. Query User's Posts
        $userPosts = SimpleQuery::create()
            ->select(['posts.title', 'users.username'])
            ->from('posts')
            ->join('users', 'posts.user_id = users.id')
            ->where('users.id = ?', [$userId])
            ->orderBy('posts.created_at ASC')
            ->execute($this->db);
        
        $this->assertCount(3, $userPosts);
        $this->assertEquals('First Post', $userPosts[0]['title']);
        $this->assertEquals('johndoe', $userPosts[0]['username']);
        
        // 5. User Deactivation
        SimpleQuery::create()
            ->update('users')
            ->set(['is_active' => false])
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        // Verify deactivation
        $deactivatedUser = SimpleQuery::create()
            ->select(['is_active'])
            ->from('users')
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertEquals(0, $deactivatedUser[0]['is_active']);
        
        // 6. Cascade Delete Test
        SimpleQuery::create()
            ->delete()
            ->from('users')
            ->where('id = ?', [$userId])
            ->execute($this->db);
        
        // Verify posts were cascade deleted
        $remainingPosts = SimpleQuery::create()
            ->select(['*'])
            ->from('posts')
            ->where('user_id = ?', [$userId])
            ->execute($this->db);
        
        $this->assertCount(0, $remainingPosts);
    }
}
```

---

## 🎯 Best Practices

### 1. Test Isolation

```php
// ✅ Always use transactions for test isolation
protected function setUp(): void
{
    parent::setUp();
    $this->db->beginTransaction();
}

protected function tearDown(): void
{
    $this->db->rollback();
    parent::tearDown();
}
```

### 2. Descriptive Test Names

```php
// ✅ Clear, descriptive test method names
public function testUserCannotRegisterWithDuplicateEmail()
public function testPostBelongsToCorrectUser()
public function testInactiveUsersCannotLogin()

// ❌ Vague test names
public function testUser()
public function testDatabase()
public function testQuery()
```

### 3. Test Data Management

```php
// ✅ Use factory methods for test data
protected function createTestUser(array $overrides = []): int
{
    $defaults = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_active' => true
    ];
    
    $data = array_merge($defaults, $overrides);
    
    return SimpleQuery::create()
        ->insert($data)
        ->into('users')
        ->execute($this->db);
}

// Use in tests
public function testUserCreation()
{
    $userId = $this->createTestUser(['name' => 'John Doe']);
    // ... test logic
}
```

### 4. Error Testing

```php
// ✅ Test error conditions
public function testDuplicateEmailThrowsException()
{
    $this->createTestUser(['email' => 'test@example.com']);
    
    $this->expectException(PDOException::class);
    $this->createTestUser(['email' => 'test@example.com']);
}

public function testInvalidDataTypesAreRejected()
{
    $this->expectException(InvalidArgumentException::class);
    
    SimpleQuery::create()
        ->insert(['is_active' => 'not_a_boolean'])
        ->into('users')
        ->execute($this->db);
}
```

### 5. Performance Assertions

```php
// ✅ Include performance tests
public function testQueryPerformanceIsAcceptable()
{
    $startTime = microtime(true);
    
    // Perform operation
    $results = SimpleQuery::create()
        ->select(['*'])
        ->from('large_table')
        ->execute($this->db);
    
    $duration = microtime(true) - $startTime;
    
    $this->assertLessThan(1.0, $duration, "Query took too long: {$duration}s");
}
```

---

## 🚀 Running Tests

### Command Line Testing

```bash
# Run all tests
php vendor/bin/phpunit tests/

# Run specific test file
php vendor/bin/phpunit tests/SchemaTest.php

# Run with coverage
php vendor/bin/phpunit --coverage-html coverage tests/

# Run performance tests only
php vendor/bin/phpunit tests/PerformanceTest.php
```

### CI/CD Integration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: simplemdb_ci_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.1
        extensions: pdo, pdo_mysql
    
    - name: Install dependencies
      run: composer install
    
    - name: Run tests
      run: php vendor/bin/phpunit tests/
      env:
        DB_HOST: 127.0.0.1
        DB_USERNAME: root
        DB_PASSWORD: root
        DB_DATABASE: simplemdb_ci_test
```

---

## 📞 Getting Help

**Testing Issues:**
- 📚 [Review Installation Guide](docs/installation.md)
- 🔍 [Check GitHub Issues](https://github.com/imrnansaadullah/SimpleMDB/issues)
- 💬 [Join Discord Community](https://discord.gg/simplemdb)

**Next Steps:**
- 👉 [Learn Basic Concepts](docs/basic-concepts.md)
- 👉 [Master Schema Builder](docs/schema-builder.md)
- 👉 [Explore Query Builder](docs/query-builder.md) 