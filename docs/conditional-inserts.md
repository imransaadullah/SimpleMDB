# Conditional Inserts

## 🎯 Overview

Conditional inserts allow you to safely insert data by checking unique constraints before inserting. This prevents duplicate data and makes data insertion **idempotent** - safe to run multiple times without errors.

## 🚨 The Problem

Traditional inserts fail when run multiple times:

```php
// ❌ This will crash on second run
$db->write_data('users', [
    'email' => 'john@example.com',
    'name' => 'John Doe'
]); // Error: Duplicate entry for key 'email'
```

## ✅ The Solution

Conditional insert methods automatically check if records exist before inserting:

```php
// ✅ Safe to run multiple times
$schema->insertIfNotExists('users', [
    'email' => 'john@example.com',
    'name' => 'John Doe'
]); // Only inserts if email doesn't exist
```

## 📋 Available Methods

### 1. `insertIfNotExists()`

Insert a single record only if it doesn't exist based on unique fields.

```php
$result = $schema->insertIfNotExists('users', [
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'is_active' => true
], ['email']); // Check unique constraint on email

if ($result) {
    echo "✅ Record inserted";
} else {
    echo "⏭️ Record already exists, skipped";
}
```

### 2. `insertManyIfNotExists()`

Insert multiple records, skipping those that already exist.

```php
$users = [
    ['email' => 'john@example.com', 'name' => 'John Doe'],
    ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
    ['email' => 'bob@example.com', 'name' => 'Bob Johnson']
];

$results = $schema->insertManyIfNotExists('users', $users);

echo "Total: {$results['total']}\n";
echo "Inserted: {$results['inserted']}\n";
echo "Skipped: {$results['skipped']}\n";
echo "Errors: " . count($results['errors']) . "\n";
```

### 3. `upsert()`

Insert if not exists, update if exists (INSERT ... ON DUPLICATE KEY UPDATE).

```php
$result = $schema->upsert('users', [
    'email' => 'john@example.com',
    'name' => 'John Doe Updated',
    'is_active' => false
], ['email']);

if ($result) {
    echo "✅ Record inserted or updated";
} else {
    echo "❌ Upsert failed";
}
```

### 4. `recordExists()`

Check if a record exists based on unique fields.

```php
$exists = $schema->recordExists('users', [
    'email' => 'john@example.com'
], ['email']);

if ($exists) {
    echo "✅ User with this email already exists";
} else {
    echo "❌ User not found";
}
```

### 5. `getUniqueFields()`

Get all unique fields for a table.

```php
$uniqueFields = $schema->getUniqueFields('users');
echo "Unique fields: " . implode(', ', $uniqueFields);
// Output: email, username
```

## 🔧 How It Works

### Automatic Unique Field Detection

If you don't specify unique fields, the system automatically detects them:

```php
// Automatically detects unique constraints
$schema->insertIfNotExists('users', [
    'email' => 'john@example.com',
    'name' => 'John Doe'
]); // Uses detected unique fields
```

### Manual Unique Field Specification

You can also specify unique fields manually:

```php
// Specify unique fields manually
$schema->insertIfNotExists('users', [
    'email' => 'john@example.com',
    'name' => 'John Doe'
], ['email', 'username']); // Check both email and username
```

### Multiple Unique Constraints

The system handles multiple unique constraints:

```php
// Table with multiple unique constraints
$schema->createTableIfNotExists('products', function($table) {
    $table->id();
    $table->string('sku')->unique();
    $table->string('name');
    $table->string('barcode')->unique();
    $table->timestamps();
});

// Check all unique constraints
$schema->insertIfNotExists('products', [
    'sku' => 'PROD-001',
    'name' => 'Laptop',
    'barcode' => '123456789'
]); // Checks both sku and barcode
```

## 🚀 Migration Examples

### Safe Data Seeding

```php
class SeedUsersMigration extends Migration
{
    public function up(): void
    {
        $users = [
            [
                'email' => 'admin@example.com',
                'name' => 'Admin User',
                'role' => 'admin'
            ],
            [
                'email' => 'user@example.com',
                'name' => 'Regular User',
                'role' => 'user'
            ]
        ];
        
        // Safe to run multiple times
        $results = $this->insertManyIfNotExists('users', $users);
        
        echo "Seeded {$results['inserted']} users, skipped {$results['skipped']}";
    }
}
```

