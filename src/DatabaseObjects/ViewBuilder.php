<?php

namespace SimpleMDB\DatabaseObjects;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Fluent interface for creating and managing database views
 */
class ViewBuilder
{
    private DatabaseInterface $db;
    private string $viewName;
    private string $selectQuery = '';
    private array $columns = [];
    private string $algorithm = '';
    private string $security = 'DEFINER';
    private string $definer = '';
    private string $comment = '';
    private bool $ifNotExists = false;
    private bool $replace = false;

    public function __construct(DatabaseInterface $db, string $viewName)
    {
        $this->db = $db;
        $this->viewName = $viewName;
    }

    /**
     * Set the SELECT query for the view
     */
    public function select(string $query): self
    {
        $this->selectQuery = $query;
        return $this;
    }

    /**
     * Set specific columns for the view
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set view algorithm
     */
    public function algorithm(string $algorithm): self
    {
        $this->algorithm = strtoupper($algorithm);
        return $this;
    }

    public function undefined(): self
    {
        $this->algorithm = 'UNDEFINED';
        return $this;
    }

    public function merge(): self
    {
        $this->algorithm = 'MERGE';
        return $this;
    }

    public function temptable(): self
    {
        $this->algorithm = 'TEMPTABLE';
        return $this;
    }

    /**
     * Set security context
     */
    public function definer(string $definer = ''): self
    {
        $this->security = 'DEFINER';
        $this->definer = $definer;
        return $this;
    }

    public function invoker(): self
    {
        $this->security = 'INVOKER';
        return $this;
    }

    /**
     * Add a comment
     */
    public function comment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Use IF NOT EXISTS
     */
    public function ifNotExists(): self
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
     * Use OR REPLACE
     */
    public function orReplace(): self
    {
        $this->replace = true;
        return $this;
    }

    /**
     * Create the view
     */
    public function create(): bool
    {
        $sql = $this->generateCreateViewSql();
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to create view '{$this->viewName}': " . $e->getMessage());
        }
    }

    /**
     * Generate the CREATE VIEW SQL
     */
    public function generateCreateViewSql(): string
    {
        if (empty($this->selectQuery)) {
            throw new SchemaException("SELECT query is required for view '{$this->viewName}'");
        }

        $sql = "CREATE ";
        
        if ($this->replace) {
            $sql .= "OR REPLACE ";
        }
        
        $sql .= "VIEW ";
        
        if ($this->ifNotExists && !$this->replace) {
            $sql .= "IF NOT EXISTS ";
        }
        
        $sql .= "`{$this->viewName}`";
        
        // Add columns if specified
        if (!empty($this->columns)) {
            $sql .= " (" . implode(', ', array_map(fn($col) => "`{$col}`", $this->columns)) . ")";
        }
        
        $sql .= " AS ";
        
        if (!empty($this->algorithm)) {
            $sql .= "ALGORITHM = {$this->algorithm} ";
        }
        
        if ($this->security === 'DEFINER' && !empty($this->definer)) {
            $sql .= "DEFINER = {$this->definer} ";
        } else {
            $sql .= "{$this->security} ";
        }
        
        if (!empty($this->comment)) {
            $sql .= "COMMENT '{$this->comment}' ";
        }
        
        $sql .= "{$this->selectQuery}";
        
        return $sql;
    }

    /**
     * Drop the view
     */
    public function drop(): bool
    {
        $sql = "DROP VIEW IF EXISTS `{$this->viewName}`";
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to drop view '{$this->viewName}': " . $e->getMessage());
        }
    }

    /**
     * Check if view exists
     */
    public function exists(): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.views 
                WHERE table_name = ? 
                AND table_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->viewName]);
            return $result->fetchColumn(0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get view definition
     */
    public function getDefinition(): ?string
    {
        $sql = "SELECT VIEW_DEFINITION FROM information_schema.views 
                WHERE table_name = ? 
                AND table_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->viewName]);
            return $result->fetchColumn(0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update the view (drop and recreate)
     */
    public function update(): bool
    {
        if ($this->exists()) {
            $this->drop();
        }
        return $this->create();
    }
} 