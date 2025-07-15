# Basic Concepts

Master SimpleMDB fundamentals to build enterprise applications confidently. This guide covers core concepts that every SimpleMDB developer needs to understand.

## 🏗️ Architecture Overview

SimpleMDB follows a clean, Laravel-inspired architecture:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Your App     │    │   SimpleMDB     │    │   Database      │
│                │────│                 │────│                 │
│ • Controllers  │    │ • Query Builder │    │ • MySQL         │
│ • Models       │    │ • Schema Builder│    │ • MariaDB       │
│ • Services     │    │ • Migrations    │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 🔗 Database Connection

### Connection Factory
SimpleMDB uses a factory pattern for database connections:

```php
use SimpleMDB\DatabaseFactory;

// Create PDO connection (recommended)
$db = DatabaseFactory::create('pdo', $host, $user, $pass, $database);

// Create MySQLi connection (legacy support)
$db = DatabaseFactory::create('mysqli', $host, $user, $pass, $database);
```

### Connection Testing
```php
// Check if connected
if ($db->isConnected()) {
    echo "Connected to database";
}

// Test connection with query
try {
    $result = $db->query("SELECT 1");
    echo "Connection working properly";
} catch (Exception $e) {
    echo "Connection issue: " . $e->getMessage();
}
```

## 🏗️ Schema Builder

The Schema Builder creates and modifies database tables with a fluent interface:

### Basic Table Creation
```php
use SimpleMDB\SchemaBuilder;

$schema = new SchemaBuilder($db);

// Create table with fluent syntax
$schema->increments('id')                    // Primary key
       ->string('name', 100)                 // VARCHAR(100)
       ->string('email', 150)->unique()      // Unique email
       ->text('description')->nullable()     // Nullable text
       ->timestamps()                        // created_at, updated_at
       ->createTable('users');
```

### Column Types and Modifiers
```php
// Data types
$schema->increments('id')           // AUTO_INCREMENT PRIMARY KEY
       ->string('name', 100)        // VARCHAR(100)
       ->text('description')        // TEXT
       ->integer('age')             // INT
       ->decimal('price', 8, 2)     // DECIMAL(8,2)
       ->boolean('is_active')       // TINYINT(1)
       ->json('metadata')           // JSON
       ->timestamp('created_at')    // TIMESTAMP

// Modifiers
       ->nullable()                 // Allow NULL
       ->default('value')           // Default value
       ->unique()                   // Unique constraint
       ->comment('Description')     // Column comment
       ->createTable('products');
```

## 🔍 Query Builder

SimpleMDB provides a powerful, Laravel-like query builder:

### Basic Queries
```php
use SimpleMDB\SimpleQuery;

// SELECT queries
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->orderBy('created_at DESC')
    ->execute($db);

// INSERT queries
$userId = SimpleQuery::create()
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_active' => true
    ])
    ->into('users')
    ->execute($db);

// UPDATE queries
$affected = SimpleQuery::create()
    ->update('users')
    ->set(['last_login' => date('Y-m-d H:i:s')])
    ->where('id = ?', [$userId])
    ->execute($db);

// DELETE queries
$deleted = SimpleQuery::create()
    ->delete()
    ->from('users')
    ->where('is_active = ?', [false])
    ->execute($db);
```

### Advanced Query Features
```php
// JOIN queries
$posts = SimpleQuery::create()
    ->select(['posts.title', 'users.name as author'])
    ->from('posts')
    ->join('users', 'posts.user_id = users.id')
    ->where('posts.published = ?', [true])
    ->execute($db);

// Subqueries
$activeUsers = SimpleQuery::create()
    ->select(['id'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->toSQL();

$posts = SimpleQuery::create()
    ->select(['*'])
    ->from('posts')
    ->whereIn('user_id', $activeUsers)
    ->execute($db);

// Aggregations
$stats = SimpleQuery::create()
    ->select(['COUNT(*) as total', 'AVG(age) as avg_age'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->execute($db);
```

## 🚀 Migrations

Migrations provide version control for your database schema:

### Creating Migrations
```php
use SimpleMDB\Migrations\MigrationManager;

$migrations = new MigrationManager($db);

// Create new migration (auto-generates intelligent template)
$migrations->create('create_blog_posts_table');
// ✨ Detects "blog posts" context and generates appropriate structure

$migrations->create('add_status_to_users_table');
// ✨ Detects table modification and generates alter table structure
```

### Migration Structure
```php
<?php
// Migration_20240101_120000_CreateBlogPostsTable.php

use SimpleMDB\Migrations\Migration;
use SimpleMDB\SchemaBuilder;

class CreateBlogPostsTable extends Migration
{
    public function up(SchemaBuilder $schema)
    {
        $schema->increments('id')
               ->string('title', 255)
               ->text('content')
               ->string('status', 20)->default('draft')
               ->integer('user_id')->unsigned()
               ->timestamps()
               ->foreign('user_id')->references('id')->on('users')
               ->createTable('blog_posts');
    }

    public function down(SchemaBuilder $schema)
    {
        $schema->dropTable('blog_posts');
    }
}
```

### Running Migrations
```php
// Run all pending migrations
$migrations->migrate();

// Rollback last migration
$migrations->rollback();

// Check migration status
$status = $migrations->status();
```

