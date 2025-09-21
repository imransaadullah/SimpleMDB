<?php

namespace SimpleMDB\Schema\MySQL;

use SimpleMDB\Interfaces\SchemaBuilderInterface;
use SimpleMDB\Interfaces\ForeignKeyDefinitionInterface;
use SimpleMDB\DatabaseInterface;
use SimpleMDB\Schema\MySQL\MySQLForeignKeyDefinition;

/**
 * MySQLSchemaBuilder
 * 
 * MySQL-specific implementation of SchemaBuilderInterface
 * Handles MySQL syntax, data types, and constraints
 */
class MySQLSchemaBuilder implements SchemaBuilderInterface
{
    protected DatabaseInterface $db;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = [];
    protected array $primaryKey = [];
    protected ?string $engine = null;
    protected ?string $charset = null;
    protected ?string $collation = null;
    protected ?string $lastColumn = null;
    protected bool $ifNotExists = false;
    
    // MySQL reserved words that should be avoided as column names
    private const MYSQL_RESERVED_WORDS = [
        'select', 'insert', 'update', 'delete', 'create', 'drop', 'alter', 'table', 'index', 'view',
        'database', 'schema', 'primary', 'key', 'foreign', 'unique', 'constraint', 'null', 'not',
        'default', 'auto_increment', 'timestamp', 'datetime', 'date', 'time', 'year', 'int', 'integer',
        'varchar', 'char', 'text', 'blob', 'decimal', 'float', 'double', 'boolean', 'bool', 'json',
        'order', 'by', 'group', 'having', 'where', 'from', 'join', 'inner', 'left', 'right', 'outer',
        'union', 'exists', 'in', 'between', 'like', 'regexp', 'and', 'or', 'xor', 'case', 'when',
        'then', 'else', 'end', 'if', 'ifnull', 'nullif', 'count', 'sum', 'avg', 'min', 'max',
        'distinct', 'limit', 'offset', 'desc', 'asc', 'show', 'describe', 'explain', 'grant', 'revoke'
    ];

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }
    
    /**
     * Validate column name for MySQL
     */
    protected function validateColumnName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Column name cannot be empty.');
        }
        
        if (strlen($name) > 64) {
            throw new \InvalidArgumentException("Column name '{$name}' is too long. Maximum length is 64 characters.");
        }
        
        if (in_array(strtolower($name), self::MYSQL_RESERVED_WORDS)) {
            throw new \InvalidArgumentException("Column name '{$name}' is a MySQL reserved word. Consider using a different name or add backticks.");
        }
        
        if (isset($this->columns[$name])) {
            throw new \InvalidArgumentException("Column '{$name}' already exists in this table definition.");
        }
    }

    /**
     * Validate table name for MySQL
     */
    protected function validateTableName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }
        
        if (strlen($name) > 64) {
            throw new \InvalidArgumentException("Table name '{$name}' is too long. Maximum length is 64 characters.");
        }
    }

    public function integer(string $name, bool $unsigned = false, bool $autoIncrement = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'INT',
            'unsigned' => $unsigned,
            'auto_increment' => $autoIncrement,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function bigInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => $unsigned,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function smallInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'SMALLINT',
            'unsigned' => $unsigned,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function tinyInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'unsigned' => $unsigned,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function mediumInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'MEDIUMINT',
            'unsigned' => $unsigned,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function increments(string $name = 'id'): SchemaBuilderInterface
    {
        $this->integer($name, true, true);
        $this->primary([$name]);
        return $this;
    }

    public function bigIncrements(string $name = 'id'): SchemaBuilderInterface
    {
        $this->bigInteger($name, true);
        $this->columns[$name]['auto_increment'] = true;
        $this->primary([$name]);
        return $this;
    }

    public function string(string $name, int $length = 255): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => $length,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function char(string $name, int $length = 1): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'CHAR',
            'length' => $length,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function text(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TEXT',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function mediumText(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'MEDIUMTEXT',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function longText(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'LONGTEXT',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function boolean(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'length' => 1,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function date(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'DATE',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function dateTime(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'DATETIME',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function time(string $name, int $precision = 0): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TIME',
            'precision' => $precision,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function timestamp(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TIMESTAMP',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function timestamps(): SchemaBuilderInterface
    {
        $this->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        return $this;
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'DECIMAL',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function float(string $name, int $precision = 8, int $scale = 2): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'FLOAT',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function double(string $name, int $precision = 8, int $scale = 2): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'DOUBLE',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function json(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'JSON',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function uuid(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'CHAR',
            'length' => 36,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function ipAddress(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => 45, // IPv6 compatible
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function macAddress(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => 17,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function binary(string $name, int $length = 255): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BINARY',
            'length' => $length,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function enum(string $name, array $values): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'ENUM',
            'values' => $values,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function set(string $name, array $values): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'SET',
            'values' => $values,
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function nullable(bool $nullable = true): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $this->columns[$this->lastColumn]['nullable'] = $nullable;
        }
        return $this;
    }

    public function default($value): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $this->columns[$this->lastColumn]['default'] = $value;
        }
        return $this;
    }

    public function unique(string $indexName = null): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $indexName = $indexName ?: $this->lastColumn . '_unique';
            $this->indexes[$indexName] = [
                'type' => 'UNIQUE KEY',
                'columns' => [$this->lastColumn]
            ];
        }
        return $this;
    }

    public function index(string $indexName = null): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $indexName = $indexName ?: $this->lastColumn . '_index';
            $this->indexes[$indexName] = [
                'type' => 'KEY',
                'columns' => [$this->lastColumn]
            ];
        }
        return $this;
    }

    public function comment(string $comment): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $this->columns[$this->lastColumn]['comment'] = $comment;
        }
        return $this;
    }

    public function unsigned(): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $this->columns[$this->lastColumn]['unsigned'] = true;
        }
        return $this;
    }

    public function primary(array $columns): SchemaBuilderInterface
    {
        $this->primaryKey = $columns;
        return $this;
    }

    public function foreign(string $column): ForeignKeyDefinitionInterface
    {
        return new MySQLForeignKeyDefinition($this, $column);
    }

    public function createTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        
        if (empty($this->columns)) {
            throw new \InvalidArgumentException("Cannot create table '{$tableName}': No columns defined. Add at least one column.");
        }

        $escapedTableName = "`{$tableName}`";
        $ifNotExistsClause = $this->ifNotExists ? "IF NOT EXISTS " : "";
        $sql = "CREATE TABLE {$ifNotExistsClause}{$escapedTableName} (\n";
        
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

        $this->db->query($sql);
        $this->reset();
        return true;
    }

    public function dropTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        $this->db->query($sql);
        return true;
    }

    public function hasTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->db->query($sql, [$tableName])->fetch('col');
        return !empty($result);
    }

    public function hasColumn(string $tableName, string $columnName): bool
    {
        $this->validateTableName($tableName);
        $this->validateColumnName($columnName);
        
        $sql = "SHOW COLUMNS FROM `{$tableName}` LIKE ?";
        $result = $this->db->query($sql, [$columnName])->fetch('col');
        return !empty($result);
    }

    public function reset(): SchemaBuilderInterface
    {
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        $this->primaryKey = [];
        $this->engine = null;
        $this->charset = null;
        $this->collation = null;
        $this->lastColumn = null;
        $this->ifNotExists = false;
        return $this;
    }

    public function ifNotExists(): SchemaBuilderInterface
    {
        $this->ifNotExists = true;
        return $this;
    }

    public function softDeletes(): SchemaBuilderInterface
    {
        $this->timestamp('deleted_at')->nullable();
        return $this;
    }

    public function rememberToken(): SchemaBuilderInterface
    {
        $this->string('remember_token', 100)->nullable();
        return $this;
    }

    public function morphs(string $name): SchemaBuilderInterface
    {
        $this->integer($name . '_id')->unsigned();
        $this->string($name . '_type');
        return $this;
    }

    public function getTableInfo(string $tableName): array
    {
        $this->validateTableName($tableName);
        $sql = "DESCRIBE `{$tableName}`";
        return $this->db->query($sql)->fetchAll('assoc');
    }

    /**
     * Build MySQL column definition
     */
    private function buildColumnDefinition(string $name, array $column): string
    {
        $definition = "    `{$name}` ";
        
        // Add type
        $definition .= $column['type'];
        
        // Add length/precision
        if (isset($column['length'])) {
            $definition .= "({$column['length']})";
        } elseif (isset($column['precision']) && isset($column['scale'])) {
            $definition .= "({$column['precision']},{$column['scale']})";
        } elseif (isset($column['precision'])) {
            $definition .= "({$column['precision']})";
        }
        
        // Add values for ENUM/SET
        if (isset($column['values'])) {
            $values = implode("','", $column['values']);
            $definition .= "('{$values}')";
        }
        
        // Add unsigned
        if (isset($column['unsigned']) && $column['unsigned']) {
            $definition .= " UNSIGNED";
        }
        
        // Add nullable
        if (!$column['nullable']) {
            $definition .= " NOT NULL";
        }
        
        // Add auto increment
        if (isset($column['auto_increment']) && $column['auto_increment']) {
            $definition .= " AUTO_INCREMENT";
        }
        
        // Add default
        if (isset($column['default'])) {
            if ($column['default'] === 'CURRENT_TIMESTAMP' || $column['default'] === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') {
                $definition .= " DEFAULT {$column['default']}";
            } elseif ($column['default'] === 'NULL') {
                $definition .= " DEFAULT NULL";
            } elseif (is_string($column['default'])) {
                $definition .= " DEFAULT '" . addslashes($column['default']) . "'";
            } else {
                $definition .= " DEFAULT " . $column['default'];
            }
        }
        
        // Add comment
        if (isset($column['comment'])) {
            $definition .= " COMMENT '" . addslashes($column['comment']) . "'";
        }

        return $definition;
    }

    /**
     * Add a foreign key to the schema
     */
    public function addForeignKey(array $foreignKey): void
    {
        $this->foreignKeys[] = $foreignKey;
    }

    /**
     * Set table engine
     */
    public function engine(string $engine): SchemaBuilderInterface
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Set table charset
     */
    public function charset(string $charset): SchemaBuilderInterface
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Set table collation
     */
    public function collation(string $collation): SchemaBuilderInterface
    {
        $this->collation = $collation;
        return $this;
    }
}

