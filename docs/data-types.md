# Data Types Reference

SimpleMDB supports 25+ modern data types to handle every use case, from basic strings to advanced geographic data. This reference covers every data type with practical examples and best practices.

## 📋 Quick Reference

| Category | Types | Use Cases |
|----------|-------|-----------|
| **Numeric** | `increments`, `integer`, `bigInteger`, `decimal`, `float`, `double` | IDs, quantities, prices, calculations |
| **Text** | `string`, `text`, `mediumText`, `longText`, `char` | Names, descriptions, content |
| **Date/Time** | `date`, `datetime`, `timestamp`, `time`, `year` | Timestamps, scheduling, logging |
| **Boolean** | `boolean` | Flags, states, permissions |
| **Binary** | `binary`, `blob`, `mediumBlob`, `longBlob` | Files, images, documents |
| **Modern** | `json`, `uuid`, `ipAddress`, `macAddress`, `url` | APIs, tracking, networking |
| **Geographic** | `point`, `polygon`, `geometry`, `lineString`, `multiPoint` | Maps, locations, boundaries |
| **Special** | `enum`, `set`, `morphs`, `rememberToken`, `softDeletes` | Relationships, authentication |

---

## 🔢 Numeric Types

### `increments(column)`
Auto-incrementing primary key (UNSIGNED INT AUTO_INCREMENT).

```php
$schema->increments('id');
// Creates: id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

**Use Cases:**
- Primary keys for all tables
- Auto-generated unique identifiers

### `integer(column)` / `int(column)`
Standard integer storage (-2,147,483,648 to 2,147,483,647).

```php
$schema->integer('quantity');
$schema->integer('user_id')->unsigned();  // 0 to 4,294,967,295
$schema->integer('balance')->default(0);
```

**Use Cases:**
- Quantities, counts, foreign keys
- User IDs, product quantities
- Status codes, enum values

### `bigInteger(column)`
Large integer storage (-9,223,372,036,854,775,808 to 9,223,372,036,854,775,807).

```php
$schema->bigInteger('file_size');
$schema->bigInteger('transaction_id')->unsigned();
```

**Use Cases:**
- File sizes, large counters
- High-volume transaction IDs
- Social media metrics (likes, views)

### `decimal(column, precision, scale)`
Exact decimal numbers for financial calculations.

```php
$schema->decimal('price', 10, 2);        // 99999999.99
$schema->decimal('tax_rate', 5, 4);      // 9.9999 (percentage)
$schema->decimal('balance', 15, 2);      // Large monetary values
```

**Use Cases:**
- **Money/prices** (always use decimal, never float)
- Tax rates, percentages
- Financial calculations requiring precision

### `float(column)` / `double(column)`
Floating-point numbers (approximate values).

```php
$schema->float('rating');               // Product ratings
$schema->double('coordinates_lat');     // Geographic coordinates
```

**⚠️ Warning:** Never use for money! Use `decimal` instead.

**Use Cases:**
- Scientific calculations
- Geographic coordinates
- Ratings, averages (non-financial)

---

## 📝 Text Types

### `string(column, length = 255)`
Variable-length string (VARCHAR).

```php
$schema->string('name', 100);           // User names
$schema->string('email', 150)->unique(); // Email addresses
$schema->string('slug', 255)->unique(); // URL slugs
$schema->string('status', 20)->default('active'); // Status values
```

**Use Cases:**
- Names, emails, titles
- Short descriptions, tags
- Status values, categories

### `char(column, length)`
Fixed-length string (CHAR).

```php
$schema->char('country_code', 2);       // ISO country codes (US, UK)
$schema->char('currency', 3);           // Currency codes (USD, EUR)
$schema->char('grade', 1);              // Single letter grades
```

**Use Cases:**
- Country codes, currency codes
- Fixed-format identifiers
- Single character flags

### `text(column)`
Large text content (up to 65,535 characters).

```php
$schema->text('description');           // Product descriptions
$schema->text('content');               // Blog post content
$schema->text('notes')->nullable();     // Optional notes
```

**Use Cases:**
- Blog posts, comments
- Product descriptions
- User-generated content

### `mediumText(column)`
Larger text content (up to 16,777,215 characters).

```php
$schema->mediumText('article_content'); // Long articles
$schema->mediumText('serialized_data'); // Serialized objects
```

### `longText(column)`
Massive text content (up to 4,294,967,295 characters).

```php
$schema->longText('full_document');     // Complete documents
$schema->longText('log_data');          // Extensive log files
```

---

## 📅 Date & Time Types

### `date(column)`
Date only (YYYY-MM-DD).

```php
$schema->date('birth_date');            // User birth dates
$schema->date('event_date');            // Event scheduling
$schema->date('expiry_date')->nullable(); // Subscription expiry
```

### `datetime(column)`
Date and time (YYYY-MM-DD HH:MM:SS).

```php
$schema->datetime('created_at');        // Record creation time
$schema->datetime('scheduled_at')->nullable(); // Scheduled tasks
```

### `timestamp(column)`
Timestamp with automatic updates.

```php
$schema->timestamp('updated_at')
       ->default('CURRENT_TIMESTAMP')
       ->onUpdate('CURRENT_TIMESTAMP'); // Auto-update on changes
