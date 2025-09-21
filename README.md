# SimpleMDB - Enterprise Database Toolkit for PHP

> **🚀 Production-Ready Database Management** - Build enterprise-grade applications with Laravel-like syntax, 25+ data types, intelligent migrations, and military-grade security.

[![Latest Version](https://img.shields.io/badge/version-v4.1.1-blue.svg)](https://github.com/imrnansaadullah/SimpleMDB/releases)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## ⚡ 5-Minute Quick Start

**Install SimpleMDB:**
```bash
composer require simplemdb/simplemdb
```

**Create your first table (MySQL):**
```php
<?php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder;

// Connect to MySQL database
$db = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'myapp');

// Create modern table with enterprise features
$schema = new SchemaBuilder($db);
$schema->increments('id')                           // Auto-increment primary key
       ->string('name', 100)->comment('Full name')  // VARCHAR with comment
       ->string('email', 150)->unique()             // Unique email
       ->boolean('is_active')->default(true)        // Boolean with default
       ->json('preferences')->nullable()            // JSON data storage
       ->ipAddress('last_login_ip')->nullable()     // IPv4/IPv6 address
       ->timestamps()                               // created_at, updated_at
       ->createTable('users');

echo "✅ Modern users table created!\n";
```

**Or use PostgreSQL:**
```php
<?php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder_PostgreSQL;

// Connect to PostgreSQL database
$db = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'myapp');

// Create modern table with PostgreSQL-specific features
$schema = new SchemaBuilder_PostgreSQL($db);
$schema->increments('id')                           // SERIAL PRIMARY KEY
       ->string('name', 100)->comment('Full name')  // VARCHAR with comment
       ->string('email', 150)->unique()             // Unique email
       ->boolean('is_active')->default(true)        // Boolean with default
       ->jsonb('preferences')->nullable()           // JSONB data storage (PostgreSQL)
       ->inet('last_login_ip')->nullable()          // INET address type (PostgreSQL)
       ->uuidWithDefault('external_id')             // UUID with gen_random_uuid()
       ->textArray('tags')->nullable()              // TEXT[] array (PostgreSQL)
       ->timestamps()                               // created_at, updated_at
       ->createTable('users');

echo "✅ Modern PostgreSQL users table created!\n";
```

**That's it!** You now have a production-ready table with modern data types and enterprise features.

👉 **[Try the complete quick start example →](QUICK_START.md)**

---

## 🏆 Why Choose SimpleMDB?

### **Laravel-Like Developer Experience**
```php
// Familiar fluent syntax
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->orderBy('created_at DESC')
    ->execute($db);
```

### **25+ Modern Data Types**
```php
$table->uuid('external_id')                // UUID storage
      ->ipAddress('client_ip')             // IPv4/IPv6 (45 chars)
      ->json('metadata')                   // JSON documents
      ->point('location')                  // Geographic coordinates
      ->morphs('commentable');             // Polymorphic relationships
```

### **Intelligent Migrations**
```php
// Auto-generates context-aware templates
$migrations->create('create_blog_posts_table');
// ✨ Detects "blog posts" and generates complete table structure
```

### **Enterprise Security & Performance**
- **100% SQL injection prevention** with comprehensive validation
- **Memory-efficient streaming** for large datasets (10-50x reduction)
- **AES-256 encryption** for sensitive data
- **Connection pooling** and advanced caching

### **Complete Database Object Management**
- **Functions & Procedures** with fluent parameter management
- **Views** with algorithm optimization and security contexts
- **Events** with flexible scheduling (one-time, recurring, intervals)
- **Triggers** for data integrity and automated auditing
- **Unified Management** interface for all database objects

---

## 📚 Documentation

### 🚀 **Getting Started**
- **[5-Minute Quick Start](QUICK_START.md)** - Get SimpleMDB working immediately
- **[Installation Guide](docs/installation.md)** - Detailed setup and configuration
- **[Basic Concepts](docs/basic-concepts.md)** - Core SimpleMDB concepts
- **[Testing Guide](TESTING.md)** - Write reliable tests for your application

### 🏗️ **Core Features**
- **[Schema Builder](docs/schema-builder.md)** - Create and modify database tables
- **[Data Types Reference](docs/data-types.md)** - All 25+ data types with examples
- **[Query Builder](docs/query-builder.md)** - Advanced query building
- **[Migration System](docs/migrations.md)** - Intelligent database migrations

### 🔄 **Migration Guides**
- **[From Laravel/Eloquent](docs/migration-guides.md#from-laravel-eloquent)** - Migrate from Laravel
- **[From Doctrine ORM](docs/migration-guides.md#from-doctrine-orm)** - Migrate from Doctrine
- **[From CodeIgniter](docs/migration-guides.md#from-codeigniter)** - Migrate from CodeIgniter
- **[Complete Migration Guide](docs/migration-guides.md)** - All framework migrations

### 🛡️ **Enterprise Features**
- **[Security Guide](docs/security.md)** - Enterprise security best practices
- **[Performance Optimization](docs/performance.md)** - Caching, pooling, optimization
- **[Backup System](docs/backup-system.md)** - Complete backup and restore solution
- **[Database Objects](docs/database-objects.md)** - Functions, procedures, views, events, triggers
- **[Testing Guide](TESTING.md)** - Testing your SimpleMDB applications

### 🔄 **Migration Guides**
- **[From Laravel](docs/migrate-from-laravel.md)** - Step-by-step Laravel migration
- **[From Doctrine](docs/migrate-from-doctrine.md)** - Doctrine to SimpleMDB
- **[From Raw SQL](docs/migrate-from-sql.md)** - Converting raw SQL projects

---

## 🗄️ Multi-Database Support

SimpleMDB now supports **multiple database engines** with 100% backward compatibility:

### **Supported Databases**
- ✅ **MySQL** 5.7+ / 8.0+ (Full support)
- ✅ **MariaDB** 10.2+ (Full support)
- ✅ **PostgreSQL** 9.6+ / 12.0+ (Full support with advanced features)

### **Database-Specific Features**

| Feature | MySQL | PostgreSQL |
|---------|-------|------------|
| **JSON Storage** | `json()` | `json()` + `jsonb()` |
| **IP Addresses** | `ipAddress()` | `inet()` (native type) |
| **Arrays** | JSON arrays | Native arrays `textArray()`, `integerArray()` |
| **UUIDs** | `uuid()` as CHAR(36) | `uuid()` native + `uuidWithDefault()` |
| **Auto-increment** | `AUTO_INCREMENT` | `SERIAL`/`BIGSERIAL` |
| **Full-text Search** | `FULLTEXT` indexes | Built-in text search |

### **Connection Examples**

```php
// MySQL Connection
$mysql = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'myapp');

// PostgreSQL Connection  
$pgsql = DatabaseFactory::create('postgresql', 'localhost', 'postgres', 'password', 'myapp');

// Same API, different engines!
$mysql->write_data('users', ['name' => 'John', 'email' => 'john@example.com']);
$pgsql->write_data('users', ['name' => 'Jane', 'email' => 'jane@example.com']);
```

---

## 🎯 Feature Comparison

| Feature | SimpleMDB | Laravel Schema | Doctrine DBAL |
|---------|-----------|----------------|---------------|
| **Multi-Database** | ✅ **MySQL + PostgreSQL** | ✅ Multiple | ✅ Multiple |
| **Data Types** | ✅ 25+ types | ✅ 27+ types | ✅ 20+ types |
| **Schema Validation** | ✅ **Comprehensive** | ⚠️ Basic | ⚠️ Basic |
| **Migration Intelligence** | ✅ **Smart Templates** | ⚠️ Static | ❌ Manual |
| **Database Objects** | ✅ **Complete** | ❌ Limited | ❌ Limited |
| **Security Features** | ✅ **Enterprise** | ✅ Good | ✅ Good |
| **Memory Efficiency** | ✅ **Streaming** | ⚠️ Standard | ⚠️ Standard |
| **Learning Curve** | ✅ **Gentle** | ⚠️ Steep | ⚠️ Steep |
| **Backward Compatibility** | ✅ **100%** | ⚠️ Breaking changes | ⚠️ Breaking changes |

---

## 💡 Real-World Examples

### E-commerce Product Table
```php
$schema->createTable('products', function($table) {
    $table->increments('id');
    $table->string('sku', 50)->unique();
    $table->string('name', 200);
    $table->decimal('price', 10, 2)->unsigned();
    $table->json('attributes')->nullable();             // Color, size, etc.
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->ipAddress('created_from_ip');
    $table->timestamps();
    
    $table->index(['status', 'price']);
});
```

### User Authentication System
```php
$schema->createTable('users', function($table) {
    $table->increments('id');
    $table->string('email')->unique();
    $table->string('password');
    $table->json('preferences')->nullable();
    $table->ipAddress('last_login_ip')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

### Complete Backup Solution
```php
use SimpleMDB\Backup\BackupManager;

$backupManager = new BackupManager($db, 'backups/');

// Memory-efficient encrypted backup
$backup = $backupManager
    ->backup('daily_backup')
    ->streaming(1000)              // Process in chunks
    ->encrypted($encryptionKey)     // AES-256 encryption
    ->compress('gzip')             // Space efficient
    ->execute();
```

### Database Objects Management
```php
use SimpleMDB\DatabaseObjects\DatabaseObjectManager;

$objects = new DatabaseObjectManager($db);

// Create a function
$objects->function('calculate_total')
    ->inParameter('amount', 'DECIMAL(10,2)')
    ->returns('DECIMAL(10,2)')
    ->body("RETURN amount * 1.1;")
    ->create();

// Create a view with complex logic
$objects->view('user_summary')
    ->select("
        u.id, u.name, u.email,
        COUNT(o.id) as order_count,
        SUM(o.total) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ")
    ->create();

// Create a trigger for auditing
$objects->trigger('audit_changes')
    ->after()
    ->update()
    ->on('users')
    ->body("INSERT INTO audit_log (table_name, action, record_id) VALUES ('users', 'UPDATE', NEW.id);")
    ->create();
```

---

## ⚙️ System Requirements

- **PHP**: 8.0 or higher
- **Databases**: 
  - **MySQL**: 5.7+ or 8.0+ (recommended)
  - **MariaDB**: 10.2+ (fully supported)  
  - **PostgreSQL**: 9.6+ or 12.0+ (recommended)
- **Extensions**: 
  - PDO or MySQLi (for MySQL/MariaDB)
  - PDO with pdo_pgsql (for PostgreSQL)
- **Memory**: 64MB minimum (128MB recommended)

---

## 🚀 Installation Options

### Composer (Recommended)
```bash
composer require simplemdb/simplemdb
```

### Manual Installation
```bash
git clone https://github.com/imrnansaadullah/SimpleMDB.git
cd SimpleMDB
composer install
```

### Verify Installation
```php
<?php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;

try {
    $db = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'test');
    echo "✅ SimpleMDB installed successfully!\n";
} catch (Exception $e) {
    echo "❌ Installation issue: " . $e->getMessage() . "\n";
}
```

---

## 🎓 Learning Path

### **Beginner (30 minutes)**
1. **[5-Minute Quick Start](QUICK_START.md)** - Basic table creation
2. **[Schema Builder Basics](docs/schema-builder.md#basics)** - Understanding column types
3. **[Simple Queries](docs/query-builder.md#basics)** - SELECT, INSERT, UPDATE, DELETE

### **Intermediate (2 hours)**
1. **[Advanced Data Types](docs/data-types.md)** - JSON, UUIDs, polymorphic types
2. **[Migration System](docs/migrations.md)** - Creating and running migrations
3. **[Security Best Practices](docs/security.md)** - SQL injection prevention

### **Advanced (1 day)**
1. **[Performance Optimization](docs/performance.md)** - Caching, connection pooling
2. **[Enterprise Backup System](docs/backup-system.md)** - Complete backup solutions
3. **[Production Deployment](docs/deployment.md)** - Production best practices

---

## 🛠️ Quick API Reference

### Schema Builder
```php
// Column types
$table->string('name', 100)           // VARCHAR
$table->integer('count')              // INT
$table->boolean('active')             // TINYINT(1)
$table->json('data')                  // JSON
$table->timestamp('created_at')       // TIMESTAMP
$table->ipAddress('ip')               // VARCHAR(45)

// Modifiers
$table->nullable()                    // Allow NULL
$table->default('value')              // Default value
$table->unique()                      // Unique constraint
$table->comment('Description')        // Column comment

// Indexes
$table->index(['column'])             // Regular index
$table->unique(['email'])             // Unique index
$table->foreignKey('user_id', 'users', 'id')  // Foreign key
```

### Query Builder
```php
// SELECT
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->where('active = ?', [true])
    ->execute($db);

// INSERT
$result = SimpleQuery::create()
    ->insert(['name' => 'John', 'email' => 'john@example.com'])
    ->into('users')
    ->execute($db);

// UPDATE
$result = SimpleQuery::create()
    ->update('users')
    ->set(['name' => 'Jane'])
    ->where('id = ?', [1])
    ->execute($db);
```

---

## 🤝 Contributing

We welcome contributions! See our [Contributing Guide](docs/contributing.md) for details.

### Quick Contribution Setup
```bash
git clone https://github.com/imrnansaadullah/SimpleMDB.git
cd SimpleMDB
composer install
php examples/quick_start_example.php  # Test your setup
```

---

## 📖 Resources

- **[Complete Documentation](docs/)** - Comprehensive guides and references
- **[Example Projects](examples/)** - Real-world usage examples  
- **[API Reference](docs/api/)** - Complete method documentation
- **[Video Tutorials](https://youtube.com/@SimpleMDB)** - Step-by-step video guides
- **[Community Forum](https://github.com/imrnansaadullah/SimpleMDB/discussions)** - Get help and share tips

---

## 📜 License

MIT License - Use SimpleMDB in any project, commercial or open source.

---

## 🌟 Enterprise Ready

**SimpleMDB is production-ready for enterprise applications.** Join the growing community of developers who choose SimpleMDB for its combination of **power**, **security**, and **developer experience**.

**⭐ Star us on GitHub** if SimpleMDB helps you build better applications!

---

*Need enterprise support? [Contact us](mailto:lems?
@simplemdb.com) for professional services, training, and custom development.*
