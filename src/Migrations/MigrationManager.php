<?php

namespace SimpleMDB\Migrations;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SchemaBuilder;
use SimpleMDB\Exceptions\SchemaException;
use SimpleMDB\Traits\LoggerAwareTrait;
use DirectoryIterator;

/**
 * Migration manager for handling database schema migrations
 */
class MigrationManager
{
    use LoggerAwareTrait;

    private DatabaseInterface $db;
    private string $migrationsPath;
    private string $migrationTable = 'migrations';
    private array $loadedMigrations = [];

    public function __construct(DatabaseInterface $db, string $migrationsPath = 'migrations')
    {
        $this->db = $db;
        $this->migrationsPath = rtrim($migrationsPath, '/');
        $this->ensureMigrationTable();
    }

    /**
     * Run pending migrations
     */
    public function migrate(?int $steps = null): array
    {
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            $this->log('info', 'No pending migrations to run');
            return [];
        }

        if ($steps !== null) {
            $pendingMigrations = array_slice($pendingMigrations, 0, $steps);
        }

        $executed = [];
        
        foreach ($pendingMigrations as $migration) {
            $this->log('info', "Running migration: {$migration->getName()}");
            
            try {
                $startTime = microtime(true);
                
                // Execute migration without transaction wrapper since DDL statements
                // (CREATE TABLE, ALTER TABLE, etc.) cause implicit commits in MySQL
                $migration->up();
                
                $executionTime = microtime(true) - $startTime;
                
                // Record migration execution in a separate transaction
                $this->db->transaction(function() use ($migration, $executionTime) {
                    $this->recordMigration($migration, $executionTime);
                });
                
                $executed[] = $migration->getName();
                $this->log('info', "Successfully executed migration: {$migration->getName()}");
                
            } catch (\Exception $e) {
                $this->log('error', "Migration failed: {$migration->getName()}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw SchemaException::migrationFailed(
                    $migration->getName(),
                    'up',
                    $e->getMessage()
                );
            }
        }