```

### `timestamps()`
Convenience method for created_at and updated_at.

```php
$schema->timestamps();
// Creates both:
// created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### `time(column)`
Time only (HH:MM:SS).

```php
$schema->time('opening_time');          // Business hours
$schema->time('duration');              // Event duration
```

### `year(column)`
Year only (1901 to 2155).

```php
$schema->year('graduation_year');       // Graduation years
$schema->year('model_year');            // Vehicle model years
```

---

## ✅ Boolean Type

### `boolean(column)`
True/false values (stored as TINYINT(1)).

```php
$schema->boolean('is_active')->default(true);
$schema->boolean('is_verified')->default(false);
$schema->boolean('email_notifications')->default(true);
```

**Use Cases:**
- Feature flags, permissions
- Status indicators
- User preferences

---

## 📄 Binary Types

### `binary(column, length)`
Fixed-length binary data.

```php
$schema->binary('file_hash', 32);       // SHA-256 hashes
$schema->binary('encryption_key', 16);  // AES-128 keys
```

### `blob(column)`
Binary large object (up to 65,535 bytes).

```php
$schema->blob('thumbnail');             // Small images
$schema->blob('file_data');             // Small files
```

### `mediumBlob(column)` / `longBlob(column)`
Larger binary storage.

```php
$schema->mediumBlob('image_data');      // Medium images
$schema->longBlob('video_data');        // Large files
```

---

## 🚀 Modern Types

### `json(column)`
Native JSON storage and querying.

```php
$schema->json('metadata');              // Flexible data storage
$schema->json('preferences');           // User preferences
$schema->json('api_response');          // API response caching
```

**Example Usage:**
```php
// Store JSON data
$user = [
    'name' => 'John Doe',
    'preferences' => json_encode([
        'theme' => 'dark',
        'notifications' => true,
        'language' => 'en'
    ])
];

// Query JSON fields (MySQL 5.7+)
$darkThemeUsers = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where("JSON_EXTRACT(preferences, '$.theme') = ?", ['dark'])
    ->execute($db);
```

### `uuid(column)`
Universally unique identifier storage (36 characters).

```php
$schema->uuid('external_id')->unique(); // External API integration
$schema->uuid('session_id');            // Session tracking
```

**Example Usage:**
```php
// Generate UUID
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);
```

### `ipAddress(column)`
IPv4 and IPv6 address storage (45 characters max).

```php
$schema->ipAddress('user_ip');          // User IP tracking
$schema->ipAddress('server_ip');        // Server identification
```

**Supports:**
- IPv4: `192.168.1.1`
- IPv6: `2001:0db8:85a3:0000:0000:8a2e:0370:7334`

### `macAddress(column)`
MAC address storage (17 characters).

```php
$schema->macAddress('device_mac');      // Device identification
$schema->macAddress('network_card');    // Hardware tracking
```

**Format:** `AA:BB:CC:DD:EE:FF`

### `url(column)`
URL storage with validation (2048 characters max).

```php
$schema->url('website');                // User websites
$schema->url('avatar_url');             // Profile images
$schema->url('callback_url');           // API callbacks
```

---

## 🌍 Geographic Types

### `point(column)`
Geographic point (latitude, longitude).

```php
$schema->point('location');             // Store coordinates
```

**Example Usage:**
```php
// Store point data
$location = "POINT(40.7128 -74.0060)";  // New York City
SimpleQuery::create()
    ->insert(['name' => 'NYC Office', 'location' => $location])
    ->into('offices')
    ->execute($db);

// Find nearby points (within 10km)
$nearby = SimpleQuery::create()
    ->select(['*'])
    ->from('offices')
    ->where('ST_Distance_Sphere(location, POINT(?, ?)) <= ?', 
           [40.7580, -73.9855, 10000])  // 10km radius
    ->execute($db);
```

### `polygon(column)`
Geographic polygon for boundaries.

```php
$schema->polygon('delivery_area');      // Delivery boundaries
$schema->polygon('sales_territory');    // Sales regions
```

### `geometry(column)`
General geometric data.

```php
$schema->geometry('shape');             // Any geometric shape
```

### `lineString(column)`
Geographic lines (routes, paths).

```php
$schema->lineString('route');           // Delivery routes
$schema->lineString('boundary');        // Property boundaries
```

### `multiPoint(column)`
Multiple geographic points.

```php
$schema->multiPoint('locations');       // Multiple office locations
```

---

## 🎯 Special Types

### `enum(column, values)`
Predefined list of values.

