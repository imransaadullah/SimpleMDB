# Query Builder Reference

Master SimpleMDB's powerful query builder to create complex database queries with elegant, readable syntax. Build everything from simple SELECT statements to advanced subqueries with joins and aggregations.

## 🚀 Quick Start

```php
use SimpleMDB\SimpleQuery;

// Basic query
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->orderBy('created_at DESC')
    ->execute($db);
```

## 📋 Table of Contents

- [Basic Queries](#basic-queries)
- [Advanced SELECT](#advanced-select)
- [WHERE Conditions](#where-conditions)
- [JOINs](#joins)
- [Aggregations](#aggregations)
- [Subqueries](#subqueries)
- [Transactions](#transactions)
- [Performance Tips](#performance-tips)

---

## 🔍 Basic Queries

### SELECT Queries

```php
// Simple SELECT
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->execute($db);

// Specific columns
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->execute($db);

// Column aliases
$users = SimpleQuery::create()
    ->select(['id', 'name as full_name', 'email as email_address'])
    ->from('users')
    ->execute($db);

// Count records
$count = SimpleQuery::create()
    ->select(['COUNT(*) as total'])
    ->from('users')
    ->execute($db);

echo "Total users: " . $count[0]['total'];
```

### INSERT Queries

```php
// Single record
$userId = SimpleQuery::create()
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->into('users')
    ->execute($db);

echo "New user ID: $userId";

// Multiple records
$userIds = SimpleQuery::create()
    ->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ['name' => 'Bob Wilson', 'email' => 'bob@example.com']
    ])
    ->into('users')
    ->execute($db);

echo "Inserted " . count($userIds) . " users";
```

### UPDATE Queries

```php
// Update single record
$affected = SimpleQuery::create()
    ->update('users')
    ->set([
        'name' => 'John Smith',
        'updated_at' => date('Y-m-d H:i:s')
    ])
    ->where('id = ?', [123])
    ->execute($db);

echo "Updated $affected record(s)";

// Update multiple records
$affected = SimpleQuery::create()
    ->update('users')
    ->set(['is_active' => false])
    ->where('last_login < ?', ['2023-01-01'])
    ->execute($db);

echo "Deactivated $affected inactive users";

// Conditional update
$affected = SimpleQuery::create()
    ->update('posts')
    ->set(['status' => 'published'])
    ->where('status = ? AND scheduled_at <= ?', ['draft', date('Y-m-d H:i:s')])
    ->execute($db);
```

### DELETE Queries

```php
// Delete single record
$deleted = SimpleQuery::create()
    ->delete()
    ->from('users')
    ->where('id = ?', [123])
    ->execute($db);

echo "Deleted $deleted record(s)";

// Delete with conditions
$deleted = SimpleQuery::create()
    ->delete()
    ->from('users')
    ->where('is_active = ? AND created_at < ?', [false, '2023-01-01'])
    ->execute($db);

echo "Cleaned up $deleted old inactive users";

// Soft delete (update instead of delete)
$softDeleted = SimpleQuery::create()
    ->update('users')
    ->set(['deleted_at' => date('Y-m-d H:i:s')])
    ->where('id = ?', [123])
    ->execute($db);
```

---

## 🔧 Advanced SELECT

### DISTINCT Queries

```php
// Get unique values
$cities = SimpleQuery::create()
    ->select(['DISTINCT city'])
    ->from('users')
    ->execute($db);

// Distinct with multiple columns
$combinations = SimpleQuery::create()
    ->select(['DISTINCT country', 'city'])
    ->from('users')
    ->orderBy('country, city')
    ->execute($db);
```

### LIMIT and OFFSET

```php
// Basic pagination
$page = 2;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->orderBy('created_at DESC')
    ->limit($perPage)
    ->offset($offset)
    ->execute($db);

// Top N records
$topPosts = SimpleQuery::create()
    ->select(['*'])
    ->from('posts')
    ->orderBy('view_count DESC')
    ->limit(5)
    ->execute($db);
```

### ORDER BY

```php
// Single column ascending
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->orderBy('name')
    ->execute($db);

// Single column descending
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->orderBy('created_at DESC')
    ->execute($db);

// Multiple columns
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->orderBy('country ASC, city ASC, name ASC')
    ->execute($db);

// Custom ordering
$posts = SimpleQuery::create()
    ->select(['*'])
    ->from('posts')
    ->orderBy("FIELD(status, 'published', 'draft', 'archived')")
    ->execute($db);
```

---

## ⚡ WHERE Conditions

### Basic WHERE Clauses

```php
// Simple equality
$user = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('email = ?', ['john@example.com'])
    ->execute($db);

// Multiple conditions (AND)
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('is_active = ? AND created_at >= ?', [true, '2024-01-01'])
    ->execute($db);

// OR conditions
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('role = ? OR role = ?', ['admin', 'moderator'])
    ->execute($db);
```

### Advanced WHERE Operations

```php
// IN clause
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->whereIn('role', ['admin', 'editor', 'moderator'])
    ->execute($db);

// NOT IN clause
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->whereNotIn('status', ['banned', 'suspended'])
    ->execute($db);

// BETWEEN clause
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->whereBetween('created_at', ['2024-01-01', '2024-12-31'])
    ->execute($db);

// LIKE patterns
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('name LIKE ?', ['John%'])  // Starts with "John"
    ->execute($db);

$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('email LIKE ?', ['%@company.com'])  // Company emails
    ->execute($db);

// NULL checks
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('phone IS NOT NULL')
    ->execute($db);

$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('deleted_at IS NULL')  // Not soft deleted
    ->execute($db);
```

### Complex WHERE Logic

```php
// Grouped conditions
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('(role = ? OR role = ?) AND is_active = ?', ['admin', 'editor', true])
    ->execute($db);

// Date/time comparisons
$recentPosts = SimpleQuery::create()
    ->select(['*'])
    ->from('posts')
    ->where('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
    ->execute($db);

// Numeric comparisons
$expensiveProducts = SimpleQuery::create()
    ->select(['*'])
    ->from('products')
    ->where('price > ? AND discount_percent < ?', [100.00, 10])
    ->execute($db);
```

---

## 🔗 JOINs

### INNER JOIN

```php
// Basic inner join
$usersWithPosts = SimpleQuery::create()
    ->select(['u.name', 'u.email', 'p.title', 'p.created_at'])
    ->from('users u')
    ->join('posts p', 'u.id = p.user_id')
    ->where('u.is_active = ?', [true])
    ->execute($db);

// Multiple joins
$fullData = SimpleQuery::create()
    ->select(['u.name', 'p.title', 'c.name as category'])
    ->from('users u')
    ->join('posts p', 'u.id = p.user_id')
    ->join('categories c', 'p.category_id = c.id')
    ->execute($db);
```

### LEFT JOIN

```php
// Include users even without posts
$allUsers = SimpleQuery::create()
    ->select(['u.name', 'u.email', 'COUNT(p.id) as post_count'])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->groupBy('u.id')
    ->execute($db);

// Optional profile data
$usersWithProfiles = SimpleQuery::create()
    ->select(['u.*', 'p.bio', 'p.avatar_url'])
    ->from('users u')
    ->leftJoin('profiles p', 'u.id = p.user_id')
    ->execute($db);
```

### RIGHT JOIN

```php
// All posts with user data (if available)
$allPosts = SimpleQuery::create()
    ->select(['p.*', 'u.name as author_name'])
    ->from('users u')
    ->rightJoin('posts p', 'u.id = p.user_id')
    ->execute($db);
```

### Complex JOINs

```php
// Join with conditions
$activeUserPosts = SimpleQuery::create()
    ->select(['u.name', 'p.title'])
    ->from('users u')
    ->join('posts p', 'u.id = p.user_id AND p.status = ?', ['published'])
    ->where('u.is_active = ?', [true])
    ->execute($db);

// Self-join (hierarchical data)
$userWithManager = SimpleQuery::create()
    ->select(['u.name as employee', 'm.name as manager'])
    ->from('employees u')
    ->leftJoin('employees m', 'u.manager_id = m.id')
    ->execute($db);
```

---

## 📊 Aggregations

### Basic Aggregations

```php
// COUNT
$userCount = SimpleQuery::create()
    ->select(['COUNT(*) as total'])
    ->from('users')
    ->execute($db);

// COUNT with conditions
$activeCount = SimpleQuery::create()
    ->select(['COUNT(*) as active_users'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->execute($db);

// SUM
$totalRevenue = SimpleQuery::create()
    ->select(['SUM(amount) as total_revenue'])
    ->from('orders')
    ->where('status = ?', ['completed'])
    ->execute($db);

// AVG
$averagePrice = SimpleQuery::create()
    ->select(['AVG(price) as avg_price'])
    ->from('products')
    ->where('is_active = ?', [true])
    ->execute($db);

// MIN and MAX
$priceRange = SimpleQuery::create()
    ->select(['MIN(price) as min_price', 'MAX(price) as max_price'])
    ->from('products')
    ->execute($db);
```

### GROUP BY

```php
// Group by single column
$usersByCountry = SimpleQuery::create()
    ->select(['country', 'COUNT(*) as user_count'])
    ->from('users')
    ->groupBy('country')
    ->orderBy('user_count DESC')
    ->execute($db);

// Group by multiple columns
$salesByRegion = SimpleQuery::create()
    ->select(['country', 'city', 'SUM(amount) as total_sales'])
    ->from('orders')
    ->groupBy('country, city')
    ->execute($db);

// Group with JOIN
$postsByUser = SimpleQuery::create()
    ->select(['u.name', 'COUNT(p.id) as post_count'])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->groupBy('u.id')
    ->having('post_count > ?', [5])
    ->execute($db);
```

### HAVING Clause

```php
// Filter grouped results
$activeCategories = SimpleQuery::create()
    ->select(['category_id', 'COUNT(*) as product_count'])
    ->from('products')
    ->where('is_active = ?', [true])
    ->groupBy('category_id')
    ->having('product_count >= ?', [10])
    ->execute($db);

// Multiple HAVING conditions
$popularUsers = SimpleQuery::create()
    ->select(['user_id', 'COUNT(*) as post_count', 'AVG(view_count) as avg_views'])
    ->from('posts')
    ->groupBy('user_id')
    ->having('post_count > ? AND avg_views > ?', [20, 1000])
    ->execute($db);
```

---

## 🎯 Subqueries

### WHERE Subqueries

```php
// IN subquery
$usersWithPosts = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('id IN (SELECT DISTINCT user_id FROM posts WHERE status = ?)', ['published'])
    ->execute($db);

// EXISTS subquery
$activeUsers = SimpleQuery::create()
    ->select(['*'])
    ->from('users u')
    ->where('EXISTS (SELECT 1 FROM posts p WHERE p.user_id = u.id AND p.created_at >= ?)', ['2024-01-01'])
    ->execute($db);

// NOT EXISTS
$usersWithoutPosts = SimpleQuery::create()
    ->select(['*'])
    ->from('users u')
    ->where('NOT EXISTS (SELECT 1 FROM posts p WHERE p.user_id = u.id)')
    ->execute($db);
```

### SELECT Subqueries

```php
// Subquery in SELECT
$usersWithPostCount = SimpleQuery::create()
    ->select([
        'id',
        'name',
        'email',
        '(SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count'
    ])
    ->from('users')
    ->execute($db);

// Multiple subqueries
$userStats = SimpleQuery::create()
    ->select([
        'id',
        'name',
        '(SELECT COUNT(*) FROM posts WHERE user_id = users.id) as total_posts',
        '(SELECT COUNT(*) FROM posts WHERE user_id = users.id AND status = "published") as published_posts',
        '(SELECT MAX(created_at) FROM posts WHERE user_id = users.id) as last_post_date'
    ])
    ->from('users')
    ->execute($db);
```

### FROM Subqueries

```php
// Subquery in FROM clause
$averagePostsPerUser = SimpleQuery::create()
    ->select(['AVG(post_count) as avg_posts_per_user'])
    ->from('(SELECT user_id, COUNT(*) as post_count FROM posts GROUP BY user_id) as user_posts')
    ->execute($db);

// Complex derived table
$topAuthors = SimpleQuery::create()
    ->select(['*'])
    ->from('(
        SELECT u.id, u.name, COUNT(p.id) as post_count, SUM(p.view_count) as total_views
        FROM users u
        JOIN posts p ON u.id = p.user_id
        WHERE p.status = "published"
        GROUP BY u.id
        HAVING post_count >= 10
    ) as author_stats')
    ->orderBy('total_views DESC')
    ->limit(10)
    ->execute($db);
```

---

## 💼 Real-World Examples

### Blog System Queries

```php
// Get recent posts with author and category
$recentPosts = SimpleQuery::create()
    ->select([
        'p.id',
        'p.title',
        'p.excerpt',
        'p.created_at',
        'u.name as author',
        'c.name as category'
    ])
    ->from('posts p')
    ->join('users u', 'p.user_id = u.id')
    ->join('categories c', 'p.category_id = c.id')
    ->where('p.status = ? AND p.published_at <= ?', ['published', date('Y-m-d H:i:s')])
    ->orderBy('p.published_at DESC')
    ->limit(10)
    ->execute($db);

// Get popular posts from last month
$popularPosts = SimpleQuery::create()
    ->select([
        'p.*',
        'u.name as author',
        'COUNT(c.id) as comment_count'
    ])
    ->from('posts p')
    ->join('users u', 'p.user_id = u.id')
    ->leftJoin('comments c', 'p.id = c.post_id')
    ->where('p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)')
    ->groupBy('p.id')
    ->orderBy('p.view_count DESC, comment_count DESC')
    ->limit(5)
    ->execute($db);
```

### E-commerce Queries

```php
// Product search with filters
$products = SimpleQuery::create()
    ->select([
        'p.*',
        'c.name as category_name',
        'AVG(r.rating) as avg_rating',
        'COUNT(r.id) as review_count'
    ])
    ->from('products p')
    ->join('categories c', 'p.category_id = c.id')
    ->leftJoin('reviews r', 'p.id = r.product_id')
    ->where('p.is_active = ? AND p.price BETWEEN ? AND ?', [true, 50, 500])
    ->where('p.name LIKE ? OR p.description LIKE ?', ['%phone%', '%phone%'])
    ->groupBy('p.id')
    ->having('avg_rating >= ?', [4.0])
    ->orderBy('avg_rating DESC, review_count DESC')
    ->execute($db);

// Sales report
$salesReport = SimpleQuery::create()
    ->select([
        'DATE(o.created_at) as sale_date',
        'COUNT(o.id) as order_count',
        'SUM(o.total_amount) as daily_revenue',
        'AVG(o.total_amount) as avg_order_value'
    ])
    ->from('orders o')
    ->where('o.status = ? AND o.created_at >= ?', ['completed', '2024-01-01'])
    ->groupBy('DATE(o.created_at)')
    ->orderBy('sale_date DESC')
    ->execute($db);
```

### Analytics Queries

```php
// User engagement metrics
$userEngagement = SimpleQuery::create()
    ->select([
        'u.id',
        'u.name',
        'u.email',
        'COUNT(DISTINCT p.id) as posts_created',
        'COUNT(DISTINCT c.id) as comments_made',
        'COUNT(DISTINCT l.id) as likes_given',
        'MAX(u.last_login) as last_activity'
    ])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->leftJoin('comments c', 'u.id = c.user_id')
    ->leftJoin('likes l', 'u.id = l.user_id')
    ->where('u.created_at >= ?', ['2024-01-01'])
    ->groupBy('u.id')
    ->orderBy('posts_created DESC, comments_made DESC')
    ->execute($db);

// Content performance
$contentMetrics = SimpleQuery::create()
    ->select([
        'p.id',
        'p.title',
        'p.view_count',
        'COUNT(DISTINCT c.id) as comment_count',
        'COUNT(DISTINCT l.id) as like_count',
        'COUNT(DISTINCT s.id) as share_count',
        '(p.view_count + COUNT(DISTINCT c.id) * 5 + COUNT(DISTINCT l.id) * 2 + COUNT(DISTINCT s.id) * 10) as engagement_score'
    ])
    ->from('posts p')
    ->leftJoin('comments c', 'p.id = c.post_id')
    ->leftJoin('likes l', 'p.id = l.post_id')
    ->leftJoin('shares s', 'p.id = s.post_id')
    ->where('p.status = ?', ['published'])
    ->groupBy('p.id')
    ->orderBy('engagement_score DESC')
    ->limit(20)
    ->execute($db);
```

---

## 🔄 Transactions

### Basic Transactions

```php
// Simple transaction
$db->beginTransaction();
try {
    // Create user
    $userId = SimpleQuery::create()
        ->insert(['name' => 'John Doe', 'email' => 'john@example.com'])
        ->into('users')
        ->execute($db);
    
    // Create profile
    $profileId = SimpleQuery::create()
        ->insert(['user_id' => $userId, 'bio' => 'Software Developer'])
        ->into('profiles')
        ->execute($db);
    
    $db->commit();
    echo "User and profile created successfully";
} catch (Exception $e) {
    $db->rollback();
    echo "Error: " . $e->getMessage();
}
```

### Complex Transaction Example

```php
// E-commerce order processing
$db->beginTransaction();
try {
    // Create order
    $orderId = SimpleQuery::create()
        ->insert([
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'status' => 'pending'
        ])
        ->into('orders')
        ->execute($db);
    
    // Add order items
    foreach ($cartItems as $item) {
        SimpleQuery::create()
            ->insert([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price']
            ])
            ->into('order_items')
            ->execute($db);
        
        // Update inventory
        SimpleQuery::create()
            ->update('products')
            ->set(['stock_quantity' => 'stock_quantity - ' . $item['quantity']])
            ->where('id = ?', [$item['product_id']])
            ->execute($db);
    }
    
    // Update order status
    SimpleQuery::create()
        ->update('orders')
        ->set(['status' => 'confirmed'])
        ->where('id = ?', [$orderId])
        ->execute($db);
    
    $db->commit();
    echo "Order processed successfully";
} catch (Exception $e) {
    $db->rollback();
    echo "Order failed: " . $e->getMessage();
}
```

---

## 🚀 Performance Tips

### Query Optimization

```php
// ✅ Select only needed columns
$users = SimpleQuery::create()
    ->select(['id', 'name', 'email'])  // Not SELECT *
    ->from('users')
    ->execute($db);

// ✅ Use LIMIT for large datasets
$recentPosts = SimpleQuery::create()
    ->select(['*'])
    ->from('posts')
    ->orderBy('created_at DESC')
    ->limit(50)  // Don't fetch everything
    ->execute($db);

// ✅ Use indexes effectively
$user = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('email = ?', [$email])  // Assuming email has unique index
    ->limit(1)
    ->execute($db);
```

### Efficient JOINs

```php
// ✅ Filter before joining when possible
$activePosts = SimpleQuery::create()
    ->select(['p.*', 'u.name'])
    ->from('posts p')
    ->join('users u', 'p.user_id = u.id AND u.is_active = ?', [true])
    ->where('p.status = ?', ['published'])
    ->execute($db);

// ✅ Use EXISTS instead of IN for large subqueries
$usersWithRecentPosts = SimpleQuery::create()
    ->select(['*'])
    ->from('users u')
    ->where('EXISTS (SELECT 1 FROM posts p WHERE p.user_id = u.id AND p.created_at >= ?)', ['2024-01-01'])
    ->execute($db);
```

### Pagination Best Practices

```php
// ✅ Use offset pagination for small offsets
function getPaginatedUsers($page, $perPage) {
    $offset = ($page - 1) * $perPage;
    
    return SimpleQuery::create()
        ->select(['*'])
        ->from('users')
        ->orderBy('id ASC')
        ->limit($perPage)
        ->offset($offset)
        ->execute($db);
}

// ✅ Use cursor pagination for large datasets
function getUsersAfterCursor($lastId, $limit = 20) {
    return SimpleQuery::create()
        ->select(['*'])
        ->from('users')
        ->where('id > ?', [$lastId])
        ->orderBy('id ASC')
        ->limit($limit)
        ->execute($db);
}
```

---

## 🔍 Debugging Queries

### Query Inspection

```php
// Get SQL without executing
$sql = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('is_active = ?', [true])
    ->toSQL();

echo "Generated SQL: " . $sql;

// Debug complex query
$query = SimpleQuery::create()
    ->select(['u.*', 'COUNT(p.id) as post_count'])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->where('u.created_at >= ?', ['2024-01-01'])
    ->groupBy('u.id')
    ->having('post_count > ?', [5]);

echo "SQL: " . $query->toSQL();
echo "Parameters: " . json_encode($query->getParameters());
```

### Performance Monitoring

```php
// Time query execution
$startTime = microtime(true);

$results = SimpleQuery::create()
    ->select(['*'])
    ->from('large_table')
    ->where('indexed_column = ?', [$value])
    ->execute($db);

$duration = microtime(true) - $startTime;

if ($duration > 1.0) {
    error_log("Slow query detected: {$duration}s");
}
```

---

## 🎯 Best Practices

### 1. Always Use Parameter Binding

```php
// ✅ Safe parameter binding
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('email = ?', [$userEmail])
    ->execute($db);

// ❌ Never do this (SQL injection risk)
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where("email = '$userEmail'")
    ->execute($db);
```

### 2. Handle Errors Gracefully

```php
try {
    $result = SimpleQuery::create()
        ->select(['*'])
        ->from('users')
        ->where('id = ?', [$id])
        ->execute($db);
        
    if (empty($result)) {
        throw new Exception('User not found');
    }
    
    return $result[0];
} catch (Exception $e) {
    error_log("Query error: " . $e->getMessage());
    return null;
}
```

### 3. Use Consistent Patterns

```php
// ✅ Consistent repository pattern
class UserRepository
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function findById($id)
    {
        $result = SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('id = ?', [$id])
            ->limit(1)
            ->execute($this->db);
            
        return !empty($result) ? $result[0] : null;
    }
    
    public function findActive()
    {
        return SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('is_active = ?', [true])
            ->orderBy('created_at DESC')
            ->execute($this->db);
    }
}
```

---

## 📚 Next Steps

- **[Learn Schema Builder](schema-builder.md)** - Create and modify tables
- **[Master Data Types](data-types.md)** - Use the right data types
- **[Implement Security](security.md)** - Secure your queries
- **[Setup Migrations](migrations.md)** - Version control your database

---

**Need Help?**
- 📖 [Review Basic Concepts](basic-concepts.md)
- 🔍 [Check FAQ](faq.md)
- 💬 [Join Discord Community](https://discord.gg/simplemdb)
- 🐛 [Report Issues](https://github.com/imrnansaadullah/SimpleMDB/issues) 