# ⚡ 5-Minute Quick Start with SimpleMDB

Get up and running with SimpleMDB in just 5 minutes. This guide focuses on the essentials to get you productive immediately.

## 📋 What You'll Learn

By the end of this guide, you'll have:
- ✅ SimpleMDB installed and connected to your database
- ✅ Created your first table with modern data types
- ✅ Inserted and queried data
- ✅ Ready to explore advanced features

**Time needed:** 5 minutes  
**Prerequisites:** PHP 8.0+, MySQL 5.7+

---

## Step 1: Install SimpleMDB (30 seconds)

```bash
composer require simplemdb/simplemdb
```

## Step 2: Connect to Database (30 seconds)

Create a file called `test.php`:

```php
<?php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder;

// Connect to your database
$db = DatabaseFactory::create('pdo', 'localhost', 'root', 'password', 'your_database');

// Test the connection
if ($db->ping()) {
    echo "✅ Connected to database successfully!\n";
} else {
    echo "❌ Connection failed\n";
    exit(1);
}
```

**Run it:** `php test.php`

## Step 3: Create Your First Modern Table (2 minutes)

Add this to your `test.php`:

```php
// Initialize schema builder
$schema = new SchemaBuilder($db);

// Create a modern users table
$schema->increments('id')                           // Auto-increment primary key
       ->string('name', 100)->comment('Full name')  // VARCHAR with comment
       ->string('email', 150)->unique()             // Unique email
       ->boolean('is_active')->default(true)        // Boolean with default
       ->json('preferences')->nullable()            // JSON data storage
       ->ipAddress('last_login_ip')->nullable()     // IPv4/IPv6 address
       ->timestamps()                               // created_at, updated_at
       ->createTable('users');

echo "✅ Users table created with modern features!\n";
```

**What makes this special:**
- **Modern data types**: JSON, IP addresses, booleans
- **Self-documenting**: Comments on columns
- **Enterprise features**: Automatic timestamps, unique constraints
- **Laravel-like syntax**: Familiar fluent interface

## Step 4: Insert and Query Data (2 minutes)

Add this to your `test.php`:

```php
use SimpleMDB\SimpleQuery;

// Insert a user
$userId = SimpleQuery::create()
    ->insert('users')
    ->values([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_active' => true,
        'preferences' => json_encode(['theme' => 'dark', 'notifications' => true]),
        'last_login_ip' => '192.168.1.100'
    ])
    ->execute($db);

echo "✅ User created with ID: $userId\n";

// Query users
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email', 'is_active'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->execute($db);

echo "✅ Found " . count($users) . " active users:\n";
foreach ($users as $user) {
    echo "  - {$user['name']} ({$user['email']})\n";
}
```

## Step 5: You're Ready! 🎉

**Congratulations!** You've just:
- ✅ Connected to your database
- ✅ Created a table with modern data types (JSON, IP addresses, booleans)
- ✅ Inserted and queried data safely
- ✅ Used enterprise features (timestamps, unique constraints)

---

## 🚀 What's Next?

Now that you have the basics working, explore these powerful features:

### **Intelligent Migrations** (5 minutes)
```php
use SimpleMDB\Migrations\MigrationManager;

$migrations = new MigrationManager($db);
$migrationFile = $migrations->create('create_blog_posts_table');
// ✨ Auto-generates intelligent templates based on the name!
```

### **25+ Advanced Data Types** (10 minutes)
```php
$schema->uuid('external_id')                // UUID storage
       ->decimal('price', 10, 2)->unsigned() // Precise money values
       ->enum('status', ['draft', 'published']) // Enumerated values
       ->morphs('commentable')              // Polymorphic relationships
       ->point('location')                  // Geographic coordinates
       ->fullTextIndex(['title', 'content']); // Full-text search
```

### **Enterprise Backup System** (10 minutes)
```php
use SimpleMDB\Backup\BackupManager;

$backupManager = new BackupManager($db, 'backups/');

// Memory-efficient encrypted backup
$backup = $backupManager
    ->backup('daily_backup')
    ->streaming(1000)              // Process in chunks
    ->encrypted($encryptionKey)     // AES-256 encryption
    ->compress()                   // Gzip compression
    ->execute();
```

---

## 📚 Learn More

| Topic | Time | Link |
|-------|------|------|
| **Data Types Reference** | 15 min | [See all 25+ data types](README.md#data-types-reference) |
| **Migration System** | 20 min | [Intelligent migrations](README.md#migration-system) |
| **Security Features** | 15 min | [Enterprise security](README.md#security-features) |
| **Performance** | 20 min | [Caching & optimization](README.md#performance-optimization) |
| **Examples** | 30 min | [Complete examples](examples/) |

---

## 🆘 Need Help?

- **Examples**: Check the `examples/` directory for real-world code
- **Documentation**: Full documentation in [README.md](README.md)
- **Issues**: Report problems on [GitHub Issues](https://github.com/imrnansaadullah/SimpleMDB/issues)
- **Testing**: See [TESTING.md](TESTING.md) for testing your setup

---

**🌟 You're now ready to build enterprise-grade database applications with SimpleMDB!**

*Time elapsed: 5 minutes. Knowledge gained: Foundational SimpleMDB skills.* 