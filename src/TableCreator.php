<?php

namespace SimpleMDB;

/**
 * Fluent table creation interface for more expressive migrations
 */
class TableCreator
{
    private DatabaseInterface $db;
    private string $tableName;
    private bool $ifNotExists = false;

    public function __construct(DatabaseInterface $db, string $tableName)
    {
        $this->db = $db;
        $this->tableName = $tableName;
    }

    /**
     * Set table creation to use IF NOT EXISTS (safe mode)
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

    /**
     * Alias for ifNotExists() - more expressive
     */
    public function safely(): self
    {
        return $this->ifNotExists();
    }

    /**
     * Only create if the table doesn't already exist
     */
    public function onlyIfMissing(): self
    {
        return $this->ifNotExists();
    }

    /**
     * Create the table with the given definition
     */
    public function create(callable $callback): bool
    {
        $tableBuilder = new SchemaBuilder($this->db);
        
        if ($this->ifNotExists) {
            $tableBuilder->ifNotExists();
        }
        
        $callback($tableBuilder);
        return $tableBuilder->createTable($this->tableName);
    }

    /**
     * Generate SQL without executing
     */
    public function toSql(callable $callback): string
    {
        $tableBuilder = new SchemaBuilder($this->db);
        
        if ($this->ifNotExists) {
            $tableBuilder->ifNotExists();
        }
        
        $callback($tableBuilder);
        return $tableBuilder->generateCreateTableSql($this->tableName);
    }
} 