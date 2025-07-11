<?php

namespace SimpleMDB;

class TableAlter
{
    private DatabaseInterface $db;
    private string $table;
    private SchemaBuilder $builder;

    public function __construct(DatabaseInterface $db, SchemaBuilder $builder, string $table)
    {
        $this->db = $db;
        $this->builder = $builder;
        $this->table = $table;
    }

    public function addColumn(string $name, array $definition): self
    {
        $escapedTable = "`{$this->table}`";
        $sql = "ALTER TABLE {$escapedTable} ADD COLUMN " . $this->builder->buildColumnDefinition($name, $definition);
        $this->db->query($sql);
        return $this;
    }

    public function dropColumn(string $name): self
    {
        $escapedTable = "`{$this->table}`";
        $escapedColumn = "`{$name}`";
        $sql = "ALTER TABLE {$escapedTable} DROP COLUMN {$escapedColumn}";
        $this->db->query($sql);
        return $this;
    }

    public function modifyColumn(string $name, array $definition): self
    {
        $escapedTable = "`{$this->table}`";
        $sql = "ALTER TABLE {$escapedTable} MODIFY COLUMN " . $this->builder->buildColumnDefinition($name, $definition);
        $this->db->query($sql);
        return $this;
    }

    public function addIndex(array|string $columns, ?string $name = null, bool $unique = false): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? implode('_', $columns) . ($unique ? '_unique' : '_index');
        $type = $unique ? 'UNIQUE' : 'INDEX';
        $escapedTable = "`{$this->table}`";
        $escapedName = "`{$name}`";
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        $sql = "ALTER TABLE {$escapedTable} ADD {$type} {$escapedName} (" . implode(', ', $escapedColumns) . ")";
        $this->db->query($sql);
        return $this;
    }

    public function dropIndex(string $name): self
    {
        $escapedTable = "`{$this->table}`";
        $escapedName = "`{$name}`";
        $sql = "ALTER TABLE {$escapedTable} DROP INDEX {$escapedName}";
        $this->db->query($sql);
        return $this;
    }

    public function renameColumn(string $oldName, string $newName, array $definition): self
    {
        // MySQL syntax: CHANGE old_name new_name definition
        $escapedTable = "`{$this->table}`";
        $escapedOldName = "`{$oldName}`";
        $sql = "ALTER TABLE {$escapedTable} CHANGE {$escapedOldName} " . $this->builder->buildColumnDefinition($newName, $definition);
        $this->db->query($sql);
        return $this;
    }

    public function addPrimaryKey(array|string $columns, ?string $name = null): self
    {
        $columnsArr = is_array($columns) ? $columns : [$columns];
        $escapedTable = "`{$this->table}`";
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columnsArr);
        $constraint = $name ? "CONSTRAINT `{$name}` " : '';
        $sql = "ALTER TABLE {$escapedTable} ADD {$constraint}PRIMARY KEY (" . implode(', ', $escapedColumns) . ")";
        $this->db->query($sql);
        return $this;
    }

    public function dropPrimaryKey(): self
    {
        $escapedTable = "`{$this->table}`";
        $sql = "ALTER TABLE {$escapedTable} DROP PRIMARY KEY";
        $this->db->query($sql);
        return $this;
    }

    public function addForeignKey(string $column, string $referenceTable, string $referenceColumn, ?string $name = null, ?string $onDelete = null, ?string $onUpdate = null): self
    {
        $escapedTable = "`{$this->table}`";
        $escapedColumn = "`{$column}`";
        $escapedRefTable = "`{$referenceTable}`";
        $escapedRefColumn = "`{$referenceColumn}`";
        $constraint = $name ? "CONSTRAINT `{$name}` " : '';
        $sql = "ALTER TABLE {$escapedTable} ADD {$constraint}FOREIGN KEY ({$escapedColumn}) REFERENCES {$escapedRefTable}({$escapedRefColumn})";
        if ($onDelete) {
            $sql .= " ON DELETE {$onDelete}";
        }
        if ($onUpdate) {
            $sql .= " ON UPDATE {$onUpdate}";
        }
        $this->db->query($sql);
        return $this;
    }

    public function dropForeignKey(string $name): self
    {
        // For MySQL you must drop index when dropping FK if automatically created; here we drop constraint only.
        $escapedTable = "`{$this->table}`";
        $escapedName = "`{$name}`";
        $sql = "ALTER TABLE {$escapedTable} DROP FOREIGN KEY {$escapedName}";
        $this->db->query($sql);
        return $this;
    }

    public function renameTable(string $newName): self
    {
        $escapedOldTable = "`{$this->table}`";
        $escapedNewTable = "`{$newName}`";
        $sql = "RENAME TABLE {$escapedOldTable} TO {$escapedNewTable}";
        $this->db->query($sql);
        $this->table = $newName;
        return $this;
    }

    public function setEngine(string $engine): self
    {
        $escapedTable = "`{$this->table}`";
        $sql = "ALTER TABLE {$escapedTable} ENGINE = {$engine}";
        $this->db->query($sql);
        return $this;
    }

    public function setCharset(string $charset): self
    {
        $escapedTable = "`{$this->table}`";
        $sql = "ALTER TABLE {$escapedTable} DEFAULT CHARSET = {$charset}";
        $this->db->query($sql);
        return $this;
    }

    public function setCollation(string $collation): self
    {
        $escapedTable = "`{$this->table}`";
        $sql = "ALTER TABLE {$escapedTable} COLLATE = {$collation}";
        $this->db->query($sql);
        return $this;
    }
} 