        return $executed;
    }

    /**
     * Rollback migrations
     */
    public function rollback(int $steps = 1): array
    {
        $executedMigrations = $this->getExecutedMigrations();
        
        if (empty($executedMigrations)) {
            $this->log('info', 'No migrations to rollback');
            return [];
        }

        // Take only the requested number of steps
        $migrationsToRollback = array_slice($executedMigrations, 0, $steps);
        $rolledBack = [];

        foreach ($migrationsToRollback as $migrationRecord) {
            $migration = $this->loadMigration($migrationRecord['migration']);
            
            if (!$migration) {
                $this->log('warning', "Migration class not found: {$migrationRecord['migration']}");
                continue;
            }

            if (!$migration->isReversible()) {
                throw SchemaException::migrationFailed(
                    $migration->getName(),
                    'down',
                    'Migration is not reversible'
                );
            }

            $this->log('info', "Rolling back migration: {$migration->getName()}");
            
            try {
                // Execute rollback without transaction wrapper since DDL statements
                // (DROP TABLE, ALTER TABLE, etc.) cause implicit commits in MySQL  
                $migration->down();
                
                // Remove migration record in a separate transaction
                $this->db->transaction(function() use ($migration) {
                    $this->removeMigrationRecord($migration);
                });
                
                $rolledBack[] = $migration->getName();
                $this->log('info', "Successfully rolled back migration: {$migration->getName()}");
                
            } catch (\Exception $e) {
                $this->log('error', "Rollback failed: {$migration->getName()}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw SchemaException::migrationFailed(
                    $migration->getName(),
                    'down',
                    $e->getMessage()
                );
            }
        }

        return $rolledBack;
    }

    /**
     * Reset all migrations (rollback all)
     */
    public function reset(): array
    {
        $executedMigrations = $this->getExecutedMigrations();
        return $this->rollback(count($executedMigrations));
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $allMigrations = $this->discoverMigrations();
        $executedMigrations = $this->getExecutedMigrations();
        $executedMap = [];
        
        foreach ($executedMigrations as $record) {
            $executedMap[$record['migration']] = $record;
        }

        $status = [];
        
        foreach ($allMigrations as $migration) {
            $name = $migration->getName();
            $version = $migration->getVersion();
            
            if (isset($executedMap[$name])) {
                $record = $executedMap[$name];
                $status[] = [
                    'migration' => $name,
                    'version' => $version,
                    'description' => $migration->getDescription(),
                    'status' => 'executed',
                    'executed_at' => $record['executed_at'],
                    'execution_time' => $record['execution_time']
                ];
            } else {
                $status[] = [
                    'migration' => $name,
                    'version' => $version,
                    'description' => $migration->getDescription(),
                    'status' => 'pending',
                    'executed_at' => null,
                    'execution_time' => null
                ];
            }
        }

        // Sort by version
        usort($status, function($a, $b) {
            return strcmp($a['version'], $b['version']);
        });

        return $status;
    }

    /**
     * Create a new migration
     */
    public function create(string $name): string
    {
        $timestamp = date('Ymd_His');
        $className = "Migration_{$timestamp}_{$name}";
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . '/' . $filename;

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $template = $this->getMigrationTemplate($className, $name);
        
        if (file_put_contents($filepath, $template) === false) {
            throw new \RuntimeException("Failed to create migration file: $filepath");
        }

        $this->log('info', "Created migration: $filename");
        
        return $filepath;
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        $allMigrations = $this->discoverMigrations();
        $executedMigrations = $this->getExecutedMigrations();
        $executedNames = array_column($executedMigrations, 'migration');

        $pending = [];
        foreach ($allMigrations as $migration) {
            if (!in_array($migration->getName(), $executedNames)) {
                $pending[] = $migration;
            }
        }

        // Sort by version
        usort($pending, function($a, $b) {
            return strcmp($a->getVersion(), $b->getVersion());
        });

        return $pending;
    }

    /**
     * Get executed migrations
     */
    private function getExecutedMigrations(): array
    {
        $escapedTable = "`{$this->migrationTable}`";
        $sql = "SELECT * FROM {$escapedTable} ORDER BY executed_at DESC";
        return $this->db->query($sql)->fetchAll('assoc');
    }

    /**
     * Discover migration files
     */
    private function discoverMigrations(): array
    {
        if (!empty($this->loadedMigrations)) {
            return $this->loadedMigrations;
        }

        $migrations = [];

        if (!is_dir($this->migrationsPath)) {
            return $migrations;
        }

        $iterator = new DirectoryIterator($this->migrationsPath);
        
        foreach ($iterator as $file) {
            if ($file->isDot() || $file->getExtension() !== 'php') {
                continue;
            }

            $migration = $this->loadMigrationFromFile($file->getPathname());
            if ($migration) {
                $migrations[] = $migration;
            }
        }

        $this->loadedMigrations = $migrations;
        return $migrations;
    }

    /**
     * Load migration from file
     */
    private function loadMigrationFromFile(string $filepath): ?Migration
    {
        require_once $filepath;
        
        $basename = basename($filepath, '.php');
        
        // Extract class name from filename
        preg_match('/(\d{8}_\d{6})_(.+)/', $basename, $matches);
        
        if (count($matches) < 3) {
            $this->log('warning', "Invalid migration filename format: $basename");
            return null;
        }

        $className = "Migration_{$matches[1]}_{$matches[2]}";
        
        if (!class_exists($className)) {
            $this->log('warning', "Migration class not found: $className");
            return null;
        }

        return new $className($this->db);
    }

    /**
     * Load migration by name
     */
    private function loadMigration(string $name): ?Migration
    {
        $allMigrations = $this->discoverMigrations();
        
        foreach ($allMigrations as $migration) {
            if ($migration->getName() === $name) {
                return $migration;
            }
        }

        return null;
    }

    /**
     * Record migration execution
     */
    private function recordMigration(Migration $migration, float $executionTime): void
    {
        $data = [
            'migration' => $migration->getName(),
            'executed_at' => date('Y-m-d H:i:s'),
            'execution_time' => round($executionTime, 4)
        ];

        $escapedTable = "`{$this->migrationTable}`";
        $this->db->write_data($escapedTable, $data);
    }

    /**
     * Remove migration record
     */
    private function removeMigrationRecord(Migration $migration): void
    {
        $escapedTable = "`{$this->migrationTable}`";
        $this->db->delete($escapedTable, 'migration = ?', [$migration->getName()]);
    }

    /**
     * Ensure migration table exists
     */
    private function ensureMigrationTable(): void
    {
        $schema = new SchemaBuilder($this->db);
        
        if (!$schema->hasTable($this->migrationTable)) {
            $schema->string('migration', 255)
                   ->datetime('executed_at')
                   ->decimal('execution_time', 8, 4)->nullable()
                   ->primaryKey('migration')
                   ->createTable($this->migrationTable);

            $this->log('info', "Created migrations table: {$this->migrationTable}");
        }
    }

    /**
     * Get migration template
     */
    private function getMigrationTemplate(string $className, string $name): string
    {
        // Detect migration type from name and provide appropriate examples
        $examples = $this->generateMigrationExamples($name);
        
        return <<<PHP
<?php

use SimpleMDB\Migrations\Migration;

class {$className} extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
{$examples['up']}
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
{$examples['down']}
    }
}
PHP;
    }

    /**
     * Generate appropriate migration examples based on migration name
     */
    private function generateMigrationExamples(string $name): array
    {
        $name = strtolower($name);
        
        // Detect table creation patterns
        if (preg_match('/create[_\s](.+)[_\s]table/', $name, $matches)) {
            $tableName = $matches[1];
            return $this->getCreateTableExamples($tableName);
        }
        
        // Detect column addition patterns
        if (preg_match('/add[_\s](.+)[_\s]to[_\s](.+)/', $name, $matches)) {
            $columnName = $matches[1];
            $tableName = $matches[2];
            return $this->getAddColumnExamples($tableName, $columnName);
        }
        
        // Detect index addition patterns
        if (preg_match('/add[_\s](.+)[_\s]index[_\s]to[_\s](.+)/', $name, $matches)) {
            $indexName = $matches[1];
            $tableName = $matches[2];
            return $this->getAddIndexExamples($tableName, $indexName);
        }
        
        // Detect table modification patterns
        if (preg_match('/modify[_\s](.+)[_\s]table/', $name, $matches) || 
            preg_match('/alter[_\s](.+)[_\s]table/', $name, $matches)) {
            $tableName = $matches[1];
            return $this->getAlterTableExamples($tableName);
        }
        
        // Default examples with all new features
        return $this->getDefaultExamples();
    }

    /**
     * Generate create table examples
     */
    private function getCreateTableExamples(string $tableName): array
    {
        $tableName = $this->sanitizeTableName($tableName);
        
        return [
            'up' => "        // Create {$tableName} table with modern data types and modifiers
        \$this->createTable('{$tableName}', function(\$table) {
            \$table->increments('id');
            \$table->string('name')->comment('Full name');
            \$table->string('email')->unique();
            \$table->timestamp('email_verified_at')->nullable();
            \$table->string('password');
            \$table->date('birth_date')->nullable();
            \$table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            \$table->json('preferences')->nullable();
            \$table->ipAddress('last_login_ip')->nullable();
            \$table->rememberToken();
            \$table->timestamps();
            \$table->softDeletes();
        });",
            
            'down' => "        // Drop {$tableName} table
        \$this->dropTable('{$tableName}');"
        ];
    }

    /**
     * Generate add column examples
     */
    private function getAddColumnExamples(string $tableName, string $columnName): array
    {
        $tableName = $this->sanitizeTableName($tableName);
        $columnName = $this->sanitizeColumnName($columnName);
        
        // Try to detect column type from name
        $columnType = $this->detectColumnType($columnName);
        
        return [
            'up' => "        // Add {$columnName} column to {$tableName} table
        \$this->table('{$tableName}')->addColumn('{$columnName}', {$columnType});",
            
            'down' => "        // Remove {$columnName} column from {$tableName} table
        \$this->table('{$tableName}')->dropColumn('{$columnName}');"
        ];
    }

    /**
     * Generate add index examples
     */
    private function getAddIndexExamples(string $tableName, string $indexName): array
    {
        $tableName = $this->sanitizeTableName($tableName);
        $indexName = $this->sanitizeColumnName($indexName);
        
        return [
            'up' => "        // Add index to {$tableName} table
        \$this->table('{$tableName}')->addIndex(['{$indexName}'], '{$indexName}_index');",
            
            'down' => "        // Remove index from {$tableName} table
        \$this->table('{$tableName}')->dropIndex('{$indexName}_index');"
        ];
    }

    /**
     * Generate alter table examples
     */
    private function getAlterTableExamples(string $tableName): array
    {
        $tableName = $this->sanitizeTableName($tableName);
        
        return [
            'up' => "        // Modify {$tableName} table structure
        \$table = \$this->table('{$tableName}');
        
        // Add new columns
        \$table->addColumn('new_field', [
            'type' => 'VARCHAR',
            'length' => 255,
            'nullable' => true,
            'comment' => 'New field description'
        ]);
        
        // Add index
        \$table->addIndex(['new_field'], 'new_field_index');",
            
            'down' => "        // Reverse {$tableName} table modifications
        \$table = \$this->table('{$tableName}');
        
        // Remove index
        \$table->dropIndex('new_field_index');
        
        // Remove column
        \$table->dropColumn('new_field');"
        ];
    }

    /**
     * Generate default comprehensive examples
     */
    private function getDefaultExamples(): array
    {
        return [
            'up' => "        // Comprehensive example showcasing all new data types and features
        \$this->createTable('example_table', function(\$table) {
            // Primary key with auto-increment
            \$table->increments('id');
            
            // String and text types with modifiers
            \$table->string('title', 200)->comment('Article title');
            \$table->char('code', 10)->nullable();
            \$table->text('content')->nullable();
            
            // Numeric types
            \$table->decimal('price', 10, 2)->unsigned()->nullable();
            \$table->float('rating', 3, 2)->default(0.0);
            \$table->bigInteger('views')->unsigned()->default(0);
            \$table->tinyInteger('priority')->default(1);
            
            // Date and time types
            \$table->date('publish_date')->nullable();
            \$table->time('publish_time')->nullable();
            \$table->year('copyright_year')->nullable();
            \$table->timestamp('featured_at')->nullable();
            
            // Special types
            \$table->boolean('is_featured')->default(false);
            \$table->json('metadata')->nullable();
            \$table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            \$table->uuid('external_id')->nullable();
            \$table->ipAddress('author_ip')->nullable();
            \$table->macAddress('device_mac')->nullable();
            
            // Indexes and constraints
            \$table->index(['status', 'publish_date'], 'status_date_index');
            \$table->unique(['title', 'publish_date'], 'title_date_unique');
            
            // Standard timestamps and soft deletes
            \$table->timestamps();
            \$table->softDeletes();
        });
        
        // Example of polymorphic relationship table
        \$this->createTable('comments', function(\$table) {
            \$table->increments('id');
            \$table->text('content');
            \$table->morphs('commentable');  // Adds commentable_id and commentable_type
            \$table->timestamps();
        });",
            
            'down' => "        // Drop created tables
        \$this->dropTable('comments');
        \$this->dropTable('example_table');"
        ];
    }

    /**
     * Detect likely column type from column name
     */
    private function detectColumnType(string $columnName): string
    {
        $columnName = strtolower($columnName);
        
        // Email detection
        if (strpos($columnName, 'email') !== false) {
            return "['type' => 'VARCHAR', 'length' => 255, 'nullable' => true]";
        }
        
        // URL detection
        if (strpos($columnName, 'url') !== false || strpos($columnName, 'link') !== false) {
            return "['type' => 'TEXT', 'nullable' => true]";
        }
        
        // Date detection
        if (strpos($columnName, 'date') !== false || strpos($columnName, '_at') !== false) {
            return "['type' => 'TIMESTAMP', 'nullable' => true]";
        }
        
        // Boolean detection
        if (strpos($columnName, 'is_') === 0 || strpos($columnName, 'has_') === 0 || 
            strpos($columnName, 'can_') === 0 || strpos($columnName, 'active') !== false) {
            return "['type' => 'TINYINT', 'length' => 1, 'default' => 0]";
        }
        
        // Integer detection
        if (strpos($columnName, 'count') !== false || strpos($columnName, 'number') !== false ||
            strpos($columnName, 'amount') !== false || strpos($columnName, '_id') !== false) {
            return "['type' => 'INT', 'unsigned' => true, 'nullable' => true]";
        }
        
        // JSON detection
        if (strpos($columnName, 'data') !== false || strpos($columnName, 'config') !== false ||
            strpos($columnName, 'settings') !== false || strpos($columnName, 'meta') !== false) {
            return "['type' => 'JSON', 'nullable' => true]";
        }
        
        // Default to VARCHAR
        return "['type' => 'VARCHAR', 'length' => 255, 'nullable' => true]";
    }

    /**
     * Sanitize table name
     */
    private function sanitizeTableName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($name));
    }

    /**
     * Sanitize column name
     */
    private function sanitizeColumnName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($name));
    }

    /**
     * Set migration table name
     */
    public function setMigrationTable(string $tableName): self
    {
        $this->migrationTable = $tableName;
        return $this;
    }

    /**
     * Set migrations path
     */
    public function setMigrationsPath(string $path): self
    {
        $this->migrationsPath = rtrim($path, '/');
        $this->loadedMigrations = []; // Reset cache
        return $this;
    }
} 