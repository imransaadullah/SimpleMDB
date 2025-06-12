# Simple-MySQLi-Fork

A modern, type-safe fork of Simple-MySQLi with PDO support and improved features.

## Features

- **Dual Database Support**: Choose between MySQLi and PDO implementations
- **Type Safety**: Full PHP type hints and return types
- **Modern PHP**: Requires PHP 8.0 or higher
- **SSL Support**: Built-in SSL connection support
- **Factory Pattern**: Easy database instance creation
- **Consistent Interface**: Same API for both MySQLi and PDO
- **Transaction Support**: Full transaction management
- **Query Building**: Helper methods for common operations
- **Error Handling**: Comprehensive exception handling
- **Interface Compatibility**: Both implementations conform to DatabaseInterface

## Installation

```bash
composer require your-vendor/simple-mysqli-fork
```

## Quick Start

```php
use SimpleMDB\DatabaseFactory;

// Create a MySQLi instance
$db = DatabaseFactory::create(
    type: DatabaseFactory::TYPE_MYSQLI,
    host: 'localhost',
    username: 'user',
    password: 'pass',
    database: 'mydb'
);

// Or create a PDO instance
$db = DatabaseFactory::create(
    type: DatabaseFactory::TYPE_PDO,
    host: 'localhost',
    username: 'user',
    password: 'pass',
    database: 'mydb'
);

// With SSL
$db = DatabaseFactory::create(
    type: DatabaseFactory::TYPE_MYSQLI,
    host: 'localhost',
    username: 'user',
    password: 'pass',
    database: 'mydb',
    sslOptions: [
        'enable' => true,
        'key' => '/path/to/client-key.pem',
        'cert' => '/path/to/client-cert.pem',
        'ca' => '/path/to/ca-cert.pem',
        'verify_cert' => true
    ]
);
```

## Basic Usage

```php
// Query with parameters
$db->query("SELECT * FROM users WHERE id = ?", [1])
   ->fetch('assoc');

// Insert data
$db->write_data('users', [
    'name' => 'John',
    'email' => 'john@example.com'
]);

// Update data
$db->update('users', 
    ['name' => 'John Doe'],
    'WHERE id = ?',
    [1]
);

// Transactions
$db->transaction(function($db) {
    $db->write_data('users', ['name' => 'John']);
    $db->write_data('profiles', ['user_id' => $db->lastInsertId()]);
});
```

## Available Fetch Types

- `assoc`: Associative array
- `obj`: Object array
- `num`: Number array
- `col`: 1D array (PDO::FETCH_COLUMN)
- `keyPair`: Unique key to single value (PDO::FETCH_KEY_PAIR)
- `keyPairArr`: Unique key to array (PDO::FETCH_UNIQUE)
- `group`: Group by common values (PDO::FETCH_GROUP)
- `groupCol`: Group by common values into 1D array
- `groupObj`: Group by common values into object arrays

## Error Handling

```php
try {
    $db->query("SELECT * FROM non_existent_table");
} catch (SimpleMySQLiException $e) {
    // Handle MySQLi specific errors
} catch (SimplePDOException $e) {
    // Handle PDO specific errors
} catch (Exception $e) {
    // Handle other errors
}
```

## Return Types

All methods return consistent types across both implementations:

- `query()`: Returns `self` for method chaining
- `execute()`: Returns `self` for method chaining
- `insert()`: Returns `array|bool` (SQL and values array, or false on error)
- `update()`: Returns `self|bool` (instance for chaining, or false on error)
- `read_data_all()`: Returns `array|bool` (result array, or false on error)
- `write_data()`: Returns `self|bool` (instance for chaining, or false on error)

## Detailed Usage Examples

### Basic Queries

```php
// Simple select
$db->query("SELECT * FROM users")->fetchAll('assoc');

// With parameters
$db->query("SELECT * FROM users WHERE id = ?", [1])->fetch('assoc');

// Multiple parameters
$db->query("SELECT * FROM users WHERE id = ? AND status = ?", [1, 'active'])->fetch('assoc');
```

### Data Manipulation

```php
// Insert
$db->write_data('users', [
    'name' => 'John',
    'email' => 'john@example.com'
]);

// Update
$db->update('users', 
    ['name' => 'John Doe'],
    'WHERE id = ?',
    [1]
);

// Delete
$db->query("DELETE FROM users WHERE id = ?", [1]);
```

### Transactions

```php
// Simple transaction
$db->transaction(function($db) {
    $db->write_data('users', ['name' => 'John']);
    $db->write_data('profiles', ['user_id' => $db->lastInsertId()]);
});

// Manual transaction
$db->beginTransaction();
try {
    $db->write_data('users', ['name' => 'John']);
    $db->write_data('profiles', ['user_id' => $db->lastInsertId()]);
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Advanced Queries

```php
// IN clause
$ids = [1, 2, 3];
$db->query("SELECT * FROM users WHERE id IN (" . $db->whereIn($ids) . ")", $ids)
   ->fetchAll('assoc');

// Multiple fetch types
$db->query("SELECT id, name FROM users")->fetchAll('keyPair');
$db->query("SELECT category, name FROM products")->fetchAll('group');
```

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer

## License

MIT License
