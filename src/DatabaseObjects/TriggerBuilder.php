<?php

namespace SimpleMDB\DatabaseObjects;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Fluent interface for creating and managing database triggers
 */
class TriggerBuilder
{
    private DatabaseInterface $db;
    private string $triggerName;
    private string $timing = '';
    private string $event = '';
    private string $table = '';
    private string $body = '';
    private string $definer = '';
    private string $comment = '';
    private bool $ifNotExists = false;
    private bool $replace = false;

    public function __construct(DatabaseInterface $db, string $triggerName)
    {
        $this->db = $db;
        $this->triggerName = $triggerName;
    }

    /**
     * Set trigger timing
     */
    public function before(): self
    {
        $this->timing = 'BEFORE';
        return $this;
    }

    public function after(): self
    {
        $this->timing = 'AFTER';
        return $this;
    }

    /**
     * Set trigger event
     */
    public function insert(): self
    {
        $this->event = 'INSERT';
        return $this;
    }

    public function update(): self
    {
        $this->event = 'UPDATE';
        return $this;
    }

    public function delete(): self
    {
        $this->event = 'DELETE';
        return $this;
    }

    /**
     * Set the table name
     */
    public function on(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the trigger body
     */
    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set the definer
     */
    public function definer(string $definer): self
    {
        $this->definer = $definer;
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
     * Create the trigger
     */
    public function create(): bool
    {
        $sql = $this->generateCreateTriggerSql();
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to create trigger '{$this->triggerName}': " . $e->getMessage());
        }
    }

    /**
     * Generate the CREATE TRIGGER SQL
     */
    public function generateCreateTriggerSql(): string
    {
        if (empty($this->timing)) {
            throw new SchemaException("Timing (BEFORE/AFTER) is required for trigger '{$this->triggerName}'");
        }

        if (empty($this->event)) {
            throw new SchemaException("Event (INSERT/UPDATE/DELETE) is required for trigger '{$this->triggerName}'");
        }

        if (empty($this->table)) {
            throw new SchemaException("Table name is required for trigger '{$this->triggerName}'");
        }

        if (empty($this->body)) {
            throw new SchemaException("Trigger body is required for trigger '{$this->triggerName}'");
        }

        $sql = "CREATE ";
        
        if ($this->replace) {
            $sql .= "OR REPLACE ";
        }
        
        $sql .= "TRIGGER ";
        
        if ($this->ifNotExists && !$this->replace) {
            $sql .= "IF NOT EXISTS ";
        }
        
        $sql .= "`{$this->triggerName}` ";
        $sql .= "{$this->timing} {$this->event} ";
        $sql .= "ON `{$this->table}` ";
        $sql .= "FOR EACH ROW ";
        
        if (!empty($this->definer)) {
            $sql .= "DEFINER = {$this->definer} ";
        }
        
        if (!empty($this->comment)) {
            $sql .= "COMMENT '{$this->comment}' ";
        }
        
        $sql .= "{$this->body}";
        
        return $sql;
    }

    /**
     * Drop the trigger
     */
    public function drop(): bool
    {
        $sql = "DROP TRIGGER IF EXISTS `{$this->triggerName}`";
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to drop trigger '{$this->triggerName}': " . $e->getMessage());
        }
    }

    /**
     * Check if trigger exists
     */
    public function exists(): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.triggers 
                WHERE trigger_name = ? 
                AND trigger_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->triggerName]);
            return $result->fetchColumn(0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get trigger information
     */
    public function getInfo(): ?array
    {
        $sql = "SELECT * FROM information_schema.triggers 
                WHERE trigger_name = ? 
                AND trigger_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->triggerName]);
            return $result->fetch('assoc');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all triggers for a table
     */
    public static function getTableTriggers(DatabaseInterface $db, string $tableName): array
    {
        $sql = "SELECT * FROM information_schema.triggers 
                WHERE event_object_table = ? 
                AND trigger_schema = DATABASE()
                ORDER BY action_timing, action_order";
        
        try {
            $result = $db->query($sql, [$tableName]);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }
} 