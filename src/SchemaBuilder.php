<?php

namespace SimpleMDB;

use SimpleMDB\TableAlter;

class SchemaBuilder
{
    private DatabaseInterface $db;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private array $primaryKey = [];
    private ?string $engine = null;
    private ?string $charset = null;
    private ?string $collation = null;
    private ?string $lastColumn = null;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function integer(string $name, bool $unsigned = false, bool $autoIncrement = false): self
    {
        $this->columns[$name] = [
            'type' => 'INT',
            'unsigned' => $unsigned,
            'auto_increment' => $autoIncrement
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function bigInteger(string $name, bool $unsigned = false): self
    {
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => $unsigned
        ];
        return $this;
    }

    public function string(string $name, int $length = 255): self
    {
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => $length
        ];
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'TEXT'
        ];
        return $this;
    }

    public function datetime(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'DATETIME'
        ];
        return $this;
    }

    public function timestamp(string $name, bool $onUpdate = false): self
    {
        $this->columns[$name] = [
            'type' => 'TIMESTAMP',
            'on_update' => $onUpdate
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->columns[$name] = [
            'type' => 'DECIMAL',
            'precision' => $precision,
            'scale' => $scale
        ];
        return $this;
    }

    public function boolean(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'length' => 1
        ];
        return $this;
    }

    public function json(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'JSON'
        ];
        return $this;
    }

    public function enum(string $name, array $values): self
    {
        $this->columns[$name] = [
            'type' => 'ENUM',
            'values' => $values
        ];
        return $this;
    }

    public function nullable(?string $name = null): self
    {
        $col = $name ?? $this->lastColumn;
        if ($col !== null && isset($this->columns[$col])) {
            $this->columns[$col]['nullable'] = true;
        }
        return $this;
    }

    public function default(?string $name, $value = null): self
    {
        // Handle both signatures: default($value) and default($name, $value)
        if ($value === null) {
            // Single parameter: default($value)
            $value = $name;
            $name = $this->lastColumn;
        }
        
        if ($name !== null && isset($this->columns[$name])) {
            $this->columns[$name]['default'] = $value;
        }
        return $this;
    }

    public function primaryKey(string|array $columns): self
    {
        $this->primaryKey = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function unique(string|array $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? implode('_', $columns) . '_unique';
        $this->indexes[$name] = [
            'columns' => $columns,
            'type' => 'UNIQUE'
        ];
        return $this;
    }

    public function index(string|array $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? implode('_', $columns) . '_index';
        $this->indexes[$name] = [
            'columns' => $columns,
            'type' => 'INDEX'
        ];
        return $this;
    }

    public function foreignKey(string $column, string $referenceTable, string $referenceColumn): self
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'reference_table' => $referenceTable,
            'reference_column' => $referenceColumn
        ];
        return $this;
    }

    public function engine(string $engine): self
    {
        $this->engine = $engine;
        return $this;
    }

    public function charset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    public function collation(string $collation): self
    {
        $this->collation = $collation;
        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at', true)->nullable()->default('CURRENT_TIMESTAMP');
        return $this;
    }

    public function softDeletes(): self
    {
        $this->timestamp('deleted_at')->nullable();
        return $this;
    }

    public function createTable(string $tableName): bool
    {
        // Escape table name to prevent SQL injection
        $escapedTableName = "`{$tableName}`";
        $sql = "CREATE TABLE {$escapedTableName} (\n";
        
        // Add columns
        $columnDefinitions = [];
        foreach ($this->columns as $name => $column) {
            $columnDefinitions[] = $this->buildColumnDefinition($name, $column);
        }
        
        // Add primary key
        if (!empty($this->primaryKey)) {
            $escapedPrimaryKeys = array_map(fn($col) => "`{$col}`", $this->primaryKey);
            $columnDefinitions[] = "PRIMARY KEY (" . implode(', ', $escapedPrimaryKeys) . ")";
        }
        
        // Add indexes
        foreach ($this->indexes as $name => $index) {
            $escapedIndexName = "`{$name}`";
            $escapedIndexColumns = array_map(fn($col) => "`{$col}`", $index['columns']);
            $columnDefinitions[] = "{$index['type']} {$escapedIndexName} (" . implode(', ', $escapedIndexColumns) . ")";
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $escapedColumn = "`{$fk['column']}`";
            $escapedRefTable = "`{$fk['reference_table']}`";
            $escapedRefColumn = "`{$fk['reference_column']}`";
            $columnDefinitions[] = "FOREIGN KEY ({$escapedColumn}) REFERENCES {$escapedRefTable}({$escapedRefColumn})";
        }
        
        $sql .= implode(",\n", $columnDefinitions);
        $sql .= "\n)";
        
        // Add table options
        if ($this->engine) {
            $sql .= " ENGINE={$this->engine}";
        }
        if ($this->charset) {
            $sql .= " DEFAULT CHARSET={$this->charset}";
        }
        if ($this->collation) {
            $sql .= " COLLATE={$this->collation}";
        }
        
        try {
            $this->db->query($sql);
            $this->reset();
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Failed to create table: " . $e->getMessage());
        }
    }

    /**
     * Reset the schema builder state
     */
    public function reset(): self
    {
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        $this->primaryKey = [];
        $this->engine = null;
        $this->charset = null;
        $this->collation = null;
        $this->lastColumn = null;
        return $this;
    }

    public function buildColumnDefinition(string $name, array $column): string
    {
        // Escape column name to prevent SQL injection
        $escapedName = "`{$name}`";
        $def = "{$escapedName} {$column['type']}";
        
        // Handle column length/precision/values
        if (isset($column['length'])) {
            $def .= "({$column['length']})";
        } elseif (isset($column['precision']) && isset($column['scale'])) {
            $def .= "({$column['precision']},{$column['scale']})";
        } elseif (isset($column['values'])) {
            $values = array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $column['values']);
            $def .= "(" . implode(',', $values) . ")";
        }
        
        // Add UNSIGNED modifier before NULL/NOT NULL
        if (isset($column['unsigned']) && $column['unsigned']) {
            $def .= " UNSIGNED";
        }
        
        // Handle NULL/NOT NULL
        if (isset($column['nullable']) && $column['nullable']) {
            $def .= " NULL";
        } else {
            $def .= " NOT NULL";
        }
        
        // Handle default values with proper escaping
        if (isset($column['default'])) {
            $default = $column['default'];
            if (is_string($default) && !in_array(strtoupper($default), ['NULL', 'CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'])) {
                $def .= " DEFAULT '" . str_replace("'", "''", $default) . "'";
            } else {
                $def .= " DEFAULT {$default}";
            }
        }
        
        // Add AUTO_INCREMENT
        if (isset($column['auto_increment']) && $column['auto_increment']) {
            $def .= " AUTO_INCREMENT";
        }
        
        // Add ON UPDATE clause
        if (isset($column['on_update']) && $column['on_update']) {
            $def .= " ON UPDATE CURRENT_TIMESTAMP";
        }
        
        return $def;
    }

    public function dropTable(string $tableName): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $this->db->query("DROP TABLE IF EXISTS {$escapedTableName}");
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Failed to drop table: " . $e->getMessage());
        }
    }

    public function hasTable(string $tableName): bool
    {
        try {
            $result = $this->db->query("SHOW TABLES LIKE ?", [$tableName]);
            return $result->numRows() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function hasColumn(string $tableName, string $columnName): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $result = $this->db->query("SHOW COLUMNS FROM {$escapedTableName} LIKE ?", [$columnName]);
            return $result->numRows() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addColumn(string $tableName, string $columnName, array $definition): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $sql = "ALTER TABLE {$escapedTableName} ADD COLUMN " . $this->buildColumnDefinition($columnName, $definition);
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Failed to add column: " . $e->getMessage());
        }
    }

    public function dropColumn(string $tableName, string $columnName): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $escapedColumnName = "`{$columnName}`";
            $this->db->query("ALTER TABLE {$escapedTableName} DROP COLUMN {$escapedColumnName}");
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Failed to drop column: " . $e->getMessage());
        }
    }

    public function modifyColumn(string $tableName, string $columnName, array $definition): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $sql = "ALTER TABLE {$escapedTableName} MODIFY COLUMN " . $this->buildColumnDefinition($columnName, $definition);
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Failed to modify column: " . $e->getMessage());
        }
    }

    public function onUpdate(): self
    {
        if ($this->lastColumn !== null) {
            $this->columns[$this->lastColumn]['on_update'] = true;
        }
        return $this;
    }

    public function table(string $tableName): TableAlter
    {
        return new TableAlter($this->db, $this, $tableName);
    }
} 