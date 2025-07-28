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
    private bool $ifNotExists = false;
    
    // Common MySQL reserved words that should be avoided as column names
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
     * Validate column name
     */
    private function validateColumnName(string $name): void
    {
        // Check for empty name
        if (empty($name)) {
            throw new \InvalidArgumentException('Column name cannot be empty.');
        }
        
        // Check length (MySQL limit is 64 characters)
        if (strlen($name) > 64) {
            throw new \InvalidArgumentException("Column name '{$name}' is too long. Maximum length is 64 characters.");
        }
        
        // Check for reserved words (case insensitive)
        if (in_array(strtolower($name), self::MYSQL_RESERVED_WORDS)) {
            throw new \InvalidArgumentException("Column name '{$name}' is a MySQL reserved word. Consider using a different name or add backticks.");
        }
        
        // Check for duplicate column names
        if (isset($this->columns[$name])) {
            throw new \InvalidArgumentException("Column '{$name}' already exists in this table definition.");
        }
    }

    public function integer(string $name, bool $unsigned = false, bool $autoIncrement = false): self
    {
        $this->validateColumnName($name);
        
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
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => $unsigned
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function string(string $name, int $length = 255): self
    {
        $this->validateColumnName($name);
        
        if ($length <= 0 || $length > 65535) {
            throw new \InvalidArgumentException("VARCHAR column '{$name}' length must be between 1 and 65535, got {$length}.");
        }
        
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => $length
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function text(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TEXT'
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function datetime(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'DATETIME'
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function timestamp(string $name, bool $onUpdate = false): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TIMESTAMP',
            'on_update' => $onUpdate
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->validateColumnName($name);
        
        if ($precision <= 0 || $precision > 65) {
            throw new \InvalidArgumentException("DECIMAL column '{$name}' precision must be between 1 and 65, got {$precision}.");
        }
        
        if ($scale < 0 || $scale > $precision) {
            throw new \InvalidArgumentException("DECIMAL column '{$name}' scale must be between 0 and {$precision}, got {$scale}.");
        }
        
        $this->columns[$name] = [
            'type' => 'DECIMAL',
            'precision' => $precision,
            'scale' => $scale
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function boolean(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'length' => 1
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function json(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'JSON'
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function enum(string $name, array $values): self
    {
        $this->validateColumnName($name);
        
        if (empty($values)) {
            throw new \InvalidArgumentException("ENUM column '{$name}' cannot have empty values array.");
        }
        
        $this->columns[$name] = [
            'type' => 'ENUM',
            'values' => $values
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function char(string $name, int $length = 1): self
    {
        $this->validateColumnName($name);
        
        if ($length <= 0 || $length > 255) {
            throw new \InvalidArgumentException("CHAR column '{$name}' length must be between 1 and 255, got {$length}.");
        }
        
        $this->columns[$name] = [
            'type' => 'CHAR',
            'length' => $length
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function binary(string $name, ?int $length = null): self
    {
        $this->validateColumnName($name);
        
        $columnDef = ['type' => 'BLOB'];
        
        // If length is specified, use VARBINARY, otherwise BLOB
        if ($length !== null) {
            if ($length <= 0 || $length > 65535) {
                throw new \InvalidArgumentException("BINARY column '{$name}' length must be between 1 and 65535, got {$length}.");
            }
            $columnDef['type'] = 'VARBINARY';
            $columnDef['length'] = $length;
        }
        
        $this->columns[$name] = $columnDef;
        $this->lastColumn = $name;
        return $this;
    }

    public function date(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'DATE'
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function time(string $name, int $precision = 0): self
    {
        $this->validateColumnName($name);
        
        if ($precision < 0 || $precision > 6) {
            throw new \InvalidArgumentException("TIME column '{$name}' precision must be between 0 and 6, got {$precision}.");
        }
        
        $columnDef = ['type' => 'TIME'];
        if ($precision > 0) {
            $columnDef['precision'] = $precision;
        }
        
        $this->columns[$name] = $columnDef;
        $this->lastColumn = $name;
        return $this;
    }

    public function float(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->validateColumnName($name);
        
        if ($precision <= 0 || $precision > 24) {
            throw new \InvalidArgumentException("FLOAT column '{$name}' precision must be between 1 and 24, got {$precision}.");
        }
        
        if ($scale < 0 || $scale > $precision) {
            throw new \InvalidArgumentException("FLOAT column '{$name}' scale must be between 0 and {$precision}, got {$scale}.");
        }
        
        $this->columns[$name] = [
            'type' => 'FLOAT',
            'precision' => $precision,
            'scale' => $scale
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function double(string $name, int $precision = 15, int $scale = 8): self
    {
        $this->validateColumnName($name);
        
        if ($precision <= 0 || $precision > 53) {
            throw new \InvalidArgumentException("DOUBLE column '{$name}' precision must be between 1 and 53, got {$precision}.");
        }
        
        if ($scale < 0 || $scale > $precision) {
            throw new \InvalidArgumentException("DOUBLE column '{$name}' scale must be between 0 and {$precision}, got {$scale}.");
        }
        
        $this->columns[$name] = [
            'type' => 'DOUBLE',
            'precision' => $precision,
            'scale' => $scale
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function uuid(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'CHAR',
            'length' => 36
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function ulid(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'CHAR',
            'length' => 26
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function year(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'YEAR'
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function increments(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true
        ];
        $this->lastColumn = $name;
        $this->primaryKey($name);
        return $this;
    }

    public function bigIncrements(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => true,
            'auto_increment' => true
        ];
        $this->lastColumn = $name;
        $this->primaryKey($name);
        return $this;
    }

    public function mediumInteger(string $name, bool $unsigned = false): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'MEDIUMINT',
            'unsigned' => $unsigned
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function smallInteger(string $name, bool $unsigned = false): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'SMALLINT',
            'unsigned' => $unsigned
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function tinyInteger(string $name, bool $unsigned = false): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'unsigned' => $unsigned
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function ipAddress(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => 45  // IPv6 addresses can be up to 45 characters
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function macAddress(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => 17  // MAC addresses are 17 characters (XX:XX:XX:XX:XX:XX)
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function morphs(string $name): self
    {
        // Create the _id column
        $this->unsignedBigInteger($name . '_id');
        
        // Create the _type column
        $this->string($name . '_type');
        
        // Add index on both columns
        $this->index([$name . '_id', $name . '_type'], $name . '_index');
        
        return $this;
    }

    public function nullableMorphs(string $name): self
    {
        // Create the _id column (nullable)
        $this->unsignedBigInteger($name . '_id')->nullable();
        
        // Create the _type column (nullable)
        $this->string($name . '_type')->nullable();
        
        // Add index on both columns
        $this->index([$name . '_id', $name . '_type'], $name . '_index');
        
        return $this;
    }

    public function unsignedBigInteger(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function unsignedInteger(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'INT',
            'unsigned' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function unsignedMediumInteger(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'MEDIUMINT',
            'unsigned' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function unsignedSmallInteger(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'SMALLINT',
            'unsigned' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function unsignedTinyInteger(string $name): self
    {
        $this->validateColumnName($name);
        
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'unsigned' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    public function rememberToken(): self
    {
        return $this->string('remember_token', 100)->nullable();
    }

    public function softDeletesTz(): self
    {
        return $this->timestamp('deleted_at')->nullable();
    }

    public function nullable(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set nullable(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set nullable(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['nullable'] = true;
        return $this;
    }

    public function default($value): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set default(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set default(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['default'] = $value;
        return $this;
    }

    public function unsigned(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set unsigned(): No column was defined. Call a column method first (e.g., integer(), bigInteger(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set unsigned(): Column '{$this->lastColumn}' not found.");
        }
        
        // Check if column type supports UNSIGNED
        $allowedTypes = ['INT', 'BIGINT', 'MEDIUMINT', 'SMALLINT', 'TINYINT', 'DECIMAL', 'FLOAT', 'DOUBLE'];
        $columnType = $this->columns[$this->lastColumn]['type'];
        
        if (!in_array($columnType, $allowedTypes)) {
            throw new \InvalidArgumentException("unsigned() can only be used with numeric columns, but '{$this->lastColumn}' is {$columnType}.");
        }
        
        $this->columns[$this->lastColumn]['unsigned'] = true;
        return $this;
    }

    public function after(string $column): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set after(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set after(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['after'] = $column;
        return $this;
    }

    public function first(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set first(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set first(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['first'] = true;
        return $this;
    }

    public function comment(string $comment): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set comment(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set comment(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['comment'] = $comment;
        return $this;
    }

    public function columnCharset(string $charset): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set charset(): No column was defined. Call a column method first (e.g., string(), text(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set charset(): Column '{$this->lastColumn}' not found.");
        }
        
        // Check if column type supports charset
        $allowedTypes = ['VARCHAR', 'CHAR', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'TINYTEXT', 'ENUM'];
        $columnType = $this->columns[$this->lastColumn]['type'];
        
        if (!in_array($columnType, $allowedTypes)) {
            throw new \InvalidArgumentException("charset() can only be used with string/text columns, but '{$this->lastColumn}' is {$columnType}.");
        }
        
        $this->columns[$this->lastColumn]['charset'] = $charset;
        return $this;
    }

    public function columnCollation(string $collation): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set collation(): No column was defined. Call a column method first (e.g., string(), text(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set collation(): Column '{$this->lastColumn}' not found.");
        }
        
        // Check if column type supports collation
        $allowedTypes = ['VARCHAR', 'CHAR', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'TINYTEXT', 'ENUM'];
        $columnType = $this->columns[$this->lastColumn]['type'];
        
        if (!in_array($columnType, $allowedTypes)) {
            throw new \InvalidArgumentException("collation() can only be used with string/text columns, but '{$this->lastColumn}' is {$columnType}.");
        }
        
        $this->columns[$this->lastColumn]['collation'] = $collation;
        return $this;
    }

    public function autoIncrement(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set autoIncrement(): No column was defined. Call a column method first (e.g., integer(), bigInteger(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set autoIncrement(): Column '{$this->lastColumn}' not found.");
        }
        
        // Check if column type supports auto increment
        $allowedTypes = ['INT', 'BIGINT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'];
        $columnType = $this->columns[$this->lastColumn]['type'];
        
        if (!in_array($columnType, $allowedTypes)) {
            throw new \InvalidArgumentException("autoIncrement() can only be used with integer columns, but '{$this->lastColumn}' is {$columnType}.");
        }
        
        $this->columns[$this->lastColumn]['auto_increment'] = true;
        return $this;
    }

    public function useCurrent(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set useCurrent(): No column was defined. Call a column method first (e.g., timestamp(), datetime(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set useCurrent(): Column '{$this->lastColumn}' not found.");
        }
        
        // Check if column type supports CURRENT_TIMESTAMP
        $allowedTypes = ['TIMESTAMP', 'DATETIME'];
        $columnType = $this->columns[$this->lastColumn]['type'];
        
        if (!in_array($columnType, $allowedTypes)) {
            throw new \InvalidArgumentException("useCurrent() can only be used with TIMESTAMP or DATETIME columns, but '{$this->lastColumn}' is {$columnType}.");
        }
        
        $this->columns[$this->lastColumn]['default'] = 'CURRENT_TIMESTAMP';
        return $this;
    }

    public function useCurrentOnUpdate(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set useCurrentOnUpdate(): No column was defined. Call a column method first (e.g., timestamp(), datetime(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set useCurrentOnUpdate(): Column '{$this->lastColumn}' not found.");
        }
        
        // Check if column type supports ON UPDATE CURRENT_TIMESTAMP
        $allowedTypes = ['TIMESTAMP', 'DATETIME'];
        $columnType = $this->columns[$this->lastColumn]['type'];
        
        if (!in_array($columnType, $allowedTypes)) {
            throw new \InvalidArgumentException("useCurrentOnUpdate() can only be used with TIMESTAMP or DATETIME columns, but '{$this->lastColumn}' is {$columnType}.");
        }
        
        $this->columns[$this->lastColumn]['on_update'] = true;
        return $this;
    }

    public function invisible(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set invisible(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set invisible(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['invisible'] = true;
        return $this;
    }

    /**
     * Make the last defined column unique (single-column unique constraint)
     */
    public function unique(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set unique(): No column was defined. Call a column method first (e.g., string(), integer(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set unique(): Column '{$this->lastColumn}' not found.");
        }
        
        $this->columns[$this->lastColumn]['unique'] = true;
        return $this;
    }

    public function primaryKey(string|array $columns): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        
        // Validate that all columns exist
        foreach ($columns as $column) {
            if (!isset($this->columns[$column])) {
                throw new \InvalidArgumentException("Cannot create primary key: Column '{$column}' does not exist. Define the column first.");
            }
        }
        
        $this->primaryKey = $columns;
        return $this;
    }

    /**
     * Create a unique constraint on one or more columns (table-level unique constraint)
     */
    public function uniqueIndex(string|array $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        
        // Validate that all columns exist
        foreach ($columns as $column) {
            if (!isset($this->columns[$column])) {
                throw new \InvalidArgumentException("Cannot create unique index: Column '{$column}' does not exist. Define the column first.");
            }
        }
        
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
        
        // Validate that all columns exist
        foreach ($columns as $column) {
            if (!isset($this->columns[$column])) {
                throw new \InvalidArgumentException("Cannot create index: Column '{$column}' does not exist. Define the column first.");
            }
        }
        
        $name = $name ?? implode('_', $columns) . '_index';
        $this->indexes[$name] = [
            'columns' => $columns,
            'type' => 'INDEX'
        ];
        return $this;
    }

    public function foreignKey(string $column, string $referenceTable, string $referenceColumn): self
    {
        // Validate that the column exists
        if (!isset($this->columns[$column])) {
            throw new \InvalidArgumentException("Cannot create foreign key: Column '{$column}' does not exist. Define the column first.");
        }
        
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

    /**
     * Set table creation to use IF NOT EXISTS
     */
    public function ifNotExists(): self
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
     * Set table creation to be strict (fail if exists) - this is the default
     */
    public function strict(): self
    {
        $this->ifNotExists = false;
        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at', true)->nullable();
        return $this;
    }

    public function softDeletes(): self
    {
        $this->timestamp('deleted_at')->nullable();
        return $this;
    }

    public function createTable(string $tableName): bool
    {
        // Validate table name
        if (empty($tableName)) {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }
        
        if (strlen($tableName) > 64) {
            throw new \InvalidArgumentException("Table name '{$tableName}' is too long. Maximum length is 64 characters.");
        }
        
        // Validate that we have at least one column
        if (empty($this->columns)) {
            throw new \InvalidArgumentException("Cannot create table '{$tableName}': No columns defined. Add at least one column.");
        }
        
        // Escape table name to prevent SQL injection
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
        $this->ifNotExists = false;
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
        
        // Add CHARACTER SET
        if (isset($column['charset'])) {
            $def .= " CHARACTER SET {$column['charset']}";
        }
        
        // Add COLLATE
        if (isset($column['collation'])) {
            $def .= " COLLATE {$column['collation']}";
        }
        
        // Handle NULL/NOT NULL - PRIMARY KEY columns must always be NOT NULL
        $isPrimaryKey = in_array($name, $this->primaryKey);
        if ($isPrimaryKey || (!isset($column['nullable']) || !$column['nullable'])) {
            $def .= " NOT NULL";
        } else {
            $def .= " NULL";
        }
        
        // Handle default values with proper escaping
        if (isset($column['default'])) {
            $default = $column['default'];
            if (is_bool($default)) {
                // Convert boolean to integer for MySQL
                $def .= " DEFAULT " . ($default ? '1' : '0');
            } elseif (is_string($default) && !in_array(strtoupper($default), ['NULL', 'CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'])) {
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
        
        // Add INVISIBLE modifier (MySQL 8.0+)
        if (isset($column['invisible']) && $column['invisible']) {
            $def .= " INVISIBLE";
        }
        
        // Add COMMENT
        if (isset($column['comment'])) {
            $escapedComment = str_replace("'", "''", $column['comment']);
            $def .= " COMMENT '{$escapedComment}'";
        }
        
        // Add UNIQUE modifier (single-column unique constraint)
        if (isset($column['unique']) && $column['unique']) {
            $def .= " UNIQUE";
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
            // Use information_schema for more reliable table checking
            $sql = "SELECT COUNT(*) as count FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
            $result = $this->db->query($sql, [$tableName]);
            
            // Fetch the result to get the actual count
            $row = $result->fetch('assoc');
            return $row && $row['count'] > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Debug method to check table existence with detailed information
     */
    public function debugHasTable(string $tableName): array
    {
        $debug = [
            'table_name' => $tableName,
            'methods' => []
        ];

        try {
            // Method 1: SHOW TABLES (using string concatenation for debug)
            $sql1 = "SHOW TABLES LIKE '{$tableName}'";
            $result1 = $this->db->query($sql1);
            $debug['methods']['show_tables'] = [
                'sql' => $sql1,
                'num_rows' => $result1->numRows(),
                'has_rows' => $result1->numRows() > 0
            ];

            // Method 2: information_schema (using string concatenation for debug)
            $sql2 = "SELECT COUNT(*) as count FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}'";
            $result2 = $this->db->query($sql2);
            $row = $result2->fetch('assoc');
            $debug['methods']['information_schema'] = [
                'sql' => $sql2,
                'row' => $row,
                'count' => $row ? $row['count'] : 0,
                'has_table' => $row && $row['count'] > 0
            ];

            // Method 3: DESCRIBE (will throw if table doesn't exist)
            try {
                $sql3 = "DESCRIBE `{$tableName}`";
                $this->db->query($sql3);
                $debug['methods']['describe'] = [
                    'sql' => $sql3,
                    'success' => true,
                    'has_table' => true
                ];
            } catch (\Exception $e) {
                $debug['methods']['describe'] = [
                    'sql' => $sql3,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'has_table' => false
                ];
            }

        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
        }

        return $debug;
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
            
            // Handle positioning modifiers (MySQL specific)
            if (isset($definition['first']) && $definition['first']) {
                $sql .= " FIRST";
            } elseif (isset($definition['after'])) {
                $escapedAfter = "`{$definition['after']}`";
                $sql .= " AFTER {$escapedAfter}";
            }
            
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
            
            // Handle positioning modifiers (MySQL specific)
            if (isset($definition['first']) && $definition['first']) {
                $sql .= " FIRST";
            } elseif (isset($definition['after'])) {
                $escapedAfter = "`{$definition['after']}`";
                $sql .= " AFTER {$escapedAfter}";
            }
            
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Failed to modify column: " . $e->getMessage());
        }
    }

    public function onUpdate(): self
    {
        if ($this->lastColumn === null) {
            throw new \InvalidArgumentException('Cannot set onUpdate(): No column was defined. Call a column method first (e.g., timestamp(), datetime(), etc.).');
        }
        
        if (!isset($this->columns[$this->lastColumn])) {
            throw new \InvalidArgumentException("Cannot set onUpdate(): Column '{$this->lastColumn}' not found.");
        }
        
        // Validate that onUpdate is only used with TIMESTAMP columns
        if ($this->columns[$this->lastColumn]['type'] !== 'TIMESTAMP') {
            throw new \InvalidArgumentException("onUpdate() can only be used with TIMESTAMP columns, but '{$this->lastColumn}' is {$this->columns[$this->lastColumn]['type']}.");
        }
        
        $this->columns[$this->lastColumn]['on_update'] = true;
        return $this;
    }

    public function table(string $tableName): TableAlter
    {
        return new TableAlter($this->db, $this, $tableName);
    }
    
    /**
     * Generate CREATE TABLE SQL without executing it
     */
    public function generateCreateTableSql(string $tableName): string
    {
        // Escape table name to prevent SQL injection
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
        
        return $sql;
    }

    /**
     * Check if index exists on a table
     */
    public function hasIndex(string $tableName, string $indexName): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $escapedIndexName = "`{$indexName}`";
            $sql = "SHOW INDEX FROM {$escapedTableName} WHERE Key_name = ?";
            $result = $this->db->query($sql, [$indexName]);
            return $result->numRows() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if index exists by columns (finds index with matching columns)
     */
    public function hasIndexByColumns(string $tableName, array $columns): bool
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $sql = "SHOW INDEX FROM {$escapedTableName}";
            $result = $this->db->query($sql);
            
            $existingIndexes = [];
            while ($row = $result->fetch('assoc')) {
                $indexName = $row['Key_name'];
                if (!isset($existingIndexes[$indexName])) {
                    $existingIndexes[$indexName] = [];
                }
                $existingIndexes[$indexName][] = $row['Column_name'];
            }
            
            // Sort columns for comparison
            sort($columns);
            
            foreach ($existingIndexes as $indexColumns) {
                sort($indexColumns);
                if ($columns === $indexColumns) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all indexes for a table
     */
    public function getIndexes(string $tableName): array
    {
        try {
            $escapedTableName = "`{$tableName}`";
            $sql = "SHOW INDEX FROM {$escapedTableName}";
            $result = $this->db->query($sql);
            
            $indexes = [];
            while ($row = $result->fetch('assoc')) {
                $indexName = $row['Key_name'];
                if (!isset($indexes[$indexName])) {
                    $indexes[$indexName] = [
                        'name' => $indexName,
                        'type' => $row['Index_type'],
                        'unique' => $row['Non_unique'] == 0,
                        'primary' => $indexName === 'PRIMARY',
                        'columns' => []
                    ];
                }
                $indexes[$indexName]['columns'][] = $row['Column_name'];
            }
            
            return array_values($indexes);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Add index only if it doesn't exist (idempotent)
     */
    public function addIndexIfNotExists(string $tableName, array $columns, ?string $name = null, bool $unique = false): bool
    {
        $name = $name ?? implode('_', $columns) . ($unique ? '_unique' : '_index');
        
        // Check if index already exists
        if ($this->hasIndex($tableName, $name)) {
            return true; // Index already exists, no action needed
        }
        
        // Check if index exists by columns
        if ($this->hasIndexByColumns($tableName, $columns)) {
            return true; // Index with same columns already exists
        }
        
        // Add the index
        try {
            $alter = $this->table($tableName);
            $alter->addIndex($columns, $name, $unique);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add unique index only if it doesn't exist (idempotent)
     */
    public function addUniqueIndexIfNotExists(string $tableName, array $columns, ?string $name = null): bool
    {
        return $this->addIndexIfNotExists($tableName, $columns, $name, true);
    }

    /**
     * Add foreign key only if it doesn't exist (idempotent)
     */
    public function addForeignKeyIfNotExists(
        string $tableName, 
        string $column, 
        string $referenceTable, 
        string $referenceColumn, 
        ?string $name = null,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): bool {
        $name = $name ?? "fk_{$tableName}_{$column}";
        
        // Check if foreign key already exists
        try {
            $sql = "SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ? 
                    AND REFERENCED_TABLE_NAME = ? 
                    AND REFERENCED_COLUMN_NAME = ?";
            $result = $this->db->query($sql, [$tableName, $column, $referenceTable, $referenceColumn]);
            
            if ($result->numRows() > 0) {
                return true; // Foreign key already exists
            }
        } catch (\Exception $e) {
            // Continue to create the foreign key
        }
        
        // Add the foreign key
        try {
            $alter = $this->table($tableName);
            $alter->addForeignKey($column, $referenceTable, $referenceColumn, $name, $onDelete, $onUpdate);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add column only if it doesn't exist (idempotent)
     */
    public function addColumnIfNotExists(string $tableName, string $columnName, array $definition): bool
    {
        if ($this->hasColumn($tableName, $columnName)) {
            return true; // Column already exists
        }
        
        return $this->addColumn($tableName, $columnName, $definition);
    }

    /**
     * Create table only if it doesn't exist (idempotent)
     */
    public function createTableIfNotExists(string $tableName, callable $callback): bool
    {
        if ($this->hasTable($tableName)) {
            return true; // Table already exists
        }
        
        // Create a fresh SchemaBuilder instance for each table to avoid state conflicts
        $tableBuilder = new SchemaBuilder($this->db);
        $tableBuilder->ifNotExists(); // Set the flag before callback
        $callback($tableBuilder);
        return $tableBuilder->createTable($tableName);
    }
} 