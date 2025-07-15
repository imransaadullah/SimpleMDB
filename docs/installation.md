# Installation Guide

Get SimpleMDB installed and configured for any environment - from local development to enterprise production.

## 📋 Requirements

- **PHP 8.0+** (8.1+ recommended for optimal performance)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Composer** for dependency management

## 🚀 Quick Installation

### Standard Installation
```bash
composer require simplemdb/simplemdb
```

### Development Installation (with dev dependencies)
```bash
composer require --dev simplemdb/simplemdb
```

## 🔧 Database Setup

### 1. Create Database
```sql
CREATE DATABASE myapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Create Database User
```sql
-- Create dedicated application user
CREATE USER 'myapp_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON myapp_db.* TO 'myapp_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Test Connection
```php
<?php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;

try {
    $db = DatabaseFactory::create('pdo', 
        'localhost', 
        'myapp_user', 
        'secure_password_here', 
        'myapp_db'
    );
    
    if ($db->isConnected()) {
        echo "✅ SimpleMDB connected successfully!\n";
    }
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
```

## 🌍 Environment-Specific Setup

### Local Development
```php
// config/database.php
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'myapp_dev',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
];
```

### Docker Setup
```yaml
# docker-compose.yml
version: '3.8'
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: myapp
      MYSQL_USER: myapp_user
      MYSQL_PASSWORD: myapp_pass
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  app:
    build: .
    depends_on:
      - mysql
    environment:
      DB_HOST: mysql
      DB_USER: myapp_user
      DB_PASS: myapp_pass
      DB_NAME: myapp

volumes:
  mysql_data:
```

### Production Setup
```php
// config/production.php
return [
    'host' => $_ENV['DB_HOST'],
    'username' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASS'],
    'database' => $_ENV['DB_NAME'],
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ]
];
```

## 🛡️ Security Configuration

### SSL/TLS Setup
```php
$options = [
    PDO::MYSQL_ATTR_SSL_KEY    => '/path/to/client-key.pem',
    PDO::MYSQL_ATTR_SSL_CERT   => '/path/to/client-cert.pem',
    PDO::MYSQL_ATTR_SSL_CA     => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];

$db = DatabaseFactory::create('pdo', $host, $user, $pass, $db, $options);
```

### Connection Pooling
```php
use SimpleMDB\Connection\ConnectionPool;

$pool = new ConnectionPool([
    'min_connections' => 2,
    'max_connections' => 20,
    'host' => 'localhost',
    'username' => 'myapp_user',
    'password' => 'secure_password',
    'database' => 'myapp_db'
]);

$db = $pool->getConnection();
```

## 🧪 Verify Installation

### Run Basic Tests
```php
<?php
// test_installation.php
require_once 'vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\SchemaBuilder;
use SimpleMDB\SimpleQuery;

echo "Testing SimpleMDB Installation...\n\n";

// Test 1: Database Connection
try {
    $db = DatabaseFactory::create('pdo', 'localhost', 'root', '', 'test_db');
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Schema Builder
try {
    $schema = new SchemaBuilder($db);
    echo "✅ Schema Builder loaded\n";
} catch (Exception $e) {
    echo "❌ Schema Builder failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Query Builder
try {
    $query = SimpleQuery::create();
    echo "✅ Query Builder loaded\n";
} catch (Exception $e) {
    echo "❌ Query Builder failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 SimpleMDB installation verified successfully!\n";
```

### Run Installation Test
```bash
php test_installation.php
```

## 🚨 Troubleshooting

### Common Issues

**Connection Refused**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Check MySQL is listening on correct port
netstat -ln | grep 3306
```

**Access Denied**
```sql
-- Reset MySQL root password
ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
```

**Character Set Issues**
```sql
-- Set proper character set for existing database
ALTER DATABASE myapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**PDO Extension Missing**
```bash
# Ubuntu/Debian
sudo apt-get install php-pdo php-mysql

# CentOS/RHEL
sudo yum install php-pdo php-mysql

# Windows (uncomment in php.ini)
extension=pdo_mysql
```

### Performance Optimization

**MySQL Configuration (my.cnf)**
```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 128M
max_connections = 200
```

**PHP Configuration (php.ini)**
```ini
memory_limit = 512M
max_execution_time = 300
mysql.default_socket = /var/run/mysqld/mysqld.sock
```

## 🔄 Upgrading

### From Previous Versions
```bash
# Backup your database first!
mysqldump -u root -p myapp_db > backup_$(date +%Y%m%d).sql

# Update SimpleMDB
composer update simplemdb/simplemdb

# Run migration if needed
php vendor/bin/simplemdb migrate
```

---

## 📞 Getting Help

**Installation Issues:**
- Check [GitHub Issues](https://github.com/imrnansaadullah/SimpleMDB/issues)
- Review [FAQ](docs/faq.md)
- Join our [Discord Community](https://discord.gg/simplemdb)

**Next Steps:**
- 👉 [Try the Quick Start Guide](../QUICK_START.md)
- 👉 [Learn Basic Concepts](basic-concepts.md)
- 👉 [Explore Schema Builder](schema-builder.md) 