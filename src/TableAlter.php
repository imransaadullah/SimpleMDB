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
        $sql = "ALTER TABLE {$this->table} ADD COLUMN " . $this->builder->buildColumnDefinition($name, $definition);
        $this->db->query($sql);
        return $this;
    }

    public function dropColumn(string $name): self
    {
        $sql = "ALTER TABLE {$this->table} DROP COLUMN {$name}";
        $this->db->query($sql);
        return $this;
    }

    public function modifyColumn(string $name, array $definition): self
    {
        $sql = "ALTER TABLE {$this->table} MODIFY COLUMN " . $this->builder->buildColumnDefinition($name, $definition);
        $this->db->query($sql);
        return $this;
    }

    public function addIndex(array|string $columns, ?string $name = null, bool $unique = false): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? implode('_', $columns) . ($unique ? '_unique' : '_index');
        $type = $unique ? 'UNIQUE' : 'INDEX';
        $sql = "ALTER TABLE {$this->table} ADD {$type} {$name} (" . implode(', ', $columns) . ")";
        $this->db->query($sql);
        return $this;
    }

    public function dropIndex(string $name): self
    {
        $sql = "ALTER TABLE {$this->table} DROP INDEX {$name}";
        $this->db->query($sql);
        return $this;
    }

    public function renameColumn(string $oldName, string $newName, array $definition): self
    {
        // MySQL syntax: CHANGE old_name new_name definition
        $sql = "ALTER TABLE {$this->table} CHANGE {$oldName} {$newName} " . $this->builder->buildColumnDefinition($newName, $definition);
        $this->db->query($sql);
        return $this;
    }

    public function addPrimaryKey(array|string $columns, ?string $name = null): self
    {
        $columnsArr = is_array($columns) ? $columns : [$columns];
        $name = $name ? "CONSTRAINT {$name} " : '';
        $sql = "ALTER TABLE {$this->table} ADD {$name}PRIMARY KEY (" . implode(', ', $columnsArr) . ")";
        $this->db->query($sql);
        return $this;
    }

    public function dropPrimaryKey(): self
    {
        $sql = "ALTER TABLE {$this->table} DROP PRIMARY KEY";
        $this->db->query($sql);
        return $this;
    }

    public function addForeignKey(string $column, string $referenceTable, string $referenceColumn, ?string $name = null, ?string $onDelete = null, ?string $onUpdate = null): self
    {
        $constraint = $name ? "CONSTRAINT {$name} " : '';
        $sql = "ALTER TABLE {$this->table} ADD {$constraint}FOREIGN KEY ({$column}) REFERENCES {$referenceTable}({$referenceColumn})";
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
        $sql = "ALTER TABLE {$this->table} DROP FOREIGN KEY {$name}";
        $this->db->query($sql);
        return $this;
    }

    public function renameTable(string $newName): self
    {
        $sql = "RENAME TABLE {$this->table} TO {$newName}";
        $this->db->query($sql);
        $this->table = $newName;
        return $this;
    }

    public function setEngine(string $engine): self
    {
        $sql = "ALTER TABLE {$this->table} ENGINE = {$engine}";
        $this->db->query($sql);
        return $this;
    }

    public function setCharset(string $charset): self
    {
        $sql = "ALTER TABLE {$this->table} DEFAULT CHARSET = {$charset}";
        $this->db->query($sql);
        return $this;
    }

    public function setCollation(string $collation): self
    {
        $sql = "ALTER TABLE {$this->table} COLLATE = {$collation}";
        $this->db->query($sql);
        return $this;
    }
} 