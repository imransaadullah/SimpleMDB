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
     * Create a new table
     */
    protected function createTable(string $name, callable $callback): void
    {
        // Create a fresh SchemaBuilder instance for each table to avoid state conflicts
        $tableBuilder = new SchemaBuilder($this->db);
        $callback($tableBuilder);
        $tableBuilder->createTable($name);
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
} 