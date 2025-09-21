<?php

namespace SimpleMDB;

use SimpleMDB\SchemaBuilder;

/**
 * PostgreSQL-specific SchemaBuilder
 * Extends base SchemaBuilder with PostgreSQL-specific syntax and data types
 */
class SchemaBuilder_PostgreSQL extends SchemaBuilder
{
    private array $columnComments = [];
    
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

    /**
     * Override column name validation for PostgreSQL
     */
    protected function validateColumnName(string $name): void
    {
        // Check for empty name
        if (empty($name)) {
            throw new \InvalidArgumentException('Column name cannot be empty.');
        }
        
        // Check length (PostgreSQL limit is 63 characters)
        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("Column name '{$name}' is too long. Maximum length is 63 characters for PostgreSQL.");
        }
        
        // Check for invalid characters (PostgreSQL allows letters, digits, underscores, dollar signs)
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $name)) {
            throw new \InvalidArgumentException("Column name '{$name}' contains invalid characters. Use only letters, digits, underscores, and dollar signs, starting with a letter, underscore, or dollar sign.");
        }
        
        // Check for reserved words (case-insensitive)
        if (in_array(strtolower($name), self::POSTGRESQL_RESERVED_WORDS)) {
            throw new \InvalidArgumentException("Column name '{$name}' is a PostgreSQL reserved word. Please use a different name or quote it.");
        }
    }

    /**
     * Override createTable to use PostgreSQL syntax
     */
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
        foreach ($this->columns as $column) {
            $columnDefinitions[] = $this->buildPostgreSQLColumnDefinition($column);
        }

        // Add primary key constraint if defined
        if (!empty($this->primaryKey)) {
            $pkColumns = implode('", "', $this->primaryKey);
            $columnDefinitions[] = "    PRIMARY KEY (\"$pkColumns\")";
        }

        $sql .= implode(",\n", $columnDefinitions);
        $sql .= "\n)";

        // Execute the table creation
        $this->db->query($sql);

        // Create indexes separately (PostgreSQL doesn't support inline index creation like MySQL)
        foreach ($this->indexes as $index) {
            $this->createPostgreSQLIndex($tableName, $index);
        }

        // Create foreign key constraints separately
        foreach ($this->foreignKeys as $fk) {
            $this->createPostgreSQLForeignKey($tableName, $fk);
        }

        $this->reset();
        return true;
    }

    /**
     * Build PostgreSQL column definition
     */
    private function buildPostgreSQLColumnDefinition(array $column): string
    {
        $definition = "    \"" . $column['name'] . "\" ";
        
        // Map MySQL types to PostgreSQL types
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
            } elseif (is_string($column['default'])) {
                $definition .= " DEFAULT '" . addslashes($column['default']) . "'";
            } else {
                $definition .= " DEFAULT " . $column['default'];
            }
        }
        
        if ($column['autoIncrement']) {
            // PostgreSQL uses SERIAL or BIGSERIAL for auto-increment
            if (strpos($column['type'], 'bigint') !== false) {
                $definition = str_replace($column['type'], 'BIGSERIAL', $definition);
            } else {
                $definition = str_replace($column['type'], 'SERIAL', $definition);
            }
        }
        
        if (isset($column['comment'])) {
            // PostgreSQL comments are added separately, store for later
            $this->columnComments[$column['name']] = $column['comment'];
        }

        return $definition;
    }

    /**
     * Map MySQL data types to PostgreSQL equivalents
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
            case 'longtext':
            case 'mediumtext':
                return "TEXT";
            
            case 'tinytext':
                return "VARCHAR(255)";
            
            case 'int':
            case 'integer':
                return "INTEGER";
            
            case 'tinyint':
                return "SMALLINT";
            
            case 'smallint':
                return "SMALLINT";
            
            case 'mediumint':
                return "INTEGER";
            
            case 'bigint':
                return "BIGINT";
            
            case 'decimal':
            case 'numeric':
                if ($precision && $scale) {
                    return "DECIMAL($precision,$scale)";
                } elseif ($precision) {
                    return "DECIMAL($precision)";
                }
                return "DECIMAL";
            
            case 'float':
                return "REAL";
            
            case 'double':
                return "DOUBLE PRECISION";
            
            case 'boolean':
            case 'bool':
                return "BOOLEAN";
            
            case 'date':
                return "DATE";
            
            case 'time':
                return $precision ? "TIME($precision)" : "TIME";
            
            case 'datetime':
                return "TIMESTAMP";
            
            case 'timestamp':
                return "TIMESTAMP";
            
            case 'json':
                return "JSON";
            
            case 'jsonb':
                return "JSONB";
            
            case 'uuid':
                return "UUID";
            
            case 'inet':
            case 'ipaddress':
                return "INET";
            
            case 'macaddr':
            case 'macaddress':
                return "MACADDR";
            
            case 'blob':
            case 'longblob':
            case 'mediumblob':
                return "BYTEA";
            
            case 'binary':
            case 'varbinary':
                return "BYTEA";
            
            case 'enum':
                // PostgreSQL doesn't have ENUM like MySQL, use CHECK constraint or custom type
                // For now, we'll use VARCHAR with a CHECK constraint
                if (isset($column['values']) && is_array($column['values'])) {
                    $values = implode("','", $column['values']);
                    return "VARCHAR CHECK (\"" . $column['name'] . "\" IN ('$values'))";
                }
                return "VARCHAR";
            
            case 'set':
                // PostgreSQL doesn't have SET type, use array or separate table
                return "TEXT[]";
            
            case 'point':
                return "POINT";
            
            case 'geometry':
                return "GEOMETRY"; // Requires PostGIS extension
            
            default:
                return strtoupper($type);
        }
    }

    /**
     * Create PostgreSQL index
     */
    private function createPostgreSQLIndex(string $tableName, array $index): void
    {
        $indexName = $index['name'] ?? ($tableName . '_' . implode('_', $index['columns']) . '_idx');
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
        $sql .= "REFERENCES \"" . $fk['references']['table'] . "\" (\"" . $fk['references']['column'] . "\")";
        
        if (isset($fk['onUpdate'])) {
            $sql .= " ON UPDATE " . strtoupper($fk['onUpdate']);
        }
        
        if (isset($fk['onDelete'])) {
            $sql .= " ON DELETE " . strtoupper($fk['onDelete']);
        }
        
        $this->db->query($sql);
    }

    /**
     * Override dropTable for PostgreSQL syntax
     */
    public function dropTable(string $tableName): bool
    {
        $this->validateTableName($tableName);
        $sql = "DROP TABLE IF EXISTS \"$tableName\" CASCADE";
        $this->db->query($sql);
        return true;
    }

    /**
     * Override hasTable for PostgreSQL system tables
     */
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

    /**
     * Override hasColumn for PostgreSQL system tables
     */
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

    /**
     * Helper method to add a column to the schema definition
     */
    protected function addColumnToSchema(string $name, string $type, array $options = []): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = array_merge([
            'type' => $type,
            'nullable' => true,
            'default' => null,
            'autoIncrement' => false,
            'length' => null,
            'precision' => null,
            'scale' => null
        ], $options);
        
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * PostgreSQL-specific JSONB column type
     */
    public function jsonb(string $name): self
    {
        return $this->addColumnToSchema($name, 'jsonb');
    }

    /**
     * PostgreSQL-specific INET column type for IP addresses
     */
    public function inet(string $name): self
    {
        return $this->addColumnToSchema($name, 'inet');
    }

    /**
     * PostgreSQL-specific MACADDR column type for MAC addresses
     */
    public function macaddr(string $name): self
    {
        return $this->addColumnToSchema($name, 'macaddr');
    }

    /**
     * PostgreSQL-specific array column types
     */
    public function textArray(string $name): self
    {
        return $this->addColumnToSchema($name, 'text[]');
    }

    public function integerArray(string $name): self
    {
        return $this->addColumnToSchema($name, 'integer[]');
    }

    /**
     * PostgreSQL-specific UUID column with generation
     */
    public function uuidWithDefault(string $name): self
    {
        return $this->addColumnToSchema($name, 'uuid', ['default' => 'gen_random_uuid()']);
    }

    /**
     * Override table name validation for PostgreSQL
     */
    protected function validateTableName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }
        
        // PostgreSQL table name limit is 63 characters
        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("Table name '{$name}' is too long. Maximum length is 63 characters for PostgreSQL.");
        }
        
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $name)) {
            throw new \InvalidArgumentException("Table name '{$name}' contains invalid characters. Use only letters, digits, underscores, and dollar signs, starting with a letter, underscore, or dollar sign.");
        }
    }

    /**
     * Get PostgreSQL-specific table information
     */
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
     * Add column comments (PostgreSQL requires separate COMMENT statements)
     */
    private function addColumnComments(string $tableName): void
    {
        if (isset($this->columnComments)) {
            foreach ($this->columnComments as $columnName => $comment) {
                $sql = "COMMENT ON COLUMN \"$tableName\".\"$columnName\" IS '" . addslashes($comment) . "'";
                $this->db->query($sql);
            }
        }
    }
}