### Configuration Data

```php
class SeedConfigMigration extends Migration
{
    public function up(): void
    {
        $configs = [
            ['key' => 'site_name', 'value' => 'My App'],
            ['key' => 'maintenance_mode', 'value' => 'false'],
            ['key' => 'max_upload_size', 'value' => '10MB']
        ];
        
        // Only insert if config key doesn't exist
        $this->insertManyIfNotExists('configs', $configs, ['key']);
    }
}
```

### Product Catalog

```php
class SeedProductsMigration extends Migration
{
    public function up(): void
    {
        $products = [
            [
                'sku' => 'LAPTOP-001',
                'name' => 'Gaming Laptop',
                'price' => 1299.99,
                'category' => 'Electronics'
            ],
            [
                'sku' => 'MOUSE-001',
                'name' => 'Wireless Mouse',
                'price' => 29.99,
                'category' => 'Accessories'
            ]
        ];
        
        // Update existing products, insert new ones
        foreach ($products as $product) {
            $this->upsert('products', $product, ['sku']);
        }
    }
}
```

## 🎯 Best Practices

### 1. Use in Migrations

Always use conditional inserts in migrations to make them idempotent:

```php
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->createTableIfNotExists('users', function($table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->timestamps();
        });
        
        // Safe seeding
        $this->insertManyIfNotExists('users', [
            ['email' => 'admin@example.com', 'name' => 'Admin'],
            ['email' => 'user@example.com', 'name' => 'User']
        ]);
    }
}
```

### 2. Handle Results

Always check the results of conditional inserts:

```php
$results = $schema->insertManyIfNotExists('users', $users);

if ($results['errors']) {
    // Handle errors
    foreach ($results['errors'] as $error) {
        log_error("Insert failed: $error");
    }
}

if ($results['skipped'] > 0) {
    log_info("Skipped {$results['skipped']} existing records");
}
```

### 3. Use Appropriate Methods

Choose the right method for your use case:

- **`insertIfNotExists()`**: When you only want to insert, never update
- **`upsert()`**: When you want to insert or update existing records
- **`insertManyIfNotExists()`**: For bulk operations with detailed results

### 4. Specify Unique Fields

For better performance and clarity, specify unique fields:

```php
// Better performance
$schema->insertIfNotExists('users', $data, ['email']);

// Automatic detection (slower)
$schema->insertIfNotExists('users', $data);
```

## 🔍 Error Handling

### Graceful Failure

All methods return appropriate results instead of throwing exceptions:

```php
$result = $schema->insertIfNotExists('users', $data);

if (!$result) {
    // Handle gracefully - record already exists
    echo "User already exists";
}
```

### Detailed Results

Bulk operations provide detailed information:

```php
$results = $schema->insertManyIfNotExists('users', $users);

if ($results['errors']) {
    foreach ($results['errors'] as $error) {
        echo "Error: $error\n";
    }
}
```

## ⚡ Performance Considerations

### Index Usage

Conditional inserts work best with proper indexes:

```php
// Ensure unique indexes exist
$schema->createTableIfNotExists('users', function($table) {
    $table->id();
    $table->string('email')->unique(); // Index for fast lookups
    $table->string('username')->unique();
    $table->timestamps();
});
```

### Batch Operations

For large datasets, use batch operations:

```php
// Process in chunks for better performance
$chunks = array_chunk($largeDataset, 1000);

foreach ($chunks as $chunk) {
    $results = $schema->insertManyIfNotExists('users', $chunk);
    // Process results
}
```

## 🎉 Summary

Conditional inserts provide:

- ✅ **Idempotent operations** - safe to run multiple times
- ✅ **Duplicate prevention** - automatic unique constraint checking
- ✅ **Flexible control** - insert, update, or skip as needed
- ✅ **Detailed feedback** - comprehensive result information
- ✅ **Performance optimized** - efficient database operations

This makes your data operations robust and production-ready! 