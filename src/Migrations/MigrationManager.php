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
                $this->db->transaction(function() use ($migration) {
                    $startTime = microtime(true);
                    
                    $migration->up();
                    
                    $executionTime = microtime(true) - $startTime;
                    
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
                $this->db->transaction(function() use ($migration) {
                    $migration->down();
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
        // TODO: Implement migration logic
        // Example:
        // \$this->createTable('example_table', function(\$table) {
        //     \$table->integer('id', unsigned: true, autoIncrement: true)->primaryKey('id');
        //     \$table->string('name', 100);
        //     \$table->timestamps();
        // });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // TODO: Implement rollback logic
        // Example:
        // \$this->dropTable('example_table');
    }
}
PHP;
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