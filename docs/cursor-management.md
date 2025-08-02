# Cursor Management in SimpleMDB

## Overview

SimpleMDB provides comprehensive cursor management capabilities to ensure proper resource handling and memory efficiency when working with database queries.

## Available Methods

### `closeCursor(): self`

Closes the current cursor and frees associated resources. This method is essential for:

- **Memory Management**: Prevents memory leaks from unclosed cursors
- **Resource Cleanup**: Frees database resources after query execution
- **Performance**: Improves performance by releasing database handles

#### Usage Examples

```php
// Basic cursor management
$db = DatabaseFactory::create('mysqli', $host, $user, $pass, $database);

// Execute a query
$result = $db->query("SELECT * FROM users WHERE active = 1");

// Fetch some data
$users = $result->fetchAll('assoc');

// Close the cursor to free resources
$result->closeCursor();

// Continue with other operations
$count = $db->query("SELECT COUNT(*) as count FROM users")->fetch('assoc');
```

#### Advanced Usage

```php
// In a loop with cursor management
$db = DatabaseFactory::create('pdo', $host, $user, $pass, $database);

$result = $db->query("SELECT id, name, email FROM users");

while ($user = $result->fetch('assoc')) {
    // Process user data
    echo "Processing user: " . $user['name'] . "\n";
    
    // Perform additional operations for each user
    $orders = $db->query("SELECT * FROM orders WHERE user_id = ?", [$user['id']]);
    $orderCount = $orders->numRows();
    $orders->closeCursor(); // Close cursor after each iteration
    
    echo "User has {$orderCount} orders\n";
}

// Close the main cursor
$result->closeCursor();
```

### `freeResult(): self`

Frees the result set and associated memory. This is similar to `closeCursor()` but specifically for result sets.

```php
$result = $db->query("SELECT * FROM large_table");
$data = $result->fetchAll('assoc');

// Free the result set
$result->freeResult();
```

### `closeStmt(): self`

Closes the prepared statement and frees associated resources.

```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([123]);
$user = $stmt->fetch('assoc');

// Close the prepared statement
$stmt->closeStmt();
```

## Best Practices

### 1. Always Close Cursors After Use

```php
// ❌ Bad - Cursor not closed
$result = $db->query("SELECT * FROM users");
$users = $result->fetchAll('assoc');
// Cursor remains open

// ✅ Good - Cursor properly closed
$result = $db->query("SELECT * FROM users");
$users = $result->fetchAll('assoc');
$result->closeCursor(); // Explicitly close cursor
```

### 2. Use in Loops

```php
// ✅ Good - Close cursors in loops
$users = $db->query("SELECT id FROM users")->fetchAll('assoc');

foreach ($users as $user) {
    $orders = $db->query("SELECT * FROM orders WHERE user_id = ?", [$user['id']]);
    $orderData = $orders->fetchAll('assoc');
    $orders->closeCursor(); // Close after each iteration
    
    // Process order data
}
```

### 3. Error Handling

```php
try {
    $result = $db->query("SELECT * FROM users");
    $users = $result->fetchAll('assoc');
    
    // Process data...
    
} catch (Exception $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
} finally {
    // Always close cursor, even if error occurs
    if (isset($result)) {
        $result->closeCursor();
    }
}
```

### 4. Transaction Management

```php
$db->beginTransaction();

try {
    $result1 = $db->query("SELECT * FROM users WHERE active = 1");
    $activeUsers = $result1->fetchAll('assoc');
    $result1->closeCursor();
    
    $result2 = $db->query("SELECT * FROM orders WHERE user_id IN (?)", [array_column($activeUsers, 'id')]);
    $orders = $result2->fetchAll('assoc');
    $result2->closeCursor();
    
    // Process data...
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

## Implementation Differences

### SimpleMySQLi Implementation

```php
public function closeCursor(): self
{
    if ($this->stmtResult) {
        $this->stmtResult->free();
    }
    return $this;
}
```

- Uses `mysqli_result::free()` to free the result set
- Handles MySQLi-specific resource management

### SimplePDO Implementation

```php
public function closeCursor(): self
{
    if ($this->stmt) {
        $this->stmt->closeCursor();
    }
    return $this;
}
```

- Uses `PDOStatement::closeCursor()` to close the cursor
- Handles PDO-specific resource management

## Performance Benefits

### Memory Efficiency

```php
// Without cursor management
$largeResult = $db->query("SELECT * FROM large_table"); // Holds result in memory
$data = $largeResult->fetchAll('assoc'); // Loads all data into memory
// Result set remains in memory until script ends

// With cursor management
$largeResult = $db->query("SELECT * FROM large_table");
$data = $largeResult->fetchAll('assoc');
$largeResult->closeCursor(); // Immediately frees memory
```

### Connection Pool Efficiency

```php
// Proper cursor management allows connection reuse
for ($i = 0; $i < 1000; $i++) {
    $result = $db->query("SELECT * FROM users WHERE id = ?", [$i]);
    $user = $result->fetch('assoc');
    $result->closeCursor(); // Free connection for next query
    
    // Process user data...
}
```

## Error Handling

### Common Issues

1. **Cursor Already Closed**
   ```php
   $result = $db->query("SELECT * FROM users");
   $result->closeCursor();
   $result->closeCursor(); // May cause warning/error
   ```

2. **Null Statement**
   ```php
   $result = null;
   $result->closeCursor(); // Will cause error
   ```

### Safe Implementation

```php
public function closeCursor(): self
{
    if ($this->stmt && $this->stmt instanceof PDOStatement) {
        $this->stmt->closeCursor();
    }
    return $this;
}
```

## Migration from Other Libraries

### From Raw PDO

```php
// Raw PDO
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll();
$stmt->closeCursor(); // Manual cursor management

// SimpleMDB
$result = $db->query("SELECT * FROM users");
$users = $result->fetchAll('assoc');
$result->closeCursor(); // Same concept, cleaner syntax
```

### From MySQLi

```php
// Raw MySQLi
$result = $mysqli->query("SELECT * FROM users");
$users = $result->fetch_all(MYSQLI_ASSOC);
$result->free(); // Manual result freeing

// SimpleMDB
$result = $db->query("SELECT * FROM users");
$users = $result->fetchAll('assoc');
$result->closeCursor(); // Same concept, cleaner syntax
```

## Summary

The `closeCursor()` method is an essential part of SimpleMDB's resource management system. It provides:

- **Memory Efficiency**: Prevents memory leaks
- **Resource Cleanup**: Frees database handles
- **Performance**: Improves overall application performance
- **Consistency**: Works across both PDO and MySQLi implementations

Always remember to close cursors after use, especially in loops or when working with large datasets. 