## 🛡️ Security & Validation

SimpleMDB prioritizes security with multiple protection layers:

### SQL Injection Prevention
```php
// ✅ SAFE - Using parameter binding
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('email = ?', [$userInput])  // Automatically escaped
    ->execute($db);

// ❌ DANGEROUS - Raw SQL (avoid)
$query = "SELECT * FROM users WHERE email = '$userInput'";
```

### Input Validation
```php
use SimpleMDB\QuerySanitizer;

$sanitizer = new QuerySanitizer();

// Validate data types
$cleanData = $sanitizer->validate([
    'name' => ['value' => $input['name'], 'type' => 'string', 'max' => 100],
    'age' => ['value' => $input['age'], 'type' => 'integer', 'min' => 0],
    'email' => ['value' => $input['email'], 'type' => 'email']
]);
```

## 📊 Data Types

SimpleMDB supports 25+ modern data types for every use case:

### Common Types
```php
$schema->increments('id')                // Auto-increment primary key
       ->string('name', 100)             // VARCHAR with length
       ->text('description')             // TEXT for long content
       ->integer('quantity')             // Integer numbers
       ->decimal('price', 10, 2)         // Decimal for money
       ->boolean('is_active')            // Boolean true/false
       ->date('birth_date')              // Date only
       ->datetime('created_at')          // Date and time
       ->json('metadata');               // JSON documents
```

### Modern Types
```php
$schema->uuid('external_id')             // UUID storage
       ->ipAddress('client_ip')          // IPv4/IPv6 addresses
       ->macAddress('device_mac')        // MAC addresses
       ->url('website')                  // URL validation
       ->point('coordinates')            // Geographic points
       ->polygon('boundaries')           // Geographic polygons
       ->enum('status', ['active', 'inactive']);  // Enumerated values
```

### Enterprise Types
```php
$schema->morphs('commentable')           // Polymorphic relationships
       ->binary('file_data')             // Binary data
       ->geometry('location')            // Spatial data
       ->lineString('route')             // Geographic lines
       ->multiPoint('locations')         // Multiple points
       ->rememberToken()                 // Authentication tokens
       ->softDeletes();                  // Soft delete timestamps
```

## 🔄 Relationships

Define relationships between tables:

### Foreign Keys
```php
$schema->increments('id')
       ->string('title')
       ->integer('user_id')->unsigned()
       ->timestamps()
       ->foreign('user_id')
           ->references('id')
           ->on('users')
           ->onDelete('cascade')
       ->createTable('posts');
```

### Polymorphic Relationships
```php
// Comments can belong to posts OR products
$schema->increments('id')
       ->text('content')
       ->morphs('commentable')  // Creates commentable_id and commentable_type
       ->timestamps()
       ->createTable('comments');
```

## 🎯 Best Practices

### 1. Use Parameter Binding
```php
// ✅ Always use parameter binding
SimpleQuery::create()
    ->where('id = ?', [$id])
    ->execute($db);

// ❌ Never concatenate user input
SimpleQuery::create()
    ->where("id = $id")  // Vulnerable to SQL injection
    ->execute($db);
```

### 2. Handle Exceptions
```php
try {
    $result = SimpleQuery::create()
        ->select(['*'])
        ->from('users')
        ->execute($db);
} catch (SimpleMDB\Exceptions\QueryException $e) {
    // Handle query-specific errors
    error_log("Query failed: " . $e->getMessage());
} catch (Exception $e) {
    // Handle general errors
    error_log("Unexpected error: " . $e->getMessage());
}
```

### 3. Use Transactions
```php
$db->beginTransaction();
try {
    // Multiple operations
    SimpleQuery::create()->insert($data1)->into('table1')->execute($db);
    SimpleQuery::create()->insert($data2)->into('table2')->execute($db);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### 4. Optimize Performance
```php
// Use specific column selection
SimpleQuery::create()
    ->select(['id', 'name'])  // ✅ Only needed columns
    ->from('users');

// Avoid SELECT *
SimpleQuery::create()
    ->select(['*'])           // ❌ Fetches unnecessary data
    ->from('users');

// Use appropriate indexes
$schema->string('email')->unique();  // Creates index for fast lookups
```

## 🚀 Next Steps

Now that you understand the basics:

1. **[Explore Schema Builder](schema-builder.md)** - Learn advanced table creation
2. **[Master Query Builder](query-builder.md)** - Build complex queries
3. **[Review Data Types](data-types.md)** - Use the right type for each field
4. **[Setup Migrations](migrations.md)** - Version control your database
5. **[Implement Security](security.md)** - Protect your application

## 💡 Quick Tips

- **Start Simple**: Begin with basic queries, then add complexity
- **Test Queries**: Use `toSQL()` to inspect generated SQL
- **Use Migrations**: Always version control schema changes
- **Parameter Binding**: Never trust user input directly
- **Handle Errors**: Wrap database operations in try-catch blocks
- **Performance**: Select only needed columns and use indexes

---

**Need Help?**
- 📖 [Read the Installation Guide](installation.md)
- 🔍 [Check the FAQ](faq.md)
- 💬 [Join our Discord](https://discord.gg/simplemdb)
- 🐛 [Report Issues](https://github.com/imrnansaadullah/SimpleMDB/issues) 