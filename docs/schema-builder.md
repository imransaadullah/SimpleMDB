# Schema Builder Reference

Master SimpleMDB's Schema Builder to create, modify, and manage database tables with enterprise-grade features. Build everything from simple tables to complex relationships with spatial data.

## 🚀 Quick Start

```php
use SimpleMDB\SchemaBuilder;

$schema = new SchemaBuilder($db);

// Create a modern user table
$schema->increments('id')
       ->string('name', 100)
       ->string('email', 150)->unique()
       ->boolean('is_active')->default(true)
       ->json('preferences')->nullable()
       ->timestamps()
       ->createTable('users');
```

## 📋 Table of Contents

- [Creating Tables](#creating-tables)
- [Modifying Tables](#modifying-tables)
- [Column Types](#column-types)
- [Indexes & Constraints](#indexes--constraints)
- [Foreign Keys](#foreign-keys)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

---

## 🏗️ Creating Tables

### Basic Table Creation

```php
$schema = new SchemaBuilder($db);

// Start with table name, then add columns
$schema->increments('id')                    // Primary key
       ->string('title', 255)                // VARCHAR(255)
       ->text('content')                     // TEXT
       ->boolean('published')->default(false) // TINYINT(1)
       ->timestamps()                        // created_at, updated_at
       ->createTable('blog_posts');          // Create the table
```

### Enterprise User Table

```php
$schema->increments('id')
       ->uuid('external_id')->unique()              // External system ID
       ->string('username', 50)->unique()           // Unique username
       ->string('email', 150)->unique()             // Unique email
       ->string('first_name', 50)                   // First name
       ->string('last_name', 50)                    // Last name
       ->string('phone', 20)->nullable()            // Optional phone
       ->date('birth_date')->nullable()             // Birth date
       ->boolean('is_active')->default(true)        // Account status
       ->boolean('email_verified')->default(false)  // Email verification
       ->json('preferences')->nullable()            // User preferences
       ->ipAddress('last_login_ip')->nullable()     // Last login IP
       ->datetime('last_login_at')->nullable()      // Last login time
       ->rememberToken()                            // "Remember me" token
       ->timestamps()                               // Created/updated
       ->softDeletes()                              // Soft delete support
       ->createTable('users');
```

### E-commerce Product Table

```php
$schema->increments('id')
       ->string('sku', 50)->unique()                // Product SKU
       ->string('name', 255)                        // Product name
       ->text('description')                        // Description
       ->text('short_description')->nullable()      // Short description
       ->decimal('price', 10, 2)->unsigned()        // Price (2 decimal places)
       ->decimal('compare_price', 10, 2)->nullable()->unsigned() // Compare price
       ->integer('stock_quantity')->unsigned()->default(0)      // Inventory
       ->boolean('track_inventory')->default(true)  // Track stock?
       ->boolean('is_active')->default(true)        // Product status
       ->json('attributes')->nullable()             // Product attributes
       ->json('variants')->nullable()               // Product variants
       ->decimal('weight', 8, 3)->nullable()        // Weight in kg
       ->string('status', 20)->default('draft')     // Status
       ->integer('category_id')->unsigned()         // Category reference
       ->timestamps()                               // Created/updated
       ->createTable('products');
```

### Geographic Location Table

```php
$schema->increments('id')
       ->string('name', 255)                        // Location name
       ->string('address', 500)                     // Full address
       ->string('city', 100)                        // City
       ->string('state', 100)                       // State/Province
       ->string('country', 2)                       // ISO country code
       ->string('postal_code', 20)                  // ZIP/Postal code
       ->point('coordinates')                       // Lat/Lng coordinates
       ->polygon('delivery_area')->nullable()       // Delivery boundaries
       ->decimal('latitude', 10, 8)                 // Separate latitude
       ->decimal('longitude', 11, 8)                // Separate longitude
       ->boolean('is_active')->default(true)        // Location status
       ->json('metadata')->nullable()               // Additional data
       ->timestamps()
       ->createTable('locations');
```

---

## 🔧 Modifying Tables

### Adding Columns

```php
use SimpleMDB\TableAlter;

$alter = new TableAlter($db, 'users');

// Add new columns to existing table
$alter->addColumn('string', 'middle_name', 50, ['nullable' => true, 'after' => 'first_name'])
      ->addColumn('boolean', 'newsletter_opt_in', null, ['default' => false])
      ->addColumn('json', 'social_profiles', null, ['nullable' => true])
      ->execute();
```

### Modifying Columns

```php
$alter = new TableAlter($db, 'products');

// Modify existing columns
$alter->modifyColumn('description', 'mediumText')           // Change to MEDIUMTEXT
      ->modifyColumn('price', 'decimal', 12, 2)             // Increase precision
      ->modifyColumn('status', 'string', 30)                // Increase length
      ->execute();
```

### Dropping Columns

```php
$alter = new TableAlter($db, 'users');

// Remove columns
$alter->dropColumn('middle_name')
      ->dropColumn('legacy_field')
      ->execute();
```

### Renaming Columns

```php
$alter = new TableAlter($db, 'products');

// Rename columns
$alter->renameColumn('old_name', 'new_name')
      ->renameColumn('product_name', 'title')
      ->execute();
```

---

## 📊 Column Types Reference

### Numeric Types

```php
// Auto-increment primary key
$schema->increments('id');

// Integer types
$schema->integer('quantity');                    // Standard int
$schema->bigInteger('large_number');            // Big int
$schema->tinyInteger('small_number');           // Tiny int
$schema->smallInteger('medium_number');         // Small int
$schema->mediumInteger('another_number');       // Medium int

// Decimal types
$schema->decimal('price', 10, 2);               // Exact decimal
$schema->float('rating');                       // Floating point
$schema->double('precise_calculation');         // Double precision

// Unsigned variants
$schema->integer('user_id')->unsigned();        // Positive only
```

### String Types

```php
// Variable length strings
$schema->string('name', 100);                   // VARCHAR
$schema->text('description');                   // TEXT
$schema->mediumText('article');                 // MEDIUMTEXT
$schema->longText('book_content');              // LONGTEXT

// Fixed length strings
$schema->char('country_code', 2);               // CHAR

// Special string types
$schema->enum('status', ['active', 'inactive']); // ENUM
$schema->set('permissions', ['read', 'write']);  // SET
```

### Date & Time Types

```php
$schema->date('birth_date');                    // Date only
$schema->datetime('event_start');               // Date and time
$schema->timestamp('last_login');               // Timestamp
$schema->time('opening_hours');                 // Time only
$schema->year('graduation_year');               // Year only

// Convenience timestamps
$schema->timestamps();                          // created_at, updated_at
$schema->softDeletes();                         // deleted_at
```

### Modern Types

```php
$schema->json('metadata');                      // JSON storage
$schema->uuid('external_id');                   // UUID
$schema->boolean('is_active');                  // Boolean
$schema->binary('file_hash', 32);               // Binary data
$schema->ipAddress('client_ip');                // IPv4/IPv6
$schema->macAddress('device_mac');              // MAC address
$schema->url('website');                        // URL
```

### Geographic Types

```php
$schema->point('location');                     // Geographic point
$schema->polygon('boundaries');                 // Polygon area
$schema->geometry('shape');                     // General geometry
$schema->lineString('route');                   // Line/path
$schema->multiPoint('locations');               // Multiple points
```

---

## 🔑 Indexes & Constraints

### Adding Indexes

```php
$schema->increments('id')
       ->string('email', 150)
       ->string('username', 50)
       ->string('first_name', 50)
       ->string('last_name', 50)
       ->boolean('is_active')
       ->timestamps()
       
       // Add indexes
       ->unique('email')                         // Unique index on email
       ->unique('username')                      // Unique index on username
       ->index('is_active')                      // Regular index
       ->index(['first_name', 'last_name'])     // Composite index
       ->fullText('first_name', 'last_name')    // Full-text search
       
       ->createTable('users');
```

### Index Types

```php
// Unique constraints
$schema->string('email')->unique();             // Single column unique
$schema->unique(['username', 'domain']);        // Composite unique

// Regular indexes
$schema->string('status')->index();             // Single column index
$schema->index(['category_id', 'is_active']);   // Composite index

// Full-text indexes (for search)
$schema->fullText('title');                     // Single column
$schema->fullText(['title', 'description']);    // Multiple columns

// Spatial indexes (for geographic data)
$schema->point('location')->spatialIndex();     // Spatial index
```

### Named Indexes

```php
$schema->string('email')
       ->unique('idx_unique_email')             // Named unique index
       
       ->string('category_id')
       ->index('idx_category_lookup')           // Named regular index
       
       ->createTable('products');
```

---

## 🔗 Foreign Keys

### Basic Foreign Keys

```php
$schema->increments('id')
       ->string('title')
       ->integer('user_id')->unsigned()         // Foreign key column
       ->integer('category_id')->unsigned()     // Another foreign key
       ->timestamps()
       
       // Define foreign key constraints
       ->foreign('user_id')
           ->references('id')
           ->on('users')
           ->onDelete('cascade')                // Delete posts when user deleted
           
       ->foreign('category_id')
           ->references('id')
           ->on('categories')
           ->onUpdate('cascade')                // Update when category ID changes
           ->onDelete('set null')               // Set to NULL when category deleted
           
       ->createTable('blog_posts');
```

### Foreign Key Actions

```php
// ON DELETE actions
->onDelete('cascade')        // Delete related records
->onDelete('set null')       // Set foreign key to NULL
->onDelete('restrict')       // Prevent deletion
->onDelete('no action')      // No action (default)

// ON UPDATE actions  
->onUpdate('cascade')        // Update related records
->onUpdate('set null')       // Set foreign key to NULL
->onUpdate('restrict')       // Prevent update
->onUpdate('no action')      // No action (default)
```

### Polymorphic Relationships

```php
// Comments table that can belong to posts OR products
$schema->increments('id')
       ->text('content')
       ->morphs('commentable')                  // Creates commentable_id and commentable_type
       ->timestamps()
       ->createTable('comments');

// Equivalent to:
$schema->increments('id')
       ->text('content')
       ->integer('commentable_id')->unsigned()
       ->string('commentable_type')
       ->timestamps()
       ->index(['commentable_id', 'commentable_type'])  // Composite index
       ->createTable('comments');
```

---

## 🚀 Advanced Features

### Table Options

```php
$schema->increments('id')
       ->string('name')
       ->timestamps()
       
       // Table-level options
       ->engine('InnoDB')                       // Storage engine
       ->charset('utf8mb4')                     // Character set
       ->collation('utf8mb4_unicode_ci')        // Collation
       ->comment('User accounts table')         // Table comment
       
       ->createTable('users');
```

### Conditional Table Creation

```php
// Only create if table doesn't exist
if (!$schema->hasTable('users')) {
    $schema->increments('id')
           ->string('name')
           ->createTable('users');
}

// Drop table if exists, then create
$schema->dropIfExists('temp_data');
$schema->increments('id')
       ->json('data')
       ->createTable('temp_data');
```

### Copying Table Structure

```php
// Create new table based on existing structure
$schema->createTableLike('users_backup', 'users');

// Copy structure and add modifications
$schema->createTableLike('users_archive', 'users');
$alter = new TableAlter($db, 'users_archive');
$alter->addColumn('string', 'archive_reason', 255)
      ->addColumn('timestamp', 'archived_at')
      ->execute();
```

### Table Information

```php
// Check if table exists
if ($schema->hasTable('users')) {
    echo "Users table exists";
}

// Check if column exists
if ($schema->hasColumn('users', 'email')) {
    echo "Email column exists in users table";
}

// Get table columns
$columns = $schema->getColumns('users');
foreach ($columns as $column) {
    echo "Column: {$column['name']} Type: {$column['type']}\n";
}
```

---

## 💼 Real-World Examples

### Blog System Schema

```php
// Categories table
$schema->increments('id')
       ->string('name', 100)->unique()
       ->string('slug', 120)->unique()
       ->text('description')->nullable()
       ->boolean('is_active')->default(true)
       ->timestamps()
       ->createTable('categories');

// Posts table
$schema->increments('id')
       ->string('title', 255)
       ->string('slug', 275)->unique()
       ->text('excerpt')->nullable()
       ->longText('content')
       ->enum('status', ['draft', 'published', 'archived'])
       ->datetime('published_at')->nullable()
       ->integer('user_id')->unsigned()
       ->integer('category_id')->unsigned()
       ->json('meta')->nullable()
       ->integer('view_count')->unsigned()->default(0)
       ->boolean('featured')->default(false)
       ->timestamps()
       ->foreign('user_id')->references('id')->on('users')->onDelete('cascade')
       ->foreign('category_id')->references('id')->on('categories')
       ->index(['status', 'published_at'])
       ->fullText(['title', 'content'])
       ->createTable('posts');

// Tags table
$schema->increments('id')
       ->string('name', 50)->unique()
       ->string('slug', 60)->unique()
       ->timestamps()
       ->createTable('tags');

// Post-Tag pivot table
$schema->increments('id')
       ->integer('post_id')->unsigned()
       ->integer('tag_id')->unsigned()
       ->timestamps()
       ->foreign('post_id')->references('id')->on('posts')->onDelete('cascade')
       ->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade')
       ->unique(['post_id', 'tag_id'])
       ->createTable('post_tags');
```

### E-commerce Schema

```php
// Customers
$schema->increments('id')
       ->string('first_name', 50)
       ->string('last_name', 50)
       ->string('email', 150)->unique()
       ->string('phone', 20)->nullable()
       ->date('birth_date')->nullable()
       ->boolean('is_active')->default(true)
       ->json('preferences')->nullable()
       ->timestamps()
       ->createTable('customers');

// Orders
$schema->increments('id')
       ->string('order_number', 20)->unique()
       ->integer('customer_id')->unsigned()
       ->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])
       ->decimal('subtotal', 10, 2)->unsigned()
       ->decimal('tax_amount', 10, 2)->unsigned()
       ->decimal('shipping_amount', 10, 2)->unsigned()
       ->decimal('total_amount', 10, 2)->unsigned()
       ->json('billing_address')
       ->json('shipping_address')
       ->datetime('shipped_at')->nullable()
       ->datetime('delivered_at')->nullable()
       ->timestamps()
       ->foreign('customer_id')->references('id')->on('customers')
       ->index(['status', 'created_at'])
       ->createTable('orders');

// Order Items
$schema->increments('id')
       ->integer('order_id')->unsigned()
       ->integer('product_id')->unsigned()
       ->string('product_name', 255)     // Snapshot of product name
       ->string('product_sku', 50)       // Snapshot of SKU
       ->decimal('unit_price', 10, 2)->unsigned()
       ->integer('quantity')->unsigned()
       ->decimal('total_price', 10, 2)->unsigned()
       ->json('product_options')->nullable()  // Size, color, etc.
       ->timestamps()
       ->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')
       ->foreign('product_id')->references('id')->on('products')
       ->createTable('order_items');
```

---

## 🎯 Best Practices

### 1. Naming Conventions

```php
// ✅ Good naming
$schema->increments('id');              // Simple primary key
$schema->string('first_name');          // Descriptive, snake_case
$schema->integer('user_id');            // Clear foreign key
$schema->timestamps();                  // Standard timestamps

// ❌ Poor naming
$schema->increments('ID');              // Avoid uppercase
$schema->string('fName');               // Unclear abbreviation
$schema->integer('uid');                // Ambiguous
```

### 2. Data Type Selection

```php
// ✅ Appropriate types
$schema->decimal('price', 10, 2);       // Money - use decimal
$schema->string('email', 150);          // Reasonable length
$schema->boolean('is_active');          // Clear boolean
$schema->json('metadata');              // Flexible data

// ❌ Poor type choices
$schema->float('price');                // Never use float for money
$schema->string('email', 1000);         // Unnecessarily long
$schema->string('is_active', 10);       // Use boolean instead
```

### 3. Index Strategy

```php
// ✅ Strategic indexing
$schema->string('email')->unique();         // Unique constraint
$schema->integer('user_id')->index();       // Foreign key index
$schema->index(['category_id', 'status']);  // Composite for queries
$schema->fullText(['title', 'content']);    // Search functionality

// ❌ Over-indexing
$schema->string('description')->index();    // Long text shouldn't be indexed
$schema->index('random_field');            // Unused index
```

### 4. Foreign Key Best Practices

```php
// ✅ Proper foreign keys
$schema->integer('user_id')->unsigned()
       ->foreign('user_id')
           ->references('id')
           ->on('users')
           ->onDelete('cascade');          // Clear deletion behavior

// ✅ Consider referential integrity
$schema->integer('category_id')->unsigned()->nullable()
       ->foreign('category_id')
           ->references('id')
           ->on('categories')
           ->onDelete('set null');         // Don't orphan records
```

### 5. Migration-Friendly Design

```php
// ✅ Add nullable columns when modifying existing tables
$alter = new TableAlter($db, 'users');
$alter->addColumn('string', 'phone', 20, ['nullable' => true])  // Safe addition
      ->execute();

// ✅ Use descriptive migration names
// Migration_20240101_120000_AddPhoneToUsersTable.php
// Migration_20240101_130000_CreateOrdersTable.php
```

### 6. Performance Considerations

```php
// ✅ Optimize for your queries
$schema->index(['user_id', 'created_at']);   // If you query by user and date
$schema->index(['status', 'priority']);      // If you filter by status and priority

// ✅ Use appropriate field sizes
$schema->string('name', 100);               // Sufficient for names
$schema->string('slug', 255);               // URLs can be long
$schema->text('description');               // Variable length content
```

---

## 🚨 Common Pitfalls

### 1. Money and Decimals
```php
// ❌ NEVER use float for money
$schema->float('price');

// ✅ Always use decimal for money
$schema->decimal('price', 10, 2);
```

### 2. Foreign Key Column Types
```php
// ❌ Mismatched types
$schema->bigInteger('user_id');         // users.id is INT
$schema->string('category_id');         // categories.id is INT

// ✅ Matching types
$schema->integer('user_id')->unsigned(); // Matches users.id
$schema->integer('category_id')->unsigned(); // Matches categories.id
```

### 3. Index Overuse
```php
// ❌ Too many indexes
$schema->string('first_name')->index();
$schema->string('last_name')->index();
$schema->string('email')->index();
$schema->string('phone')->index();

// ✅ Strategic indexing
$schema->string('email')->unique();     // Business requirement
$schema->index(['first_name', 'last_name']); // Composite for searches
```

---

## 🔍 Troubleshooting

### Common Errors

**"Table already exists"**
```php
// Check before creating
if (!$schema->hasTable('users')) {
    $schema->createTable('users');
}

// Or use dropIfExists
$schema->dropIfExists('users');
$schema->createTable('users');
```

**"Column already exists"**
```php
// Check before adding
if (!$schema->hasColumn('users', 'phone')) {
    $alter = new TableAlter($db, 'users');
    $alter->addColumn('string', 'phone', 20)->execute();
}
```

**"Foreign key constraint fails"**
```php
// Ensure referenced table exists first
$schema->createTable('users');      // Create parent table first
$schema->createTable('posts');      // Then child table with foreign key
```

---

## 📚 Next Steps

- **[Master Query Builder](query-builder.md)** - Build powerful queries
- **[Learn Migrations](migrations.md)** - Version control your schema
- **[Explore Data Types](data-types.md)** - Complete data type reference
- **[Implement Security](security.md)** - Secure your database 