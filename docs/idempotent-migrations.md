# Idempotent Migrations

## 🎯 Overview

Idempotent migrations are **safe to run multiple times** without causing errors. This is crucial for production deployments where the same migration might be executed multiple times due to deployment scripts, CI/CD pipelines, or manual re-runs.

## 🚨 The Problem

Traditional migrations fail when run multiple times:

```php
// ❌ This will crash on second run
$schema->addIndex('users', ['email']); // Error: Index already exists
$schema->addColumn('users', 'phone', ['type' => 'VARCHAR']); // Error: Column already exists
$schema->createTable('users', function($table) { ... }); // Error: Table already exists
```

## ✅ The Solution

Idempotent methods automatically check if objects exist before creating them:

```php
// ✅ Safe to run multiple times
$schema->addIndexIfNotExists('users', ['email']); // Only creates if doesn't exist
$schema->addColumnIfNotExists('users', 'phone', ['type' => 'VARCHAR']); // Only adds if doesn't exist
$schema->createTableIfNotExists('users', function($table) { ... }); // Only creates if doesn't exist
```

---

## 🔧 Available Idempotent Methods

### Table Operations

```php
// Create table only if it doesn't exist
$schema->createTableIfNotExists('users', function($table) {
    $table->increments('id')
          ->string('email')->unique()
          ->string('name')
          ->timestamps();
});
```

### Index Operations

```php
// Add regular index only if it doesn't exist
$schema->addIndexIfNotExists('users', ['email'], 'idx_users_email');

// Add unique index only if it doesn't exist
$schema->addUniqueIndexIfNotExists('users', ['email'], 'unique_users_email');

// Check if index exists
if ($schema->hasIndex('users', 'idx_users_email')) {
    echo "Index exists!";
}

// Check if index exists by columns
if ($schema->hasIndexByColumns('users', ['email', 'name'])) {
    echo "Composite index exists!";
}

// Get all indexes for a table
$indexes = $schema->getIndexes('users');
foreach ($indexes as $index) {
    echo "Index: {$index['name']} on columns: " . implode(', ', $index['columns']);
}
```

### Column Operations

```php
// Add column only if it doesn't exist
$schema->addColumnIfNotExists('users', 'phone', [
    'type' => 'VARCHAR',
    'length' => 20,
    'nullable' => true
]);
```

### Foreign Key Operations

```php
// Add foreign key only if it doesn't exist
$schema->addForeignKeyIfNotExists(
    'users', 'role_id', 'roles', 'id',
    'fk_users_role_id', 'cascade', 'cascade'
);
```

---

## 📝 Migration Class Usage

In your migration classes, use the idempotent methods:

```php
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        // Create table safely
        $this->createTableIfNotExists('users', function($table) {
            $table->increments('id')
                  ->string('email')->unique()
                  ->string('name')
                  ->boolean('is_active')->default(true)
                  ->timestamps();
        });
        
        // Add indexes safely
        $this->addIndexIfNotExists('users', ['email'], 'idx_users_email');
        $this->addIndexIfNotExists('users', ['name'], 'idx_users_name');
        
        // Add columns safely
        $this->addColumnIfNotExists('users', 'phone', [
            'type' => 'VARCHAR',
            'length' => 20,
            'nullable' => true
        ]);
        
        // Add foreign keys safely
        $this->addForeignKeyIfNotExists(
            'users', 'role_id', 'roles', 'id',
            'fk_users_role_id', 'cascade', 'cascade'
        );
    }
    
    public function down(): void
    {
        // Drop in reverse order
        $this->dropTable('users');
    }
}
```

---

## 🔍 Detection Methods

### Check if Index Exists

```php
// By index name
if ($schema->hasIndex('users', 'idx_users_email')) {
    echo "Index exists by name";
}

// By columns (finds any index with matching columns)
if ($schema->hasIndexByColumns('users', ['email', 'name'])) {
    echo "Index exists with these columns";
}
```

### Get Index Information

```php
$indexes = $schema->getIndexes('users');
foreach ($indexes as $index) {
    echo "Index: {$index['name']}\n";
    echo "  Type: {$index['type']}\n";
    echo "  Unique: " . ($index['unique'] ? 'Yes' : 'No') . "\n";
    echo "  Primary: " . ($index['primary'] ? 'Yes' : 'No') . "\n";
    echo "  Columns: " . implode(', ', $index['columns']) . "\n";
}
```

### Check if Column Exists

```php
if ($schema->hasColumn('users', 'email')) {
    echo "Column exists";
}
```

### Check if Table Exists

```php
if ($schema->hasTable('users')) {
    echo "Table exists";
}
```

