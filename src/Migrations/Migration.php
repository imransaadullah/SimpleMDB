<?php

namespace SimpleMDB\Migrations;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\SchemaBuilder;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Base migration class
 */
abstract class Migration
{
    protected DatabaseInterface $db;
    protected SchemaBuilder $schema;
    protected string $connection = 'default';
    
    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
        $this->schema = new SchemaBuilder($db);
    }

    /**
     * Run the migration
     */
    abstract public function up(): void;

    /**
     * Reverse the migration
     */
    abstract public function down(): void;

    /**
     * Get migration name/identifier
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
     * Get migration version (timestamp)
     */
    public function getVersion(): string
    {
        // Extract timestamp from class name (e.g., Migration_20231201_120000_CreateUsersTable)
        preg_match('/(\d{8}_\d{6})/', static::class, $matches);
        return $matches[1] ?? date('Ymd_His');
    }

    /**
     * Get migration description
     */
    public function getDescription(): string
    {
        // Extract description from class name
        $className = static::class;
        $parts = explode('_', $className);
        
        if (count($parts) >= 4) {
            return implode('_', array_slice($parts, 3));
        }
        
        return $className;
    }

    /**
     * Check if migration can be reversed
     */
    public function isReversible(): bool
    {
        return true;
    }

    /**
     * Get database connection name
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Set database connection
     */
    protected function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Execute raw SQL
     */
    protected function execute(string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (\Exception $e) {
            throw SchemaException::migrationFailed(
                $this->getName(),
                'execute',
                $e->getMessage()
            );
        }
    }

    /**
     * Create a new table (strict mode - will fail if table exists)
     */
    protected function createTable(string $name, callable $callback): void
    {
        // Create a fresh SchemaBuilder instance for each table to avoid state conflicts
        $tableBuilder = new SchemaBuilder($this->db);
        $callback($tableBuilder);
        $tableBuilder->createTable($name);
    }

    /**
     * Create a new table only if it doesn't exist (safe mode)
     */
    protected function createTableIfNotExists(string $name, callable $callback): void
    {
        // Create a fresh SchemaBuilder instance for each table to avoid state conflicts
        $tableBuilder = new SchemaBuilder($this->db);
        $tableBuilder->ifNotExists(); // Set the flag before callback
        $callback($tableBuilder);
        $tableBuilder->createTable($name);
    }

    /**
     * Create a new table safely (alias for createTableIfNotExists)
     */
    protected function safelyCreateTable(string $name, callable $callback): void
    {
        $this->createTableIfNotExists($name, $callback);
    }

    /**
     * Get a fluent table creator for more expressive table creation
     */
    protected function newTable(string $name): \SimpleMDB\TableCreator
    {
        return new \SimpleMDB\TableCreator($this->db, $name);
    }

    /**
     * Drop a table
     */
    protected function dropTable(string $name): void
    {
        $this->schema->dropTable($name);
    }

    /**
     * Check if table exists
     */
    protected function hasTable(string $name): bool
    {
        return $this->schema->hasTable($name);
    }

    /**
     * Check if column exists
     */
    protected function hasColumn(string $table, string $column): bool
    {
        return $this->schema->hasColumn($table, $column);
    }

    /**
     * Alter table
     */
    protected function table(string $name): \SimpleMDB\TableAlter
    {
        return $this->schema->table($name);
    }

    /**
     * Add index
     */
    protected function addIndex(string $table, array $columns, ?string $name = null): void
    {
        $this->schema->table($table)->addIndex($columns, $name);
    }

    /**
     * Drop index
     */
    protected function dropIndex(string $table, string $name): void
    {
        $this->schema->table($table)->dropIndex($name);
    }

    /**
     * Add foreign key
     */
    protected function addForeignKey(
        string $table,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        ?string $name = null,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): void {
        $this->schema->table($table)->addForeignKey(
            $column,
            $referenceTable,
            $referenceColumn,
            $name,
            $onDelete,
            $onUpdate
        );
    }

    /**
     * Drop foreign key
     */
    protected function dropForeignKey(string $table, string $name): void
    {
        $this->schema->table($table)->dropForeignKey($name);
    }

    /**
     * Insert data
     */
    protected function insert(string $table, array $data): void
    {
        if (empty($data)) {
            return;
        }

        // Handle batch insert
        if (isset($data[0]) && is_array($data[0])) {
            $columns = array_keys($data[0]);
            $values = [];
            $params = [];
            
            foreach ($data as $row) {
                $placeholders = array_fill(0, count($columns), '?');
                $values[] = '(' . implode(', ', $placeholders) . ')';
                $params = array_merge($params, array_values($row));
            }
            
            $escapedTable = "`{$table}`";
            $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
            $sql = "INSERT INTO {$escapedTable} (" . implode(', ', $escapedColumns) . ") VALUES " . implode(', ', $values);
            $this->db->query($sql, $params);
        } else {
            $escapedTable = "`{$table}`";
            $this->db->write_data($escapedTable, $data);
        }
    }

    /**
     * Update data
     */
    protected function update(string $table, array $data, string $where, array $params = []): void
    {
        $escapedTable = "`{$table}`";
        $this->db->update($escapedTable, $data, "WHERE $where", $params);
    }

    /**
     * Delete data
     */
    protected function delete(string $table, string $where, array $params = []): void
    {
        $escapedTable = "`{$table}`";
        $this->db->delete($escapedTable, "WHERE $where", $params);
    }

    /**
     * Get current timestamp for migrations
     */
    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Add index only if it doesn't exist (idempotent)
     */
    protected function addIndexIfNotExists(string $table, array $columns, ?string $name = null): void
    {
        $this->schema->addIndexIfNotExists($table, $columns, $name);
    }

    /**
     * Add unique index only if it doesn't exist (idempotent)
     */
    protected function addUniqueIndexIfNotExists(string $table, array $columns, ?string $name = null): void
    {
        $this->schema->addUniqueIndexIfNotExists($table, $columns, $name);
    }

    /**
     * Add foreign key only if it doesn't exist (idempotent)
     */
    protected function addForeignKeyIfNotExists(
        string $table,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        ?string $name = null,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): void {
        $this->schema->addForeignKeyIfNotExists($table, $column, $referenceTable, $referenceColumn, $name, $onDelete, $onUpdate);
    }

    /**
     * Add column only if it doesn't exist (idempotent)
     */
    protected function addColumnIfNotExists(string $table, string $column, array $definition): void
    {
        $this->schema->addColumnIfNotExists($table, $column, $definition);
    }

    /**
     * Insert record only if it doesn't exist based on unique fields
     */
    protected function insertIfNotExists(string $table, array $data, array $uniqueFields = []): bool
    {
        return $this->schema->insertIfNotExists($table, $data, $uniqueFields);
    }

    /**
     * Insert multiple records only if they don't exist based on unique fields
     */
    protected function insertManyIfNotExists(string $table, array $records, array $uniqueFields = []): array
    {
        return $this->schema->insertManyIfNotExists($table, $records, $uniqueFields);
    }

    /**
     * Upsert record (insert if not exists, update if exists)
     */
    protected function upsert(string $table, array $data, array $uniqueFields = []): bool
    {
        return $this->schema->upsert($table, $data, $uniqueFields);
    }

    /**
     * Check if record exists based on unique fields
     */
    protected function recordExists(string $table, array $data, array $uniqueFields = []): bool
    {
        return $this->schema->recordExists($table, $data, $uniqueFields);
    }

    /**
     * Get unique fields for a table
     */
    protected function getUniqueFields(string $table): array
    {
        return $this->schema->getUniqueFields($table);
    }

    /**
     * Check if index exists
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        return $this->schema->hasIndex($table, $indexName);
    }

    /**
     * Check if index exists by columns
     */
    protected function hasIndexByColumns(string $table, array $columns): bool
    {
        return $this->schema->hasIndexByColumns($table, $columns);
    }

    /**
     * Get all indexes for a table
     */
    protected function getIndexes(string $table): array
    {
        return $this->schema->getIndexes($table);
    }
} 