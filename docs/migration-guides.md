# Migration Guides

Complete guides to migrate from popular PHP frameworks and database tools to SimpleMDB. Transition smoothly while leveraging SimpleMDB's enterprise features.

## 📋 Framework Migrations

- [From Laravel Eloquent](#from-laravel-eloquent)
- [From Doctrine ORM](#from-doctrine-orm)
- [From CodeIgniter](#from-codeigniter)
- [From CakePHP](#from-cakephp)
- [From Raw PDO](#from-raw-pdo)
- [From WordPress](#from-wordpress)

---

## 🔄 From Laravel Eloquent

Laravel developers will find SimpleMDB familiar yet more flexible. Here's how to migrate common patterns.

### Database Configuration

**Laravel (config/database.php):**
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
]
```

**SimpleMDB:**
```php
use SimpleMDB\DatabaseFactory;

$db = DatabaseFactory::create('pdo', 
    $_ENV['DB_HOST'], 
    $_ENV['DB_USERNAME'], 
    $_ENV['DB_PASSWORD'], 
    $_ENV['DB_DATABASE']
);
```

### Schema Builder Migration

**Laravel Migration:**
```php
// Laravel
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

**SimpleMDB Schema:**
```php
// SimpleMDB
use SimpleMDB\SchemaBuilder;

$schema = new SchemaBuilder($db);
$schema->increments('id')
       ->string('name', 255)
       ->string('email', 150)->unique()
       ->timestamp('email_verified_at')->nullable()
       ->string('password', 255)
       ->rememberToken()
       ->timestamps()
       ->createTable('users');
```

### Query Builder Comparison

| Laravel | SimpleMDB |
|---------|-----------|
| `DB::table('users')->get()` | `SimpleQuery::create()->select(['*'])->from('users')->execute($db)` |
| `DB::table('users')->where('active', 1)->get()` | `SimpleQuery::create()->select(['*'])->from('users')->where('active = ?', [1])->execute($db)` |
| `DB::table('users')->insert($data)` | `SimpleQuery::create()->insert($data)->into('users')->execute($db)` |
| `DB::table('users')->where('id', 1)->update($data)` | `SimpleQuery::create()->update('users')->set($data)->where('id = ?', [1])->execute($db)` |

### Model to Query Conversion

**Laravel Model:**
```php
// Laravel User Model
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Usage
$users = User::where('active', true)->get();
$user = User::create($userData);
```

**SimpleMDB Equivalent:**
```php
// SimpleMDB User Class
class User
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function findActive()
    {
        return SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('active = ?', [true])
            ->execute($this->db);
    }
    
    public function create(array $userData)
    {
        return SimpleQuery::create()
            ->insert($userData)
            ->into('users')
            ->execute($this->db);
    }
    
    public function posts($userId)
    {
        return SimpleQuery::create()
            ->select(['*'])
            ->from('posts')
            ->where('user_id = ?', [$userId])
            ->execute($this->db);
    }
}
```

### Relationship Patterns

**Laravel Relationships:**
```php
// Laravel
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}

// Usage
$user = User::with('posts')->find(1);
```

**SimpleMDB Relationships:**
```php
// SimpleMDB
class UserService
{
    public function getUserWithPosts($userId)
    {
        return SimpleQuery::create()
            ->select([
                'users.*',
                'posts.id as post_id',
                'posts.title as post_title',
                'posts.content as post_content'
            ])
            ->from('users')
            ->leftJoin('posts', 'users.id = posts.user_id')
            ->where('users.id = ?', [$userId])
            ->execute($this->db);
    }
    
    public function getUserWithProfile($userId)
    {
        return SimpleQuery::create()
            ->select(['users.*', 'profiles.*'])
            ->from('users')
            ->join('profiles', 'users.id = profiles.user_id')
            ->where('users.id = ?', [$userId])
            ->execute($this->db);
    }
}
```

### Migration Timeline

**Week 1-2: Setup & Basic Queries**
1. Install SimpleMDB: `composer require simplemdb/simplemdb`
2. Convert basic queries (SELECT, INSERT, UPDATE, DELETE)
3. Migrate simple table schemas

**Week 3-4: Advanced Features**
1. Convert relationships to JOINs
2. Migrate complex queries and subqueries
3. Implement validation patterns

**Week 5-6: Optimization**
1. Add indexes and constraints
2. Implement caching strategies
3. Performance optimization

---

## 🏛️ From Doctrine ORM

Doctrine users can leverage SimpleMDB's flexibility while maintaining structured approaches.

### Entity to Schema Conversion

**Doctrine Entity:**
```php
// Doctrine
/**
 * @Entity
 * @Table(name="users")
 */
class User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;
    
    /**
     * @Column(type="string", length=100)
     */
    private $name;
    
    /**
     * @Column(type="string", length=150, unique=true)
     */
    private $email;
    
    /**
     * @OneToMany(targetEntity="Post", mappedBy="user")
     */
    private $posts;
}
```

**SimpleMDB Schema:**
```php
// SimpleMDB
$schema->increments('id')
       ->string('name', 100)
       ->string('email', 150)->unique()
       ->timestamps()
       ->createTable('users');

$schema->increments('id')
       ->string('title', 255)
       ->text('content')
       ->integer('user_id')->unsigned()
       ->timestamps()
       ->foreign('user_id')->references('id')->on('users')
       ->createTable('posts');
```

### Repository Pattern Migration

**Doctrine Repository:**
```php
// Doctrine
class UserRepository extends EntityRepository
{
    public function findActiveUsers()
    {
        return $this->createQueryBuilder('u')
            ->where('u.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
```

**SimpleMDB Repository:**
```php
// SimpleMDB
class UserRepository
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function findActiveUsers()
    {
        return SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('active = ?', [true])
            ->execute($this->db);
    }
    
    public function findByEmail($email)
    {
        $result = SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('email = ?', [$email])
            ->limit(1)
            ->execute($this->db);
            
        return !empty($result) ? $result[0] : null;
    }
}
```

### DQL to SimpleMDB Query

**Doctrine DQL:**
```php
$query = $entityManager->createQuery(
    'SELECT u, p FROM User u JOIN u.posts p WHERE u.active = :active'
);
$query->setParameter('active', true);
$result = $query->getResult();
```

**SimpleMDB:**
```php
$result = SimpleQuery::create()
    ->select(['u.*', 'p.*'])
    ->from('users u')
    ->join('posts p', 'u.id = p.user_id')
    ->where('u.active = ?', [true])
    ->execute($db);
```

---

## 🔥 From CodeIgniter

CodeIgniter developers will appreciate SimpleMDB's straightforward approach.

### Database Configuration

**CodeIgniter:**
```php
// application/config/database.php
$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'myapp',
    'dbdriver' => 'mysqli',
);
```

**SimpleMDB:**
```php
use SimpleMDB\DatabaseFactory;

$db = DatabaseFactory::create('mysqli', 'localhost', 'root', '', 'myapp');
// or for PDO
$db = DatabaseFactory::create('pdo', 'localhost', 'root', '', 'myapp');
```

### Active Record to Query Builder

**CodeIgniter Active Record:**
```php
// CodeIgniter
$this->db->select('*');
$this->db->from('users');
$this->db->where('active', 1);
$this->db->order_by('name', 'ASC');
$query = $this->db->get();
$result = $query->result_array();
```

**SimpleMDB:**
```php
// SimpleMDB
$result = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->where('active = ?', [1])
    ->orderBy('name ASC')
    ->execute($db);
```

### Model Migration

**CodeIgniter Model:**
```php
// CodeIgniter
class User_model extends CI_Model
{
    public function get_users($active = true)
    {
        $this->db->where('active', $active);
        return $this->db->get('users')->result_array();
    }
    
    public function insert_user($data)
    {
        return $this->db->insert('users', $data);
    }
}
```

**SimpleMDB Model:**
```php
// SimpleMDB
class UserModel
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function getUsers($active = true)
    {
        return SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('active = ?', [$active])
            ->execute($this->db);
    }
    
    public function insertUser($data)
    {
        return SimpleQuery::create()
            ->insert($data)
            ->into('users')
            ->execute($this->db);
    }
}
```

---

## 🎂 From CakePHP

CakePHP conventions translate well to SimpleMDB patterns.

### Table Class to Repository

**CakePHP Table:**
```php
// CakePHP
class UsersTable extends Table
{
    public function findActive(Query $query, array $options)
    {
        return $query->where(['active' => true]);
    }
}

// Usage
$usersTable = TableRegistry::getTableLocator()->get('Users');
$activeUsers = $usersTable->find('active')->toArray();
```

**SimpleMDB Repository:**
```php
// SimpleMDB
class UsersRepository
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function findActive()
    {
        return SimpleQuery::create()
            ->select(['*'])
            ->from('users')
            ->where('active = ?', [true])
            ->execute($this->db);
    }
}
```

### ORM to Query Builder

**CakePHP ORM:**
```php
// CakePHP
$users = $this->Users->find()
    ->contain(['Posts'])
    ->where(['Users.active' => true])
    ->order(['Users.name' => 'ASC']);
```

**SimpleMDB:**
```php
// SimpleMDB
$users = SimpleQuery::create()
    ->select(['u.*', 'p.title as post_title'])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->where('u.active = ?', [true])
    ->orderBy('u.name ASC')
    ->execute($db);
```

---

## 📄 From Raw PDO

Raw PDO users can enhance their code with SimpleMDB's conveniences.

### PDO to SimpleMDB

**Raw PDO:**
```php
// Raw PDO
try {
    $pdo = new PDO("mysql:host=localhost;dbname=myapp", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE active = ? ORDER BY name ASC");
    $stmt->execute([1]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

**SimpleMDB:**
```php
// SimpleMDB
use SimpleMDB\DatabaseFactory;

try {
    $db = DatabaseFactory::create('pdo', 'localhost', $username, $password, 'myapp');
    
    $users = SimpleQuery::create()
        ->select(['*'])
        ->from('users')
        ->where('active = ?', [1])
        ->orderBy('name ASC')
        ->execute($db);
} catch (Exception $e) {
    die("Query failed: " . $e->getMessage());
}
```

### Complex Query Migration

**Raw PDO Complex Query:**
```php
// Raw PDO
$sql = "
    SELECT u.*, COUNT(p.id) as post_count
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    WHERE u.active = ? AND u.created_at >= ?
    GROUP BY u.id
    HAVING post_count > ?
    ORDER BY post_count DESC
    LIMIT ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([1, '2024-01-01', 5, 10]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**SimpleMDB:**
```php
// SimpleMDB
$results = SimpleQuery::create()
    ->select(['u.*', 'COUNT(p.id) as post_count'])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->where('u.active = ? AND u.created_at >= ?', [1, '2024-01-01'])
    ->groupBy('u.id')
    ->having('post_count > ?', [5])
    ->orderBy('post_count DESC')
    ->limit(10)
    ->execute($db);
```

---

## 📝 From WordPress

WordPress developers can build more robust applications with SimpleMDB.

### WordPress Database to SimpleMDB

**WordPress:**
```php
// WordPress
global $wpdb;

$users = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->users} WHERE user_status = %d",
        1
    ),
    ARRAY_A
);
```

**SimpleMDB:**
```php
// SimpleMDB
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('wp_users')
    ->where('user_status = ?', [1])
    ->execute($db);
```

### Custom Table Creation

**WordPress:**
```php
// WordPress
function create_custom_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'custom_data';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

**SimpleMDB:**
```php
// SimpleMDB
use SimpleMDB\SchemaBuilder;

$schema = new SchemaBuilder($db);
$schema->increments('id')
       ->text('name')
       ->string('email', 100)
       ->timestamp('created_at')->default('CURRENT_TIMESTAMP')
       ->createTable('wp_custom_data');
```

---

## 📊 Migration Checklist

### Phase 1: Assessment (Week 1)
- [ ] Audit current database queries
- [ ] Identify complex relationships
- [ ] List custom functions and procedures
- [ ] Document performance requirements
- [ ] Plan migration timeline

### Phase 2: Setup (Week 1-2)
- [ ] Install SimpleMDB
- [ ] Set up test environment
- [ ] Configure database connections
- [ ] Create basic schema migrations
- [ ] Write unit tests for critical queries

### Phase 3: Core Migration (Week 2-4)
- [ ] Convert schema definitions
- [ ] Migrate basic CRUD operations
- [ ] Convert complex queries
- [ ] Implement relationship patterns
- [ ] Add proper indexing

### Phase 4: Advanced Features (Week 4-6)
- [ ] Implement caching strategies
- [ ] Add query optimization
- [ ] Set up backup systems
- [ ] Implement security measures
- [ ] Performance testing

### Phase 5: Deployment (Week 6-8)
- [ ] Staging environment testing
- [ ] Load testing
- [ ] Security audit
- [ ] Documentation updates
- [ ] Production deployment
- [ ] Monitoring setup

---

## 🎯 Migration Best Practices

### 1. Start Small
```php
// ✅ Begin with simple queries
$users = SimpleQuery::create()
    ->select(['*'])
    ->from('users')
    ->execute($db);

// Then gradually add complexity
$users = SimpleQuery::create()
    ->select(['u.*', 'p.title'])
    ->from('users u')
    ->leftJoin('posts p', 'u.id = p.user_id')
    ->where('u.active = ?', [true])
    ->execute($db);
```

### 2. Maintain Backward Compatibility
```php
// ✅ Create wrapper classes during migration
class LegacyUserModel
{
    private $simpleMdbRepo;
    
    public function __construct($db)
    {
        $this->simpleMdbRepo = new UserRepository($db);
    }
    
    // Keep old method names
    public function get_active_users()
    {
        return $this->simpleMdbRepo->findActive();
    }
}
```

### 3. Test Everything
```php
// ✅ Write migration tests
public function testLegacyQueryProducesSameResults()
{
    $legacyResult = $this->getLegacyUserData();
    $newResult = $this->getSimpleMdbUserData();
    
    $this->assertEquals($legacyResult, $newResult);
}
```

### 4. Performance Monitoring
```php
// ✅ Monitor query performance during migration
$startTime = microtime(true);
$result = SimpleQuery::create()->/* ... */->execute($db);
$duration = microtime(true) - $startTime;

if ($duration > 1.0) {
    error_log("Slow query detected: {$duration}s");
}
```

---

## 📞 Getting Help

**Migration Support:**
- 📚 [Review Basic Concepts](basic-concepts.md)
- 🔍 [Check Migration FAQs](faq.md)
- 💬 [Join Discord Community](https://discord.gg/simplemdb)
- 📧 [Email Migration Support](mailto:support@simplemdb.com)

**Next Steps:**
- 👉 [Learn Schema Builder](schema-builder.md)
- 👉 [Master Query Builder](query-builder.md)
- 👉 [Implement Security](security.md) 