```php
$schema->enum('status', ['draft', 'published', 'archived']);
$schema->enum('priority', ['low', 'medium', 'high', 'urgent']);
$schema->enum('size', ['XS', 'S', 'M', 'L', 'XL', 'XXL']);
```

**Use Cases:**
- Status values, priorities
- Size options, categories
- Any predefined list

### `set(column, values)`
Multiple values from predefined list.

```php
$schema->set('permissions', ['read', 'write', 'delete', 'admin']);
// Can store: 'read', 'read,write', 'read,write,delete', etc.
```

### `morphs(column)`
Polymorphic relationship fields.

```php
$schema->morphs('commentable');
// Creates:
// commentable_id INT UNSIGNED
// commentable_type VARCHAR(255)
```

**Example Usage:**
```php
// Comments can belong to posts OR products
$comment = [
    'content' => 'Great post!',
    'commentable_id' => 123,
    'commentable_type' => 'Post'
];
```

### `rememberToken()`
Authentication "remember me" token.

```php
$schema->rememberToken();
// Creates: remember_token VARCHAR(100) NULLABLE
```

### `softDeletes()`
Soft delete timestamp.

```php
$schema->softDeletes();
// Creates: deleted_at TIMESTAMP NULLABLE
```

---

## 🔧 Column Modifiers

Apply these modifiers to any column type:

### `nullable()`
Allow NULL values.

```php
$schema->string('middle_name')->nullable();
$schema->text('description')->nullable();
```

### `default(value)`
Set default value.

```php
$schema->boolean('is_active')->default(true);
$schema->string('status')->default('pending');
$schema->timestamp('created_at')->default('CURRENT_TIMESTAMP');
```

### `unique()`
Add unique constraint.

```php
$schema->string('email')->unique();
$schema->string('slug')->unique();
```

### `unsigned()`
Make numeric values unsigned (non-negative).

```php
$schema->integer('quantity')->unsigned();
$schema->decimal('price', 10, 2)->unsigned();
```

### `comment(text)`
Add column comment.

```php
$schema->string('external_id')->comment('Third-party system ID');
$schema->decimal('price', 10, 2)->comment('Price in USD');
```

### `after(column)`
Position column after specified column.

```php
$schema->string('middle_name')->after('first_name');
```

### `first()`
Position column first in table.

```php
$schema->integer('sort_order')->first();
```

---

## 🎯 Best Practices

### 1. Choose the Right Type
```php
// ✅ Correct
$schema->decimal('price', 10, 2);       // For money
$schema->string('email', 150);          // Appropriate length
$schema->boolean('is_active');          // For true/false

// ❌ Incorrect  
$schema->float('price');                // Never use for money!
$schema->string('email', 255);          // Unnecessarily long
$schema->string('is_active', 10);       // Use boolean instead
```

### 2. Use Appropriate Lengths
```php
// ✅ Good length choices
$schema->string('name', 100);           // Reasonable for names
$schema->string('email', 150);          // Standard email length
$schema->string('phone', 20);           // International phone numbers

// ❌ Poor length choices
$schema->string('name', 10);            // Too short
$schema->string('description', 50);     // Use text() instead
```

### 3. Set Sensible Defaults
```php
// ✅ Helpful defaults
$schema->boolean('is_active')->default(true);
$schema->string('status')->default('pending');
$schema->integer('sort_order')->default(0);

// ✅ Required vs optional
$schema->string('email');               // Required
$schema->string('phone')->nullable();   // Optional
```

### 4. Use Comments for Clarity
```php
$schema->string('external_id')
       ->comment('ID from third-party CRM system');
       
$schema->decimal('tax_rate', 5, 4)
       ->comment('Tax rate as decimal (e.g., 0.0825 for 8.25%)');
```

### 5. Geographic Data Best Practices
```php
// ✅ Use proper SRID for geographic data
$schema->point('location');

// Store as: POINT(longitude latitude)
// Note: Longitude first, then latitude!
$nyc = "POINT(-74.0060 40.7128)";
```

---

## 🔍 Data Type Selection Guide

### **For IDs:**
- `increments()` - Auto-increment primary keys
- `uuid()` - External system integration
- `bigInteger()` - High-volume systems

### **For Money:**
- `decimal(10, 2)` - Standard prices
- `decimal(15, 2)` - Large amounts
- **Never** use `float` or `double`

### **For Text:**
- `string(100)` - Names, short text
- `text()` - Descriptions, content
- `json()` - Structured data

### **For Flags:**
- `boolean()` - True/false values
- `enum()` - Multiple predefined states

### **For Files:**
- `string()` - File paths/URLs
- `blob()` - Small binary data
- `longBlob()` - Large files

### **For Locations:**
- `point()` - Single coordinates
- `polygon()` - Areas/boundaries
- `ipAddress()` - Network locations

---

**Next Steps:**
- 📖 [Learn Schema Builder](schema-builder.md)
- 🔍 [Master Query Builder](query-builder.md)
- 🛡️ [Implement Security](security.md) 