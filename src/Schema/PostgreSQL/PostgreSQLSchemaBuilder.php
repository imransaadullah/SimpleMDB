<?php

namespace SimpleMDB\Schema\PostgreSQL;

use SimpleMDB\Interfaces\SchemaBuilderInterface;
use SimpleMDB\Interfaces\ForeignKeyDefinitionInterface;
use SimpleMDB\DatabaseInterface;
use SimpleMDB\Schema\PostgreSQL\PostgreSQLForeignKeyDefinition;

/**
 * PostgreSQLSchemaBuilder
 * 
 * PostgreSQL-specific implementation of SchemaBuilderInterface
 * Handles PostgreSQL syntax, data types, and constraints
 */
class PostgreSQLSchemaBuilder implements SchemaBuilderInterface
{
    protected DatabaseInterface $db;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = [];
    protected array $primaryKey = [];
    protected ?string $lastColumn = null;
    protected bool $ifNotExists = false;
    protected array $columnComments = [];
    
    // PostgreSQL reserved words that should be avoided as column names
    private const POSTGRESQL_RESERVED_WORDS = [
        'select', 'insert', 'update', 'delete', 'create', 'drop', 'alter', 'table', 'index', 'view',
        'database', 'schema', 'primary', 'key', 'foreign', 'unique', 'constraint', 'null', 'not',
        'default', 'serial', 'bigserial', 'timestamp', 'timestamptz', 'date', 'time', 'timetz', 'interval',
        'integer', 'int', 'int4', 'bigint', 'int8', 'smallint', 'int2', 'varchar', 'char', 'text', 'bytea',
        'numeric', 'decimal', 'real', 'float4', 'double', 'float8', 'boolean', 'bool', 'json', 'jsonb',
        'order', 'by', 'group', 'having', 'where', 'from', 'join', 'inner', 'left', 'right', 'outer', 'full',
        'union', 'exists', 'in', 'between', 'like', 'ilike', 'similar', 'and', 'or', 'not', 'case', 'when',
        'then', 'else', 'end', 'if', 'coalesce', 'nullif', 'count', 'sum', 'avg', 'min', 'max',
        'distinct', 'limit', 'offset', 'desc', 'asc', 'show', 'explain', 'grant', 'revoke', 'array', 'uuid'
    ];

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }
    
    /**
     * Validate column name for PostgreSQL
     */
    protected function validateColumnName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Column name cannot be empty.');
        }
        
        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("Column name '{$name}' is too long. Maximum length is 63 characters for PostgreSQL.");
        }
        
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $name)) {
            throw new \InvalidArgumentException("Column name '{$name}' contains invalid characters. Use only letters, digits, underscores, and dollar signs, starting with a letter, underscore, or dollar sign.");
        }
        
        if (in_array(strtolower($name), self::POSTGRESQL_RESERVED_WORDS)) {
            throw new \InvalidArgumentException("Column name '{$name}' is a PostgreSQL reserved word. Please use a different name or quote it.");
        }
    }

    /**
     * Validate table name for PostgreSQL
     */
    protected function validateTableName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }
        
        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("Table name '{$name}' is too long. Maximum length is 63 characters for PostgreSQL.");
        }
        
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $name)) {
            throw new \InvalidArgumentException("Table name '{$name}' contains invalid characters. Use only letters, digits, underscores, and dollar signs, starting with a letter, underscore, or dollar sign.");
        }
    }

    public function integer(string $name, bool $unsigned = false, bool $autoIncrement = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => $autoIncrement ? 'SERIAL' : 'INTEGER',
            'nullable' => false,
            'default' => null,
            'autoIncrement' => $autoIncrement
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function bigInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BIGINT',
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
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function tinyInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        // PostgreSQL doesn't have TINYINT, use SMALLINT
        return $this->smallInteger($name, $unsigned);
    }

    public function mediumInteger(string $name, bool $unsigned = false): SchemaBuilderInterface
    {
        // PostgreSQL doesn't have MEDIUMINT, use INTEGER
        return $this->integer($name, $unsigned);
    }

    public function increments(string $name = 'id'): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'SERIAL',
            'nullable' => false,
            'default' => null,
            'autoIncrement' => true
        ];
        $this->primary([$name]);
        $this->lastColumn = $name;
        return $this;
    }

    public function bigIncrements(string $name = 'id'): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BIGSERIAL',
            'nullable' => false,
            'default' => null,
            'autoIncrement' => true
        ];
        $this->primary([$name]);
        $this->lastColumn = $name;
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
        // PostgreSQL doesn't have MEDIUMTEXT, use TEXT
        return $this->text($name);
    }

    public function longText(string $name): SchemaBuilderInterface
    {
        // PostgreSQL doesn't have LONGTEXT, use TEXT
        return $this->text($name);
    }

    public function boolean(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BOOLEAN',
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
            'type' => 'TIMESTAMP',
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
        $this->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP');
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
            'type' => 'REAL',
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
            'type' => 'DOUBLE PRECISION',
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

    /**
     * PostgreSQL-specific JSONB column type
     */
    public function jsonb(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'JSONB',
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
            'type' => 'UUID',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * PostgreSQL-specific UUID column with default generation
     */
    public function uuidWithDefault(string $name): SchemaBuilderInterface
    {
        $this->uuid($name)->default('gen_random_uuid()');
        return $this;
    }

    public function ipAddress(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'INET',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * PostgreSQL-specific INET column type for IP addresses
     */
    public function inet(string $name): SchemaBuilderInterface
    {
        return $this->ipAddress($name);
    }

    public function macAddress(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'MACADDR',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * PostgreSQL-specific MACADDR column type for MAC addresses
     */
    public function macaddr(string $name): SchemaBuilderInterface
    {
        return $this->macAddress($name);
    }

    public function binary(string $name, int $length = 255): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BYTEA',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function enum(string $name, array $values): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        // PostgreSQL doesn't have ENUM like MySQL, use CHECK constraint
        $this->columns[$name] = [
            'type' => 'VARCHAR',
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
        
        // PostgreSQL doesn't have SET type, use array
        $this->columns[$name] = [
            'type' => 'TEXT[]',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * PostgreSQL-specific array column types
     */
    public function textArray(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TEXT[]',
            'nullable' => false,
            'default' => null
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function integerArray(string $name): SchemaBuilderInterface
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'INTEGER[]',
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
                'unique' => true,
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
                'unique' => false,
                'columns' => [$this->lastColumn]
            ];
        }
        return $this;
    }

    public function comment(string $comment): SchemaBuilderInterface
    {
        if ($this->lastColumn) {
            $this->columnComments[$this->lastColumn] = $comment;
        }
        return $this;
    }

    public function unsigned(): SchemaBuilderInterface
    {
        // PostgreSQL doesn't have unsigned types, this is a no-op for compatibility
        return $this;
    }

    public function primary(array $columns): SchemaBuilderInterface
    {
        $this->primaryKey = $columns;
        return $this;
    }

    public function foreign(string $column): ForeignKeyDefinitionInterface
    {
        return new PostgreSQLForeignKeyDefinition($this, $column);
    }

    public function createTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        
        if (empty($this->columns)) {
            throw new \InvalidArgumentException('Cannot create table without columns.');
        }

        $sql = "CREATE TABLE ";
        if ($this->ifNotExists) {
            $sql .= "IF NOT EXISTS ";
        }
        $sql .= "\"$tableName\" (\n";

        $columnDefinitions = [];
        foreach ($this->columns as $name => $column) {
            $columnDefinitions[] = $this->buildPostgreSQLColumnDefinition($name, $column);
        }

        // Add primary key constraint if defined
        if (!empty($this->primaryKey)) {
            $pkColumns = '"' . implode('", "', $this->primaryKey) . '"';
            $columnDefinitions[] = "    PRIMARY KEY ($pkColumns)";
        }

        $sql .= implode(",\n", $columnDefinitions);
        $sql .= "\n)";

        // Execute the table creation
        $this->db->query($sql);

        // Create indexes separately (PostgreSQL doesn't support inline index creation like MySQL)
        foreach ($this->indexes as $indexName => $index) {
            $this->createPostgreSQLIndex($tableName, $indexName, $index);
        }

        // Create foreign key constraints separately
        foreach ($this->foreignKeys as $fk) {
            $this->createPostgreSQLForeignKey($tableName, $fk);
        }

        // Add column comments
        $this->addColumnComments($tableName);

        $this->reset();
        return true;
    }

    public function dropTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        $sql = "DROP TABLE IF EXISTS \"$tableName\" CASCADE";
        $this->db->query($sql);
        return true;
    }

    public function hasTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        $sql = "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = ?
        )";
        
        $result = $this->db->query($sql, [$tableName])->fetch('col');
        return (bool) $result;
    }

    public function hasColumn(string $tableName, string $columnName): bool
    {
        $this->validateTableName($tableName);
        $this->validateColumnName($columnName);
        
        $sql = "SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = ? 
            AND column_name = ?
        )";
        
        $result = $this->db->query($sql, [$tableName, $columnName])->fetch('col');
        return (bool) $result;
    }

    public function reset(): SchemaBuilderInterface
    {
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        $this->primaryKey = [];
        $this->lastColumn = null;
        $this->ifNotExists = false;
        $this->columnComments = [];
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
        $this->integer($name . '_id');
        $this->string($name . '_type');
        return $this;
    }

    public function getTableInfo(string $tableName): array
    {
        $this->validateTableName($tableName);
        
        $sql = "SELECT 
            column_name,
            data_type,
            is_nullable,
            column_default,
            character_maximum_length,
            numeric_precision,
            numeric_scale
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = ?
        ORDER BY ordinal_position";
        
        return $this->db->query($sql, [$tableName])->fetchAll('assoc');
    }

    /**
     * Build PostgreSQL column definition
     */
    private function buildPostgreSQLColumnDefinition(string $name, array $column): string
    {
        $definition = "    \"$name\" ";
        
        // Add type
        $definition .= $this->mapToPostgreSQLType($column);
        
        // Add constraints
        if (!$column['nullable']) {
            $definition .= " NOT NULL";
        }
        
        if (isset($column['default'])) {
            if ($column['default'] === 'CURRENT_TIMESTAMP') {
                $definition .= " DEFAULT CURRENT_TIMESTAMP";
            } elseif ($column['default'] === 'NULL') {
                $definition .= " DEFAULT NULL";
            } elseif (is_string($column['default']) && $column['default'] !== 'gen_random_uuid()') {
                $definition .= " DEFAULT '" . addslashes($column['default']) . "'";
            } else {
                $definition .= " DEFAULT " . $column['default'];
            }
        }

        return $definition;
    }

    /**
     * Map column types to PostgreSQL equivalents
     */
    private function mapToPostgreSQLType(array $column): string
    {
        $type = strtolower($column['type']);
        $length = $column['length'] ?? null;
        $precision = $column['precision'] ?? null;
        $scale = $column['scale'] ?? null;

        switch ($type) {
            case 'varchar':
                return $length ? "VARCHAR($length)" : "VARCHAR";
            
            case 'char':
                return $length ? "CHAR($length)" : "CHAR";
            
            case 'text':
                return "TEXT";
            
            case 'integer':
                return "INTEGER";
            
            case 'serial':
                return "SERIAL";
            
            case 'bigint':
                return "BIGINT";
            
            case 'bigserial':
                return "BIGSERIAL";
            
            case 'smallint':
                return "SMALLINT";
            
            case 'decimal':
                if ($precision && $scale) {
                    return "DECIMAL($precision,$scale)";
                } elseif ($precision) {
                    return "DECIMAL($precision)";
                }
                return "DECIMAL";
            
            case 'real':
                return "REAL";
            
            case 'double precision':
                return "DOUBLE PRECISION";
            
            case 'boolean':
                return "BOOLEAN";
            
            case 'date':
                return "DATE";
            
            case 'time':
                return $precision ? "TIME($precision)" : "TIME";
            
            case 'timestamp':
                return "TIMESTAMP";
            
            case 'json':
                return "JSON";
            
            case 'jsonb':
                return "JSONB";
            
            case 'uuid':
                return "UUID";
            
            case 'inet':
                return "INET";
            
            case 'macaddr':
                return "MACADDR";
            
            case 'bytea':
                return "BYTEA";
            
            case 'text[]':
                return "TEXT[]";
            
            case 'integer[]':
                return "INTEGER[]";
            
            default:
                return strtoupper($type);
        }
    }

    /**
     * Create PostgreSQL index
     */
    private function createPostgreSQLIndex(string $tableName, string $indexName, array $index): void
    {
        $columns = '"' . implode('", "', $index['columns']) . '"';
        
        $sql = "CREATE ";
        if ($index['unique'] ?? false) {
            $sql .= "UNIQUE ";
        }
        $sql .= "INDEX \"$indexName\" ON \"$tableName\" ($columns)";
        
        $this->db->query($sql);
    }

    /**
     * Create PostgreSQL foreign key constraint
     */
    private function createPostgreSQLForeignKey(string $tableName, array $fk): void
    {
        $constraintName = $fk['name'] ?? ($tableName . '_' . $fk['column'] . '_fkey');
        
        $sql = "ALTER TABLE \"$tableName\" ADD CONSTRAINT \"$constraintName\" ";
        $sql .= "FOREIGN KEY (\"" . $fk['column'] . "\") ";
        $sql .= "REFERENCES \"" . $fk['reference_table'] . "\" (\"" . $fk['reference_column'] . "\")";
        
        if (isset($fk['on_update'])) {
            $sql .= " ON UPDATE " . strtoupper($fk['on_update']);
        }
        
        if (isset($fk['on_delete'])) {
            $sql .= " ON DELETE " . strtoupper($fk['on_delete']);
        }
        
        $this->db->query($sql);
    }

    /**
     * Add column comments (PostgreSQL requires separate COMMENT statements)
     */
    private function addColumnComments(string $tableName): void
    {
        foreach ($this->columnComments as $columnName => $comment) {
            $sql = "COMMENT ON COLUMN \"$tableName\".\"$columnName\" IS '" . addslashes($comment) . "'";
            $this->db->query($sql);
        }
    }

    /**
     * Add a foreign key to the schema
     */
    public function addForeignKey(array $foreignKey): void
    {
        $this->foreignKeys[] = $foreignKey;
    }
}