---

## 🎯 Best Practices

### 1. Always Use Idempotent Methods in Production

```php
// ✅ Good for production
$schema->addIndexIfNotExists('users', ['email']);

// ❌ Avoid in production (will crash on re-run)
$schema->table('users')->addIndex(['email']);
```

### 2. Use Descriptive Index Names

```php
// ✅ Good naming
$schema->addIndexIfNotExists('users', ['email'], 'idx_users_email');
$schema->addIndexIfNotExists('users', ['created_at'], 'idx_users_created_at');

// ❌ Poor naming (auto-generated names can conflict)
$schema->addIndexIfNotExists('users', ['email']); // Auto-named
```

### 3. Check Before Complex Operations

```php
// For complex operations, check first
if (!$schema->hasTable('users')) {
    $schema->createTableIfNotExists('users', function($table) {
        // Complex table creation
    });
}

if (!$schema->hasIndex('users', 'idx_complex_query')) {
    $schema->addIndexIfNotExists('users', ['status', 'created_at', 'category_id'], 'idx_complex_query');
}
```

### 4. Use in CI/CD Pipelines

```php
// Perfect for deployment scripts
class DeployDatabase extends Migration
{
    public function up(): void
    {
        // Safe to run multiple times
        $this->createTableIfNotExists('deployment_logs', function($table) {
            $table->increments('id')
                  ->string('deployment_id')->unique()
                  ->timestamp('deployed_at')
                  ->text('changes');
        });
        
        // Add indexes safely
        $this->addIndexIfNotExists('deployment_logs', ['deployed_at'], 'idx_deployment_date');
    }
}
```

---

## 🚀 Advanced Usage

### Conditional Index Creation

```php
// Only create index if table has many rows
$userCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch('assoc')['count'];
if ($userCount > 1000) {
    $schema->addIndexIfNotExists('users', ['email'], 'idx_users_email_large');
}
```

### Dynamic Column Addition

```php
// Add columns based on configuration
$config = ['phone' => true, 'address' => false, 'preferences' => true];

foreach ($config as $column => $enabled) {
    if ($enabled) {
        $schema->addColumnIfNotExists('users', $column, [
            'type' => 'VARCHAR',
            'length' => 255,
            'nullable' => true
        ]);
    }
}
```

### Migration Rollback Safety

```php
class AddUserPhone extends Migration
{
    public function up(): void
    {
        $this->addColumnIfNotExists('users', 'phone', [
            'type' => 'VARCHAR',
            'length' => 20,
            'nullable' => true
        ]);
    }
    
    public function down(): void
    {
        // Only drop if column exists
        if ($this->hasColumn('users', 'phone')) {
            $this->table('users')->dropColumn('phone');
        }
    }
}
```

---

## 🔧 Troubleshooting

### Common Issues

**"Index already exists" error**
```php
// ❌ Problem
$schema->table('users')->addIndex(['email']); // Crashes on re-run

// ✅ Solution
$schema->addIndexIfNotExists('users', ['email']); // Safe
```

**"Column already exists" error**
```php
// ❌ Problem
$schema->table('users')->addColumn('phone', ['type' => 'VARCHAR']); // Crashes

// ✅ Solution
$schema->addColumnIfNotExists('users', 'phone', ['type' => 'VARCHAR']); // Safe
```

**"Table already exists" error**
```php
// ❌ Problem
$schema->createTable('users', function($table) { ... }); // Crashes

// ✅ Solution
$schema->createTableIfNotExists('users', function($table) { ... }); // Safe
```

### Debug Index Issues

```php
// Check what indexes exist
$indexes = $schema->getIndexes('users');
echo "Existing indexes:\n";
foreach ($indexes as $index) {
    echo "- {$index['name']}: " . implode(', ', $index['columns']) . "\n";
}

// Check specific index
if ($schema->hasIndex('users', 'idx_users_email')) {
    echo "Email index exists\n";
} else {
    echo "Email index does not exist\n";
}
```

---

## 📚 Related Documentation

- **[Schema Builder](schema-builder.md)** - Complete schema building guide
- **[Migration Guide](migration-guides.md)** - Migration best practices
- **[Data Types](data-types.md)** - Available column types
- **[Examples](examples/)** - Practical usage examples

---

## 🎯 Summary

Idempotent migrations ensure your database operations are:

- ✅ **Safe for multiple runs**
- ✅ **Production-ready**
- ✅ **CI/CD friendly**
- ✅ **Error-free**
- ✅ **Efficient** (only creates what's needed)

Use these methods for all production migrations to avoid deployment issues! 