<?php

namespace SimpleMDB\DatabaseObjects;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Fluent interface for creating and managing database events
 */
class EventBuilder
{
    private DatabaseInterface $db;
    private string $eventName;
    private string $schedule = '';
    private string $body = '';
    private string $status = 'ENABLE';
    private string $comment = '';
    private bool $ifNotExists = false;
    private bool $replace = false;

    public function __construct(DatabaseInterface $db, string $eventName)
    {
        $this->db = $db;
        $this->eventName = $eventName;
    }

    /**
     * Set the event schedule
     */
    public function schedule(string $schedule): self
    {
        $this->schedule = $schedule;
        return $this;
    }

    /**
     * Set one-time schedule
     */
    public function at(string $datetime): self
    {
        $this->schedule = "AT '{$datetime}'";
        return $this;
    }

    /**
     * Set recurring schedule
     */
    public function every(string $interval): self
    {
        $this->schedule = "EVERY {$interval}";
        return $this;
    }

    /**
     * Set recurring schedule with start time
     */
    public function everyStarting(string $interval, string $startTime): self
    {
        $this->schedule = "EVERY {$interval} STARTS '{$startTime}'";
        return $this;
    }

    /**
     * Set recurring schedule with end time
     */
    public function everyEnding(string $interval, string $endTime): self
    {
        $this->schedule = "EVERY {$interval} ENDS '{$endTime}'";
        return $this;
    }

    /**
     * Set recurring schedule with both start and end times
     */
    public function everyBetween(string $interval, string $startTime, string $endTime): self
    {
        $this->schedule = "EVERY {$interval} STARTS '{$startTime}' ENDS '{$endTime}'";
        return $this;
    }

    /**
     * Set the event body
     */
    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Enable the event
     */
    public function enable(): self
    {
        $this->status = 'ENABLE';
        return $this;
    }

    /**
     * Disable the event
     */
    public function disable(): self
    {
        $this->status = 'DISABLE';
        return $this;
    }

    /**
     * Disable on slave
     */
    public function disableOnSlave(): self
    {
        $this->status = 'DISABLE ON SLAVE';
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
     * Create the event
     */
    public function create(): bool
    {
        $sql = $this->generateCreateEventSql();
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to create event '{$this->eventName}': " . $e->getMessage());
        }
    }

    /**
     * Generate the CREATE EVENT SQL
     */
    public function generateCreateEventSql(): string
    {
        if (empty($this->schedule)) {
            throw new SchemaException("Schedule is required for event '{$this->eventName}'");
        }

        if (empty($this->body)) {
            throw new SchemaException("Event body is required for event '{$this->eventName}'");
        }

        $sql = "CREATE ";
        
        if ($this->replace) {
            $sql .= "OR REPLACE ";
        }
        
        $sql .= "EVENT ";
        
        if ($this->ifNotExists && !$this->replace) {
            $sql .= "IF NOT EXISTS ";
        }
        
        $sql .= "`{$this->eventName}` ";
        $sql .= "ON SCHEDULE {$this->schedule} ";
        $sql .= "{$this->status} ";
        
        if (!empty($this->comment)) {
            $sql .= "COMMENT '{$this->comment}' ";
        }
        
        $sql .= "DO {$this->body}";
        
        return $sql;
    }

    /**
     * Drop the event
     */
    public function drop(): bool
    {
        $sql = "DROP EVENT IF EXISTS `{$this->eventName}`";
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to drop event '{$this->eventName}': " . $e->getMessage());
        }
    }

    /**
     * Check if event exists
     */
    public function exists(): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.events 
                WHERE event_name = ? 
                AND event_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->eventName]);
            return $result->fetchColumn(0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Alter the event status
     */
    public function alterStatus(string $status): bool
    {
        $sql = "ALTER EVENT `{$this->eventName}` {$status}";
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to alter event '{$this->eventName}': " . $e->getMessage());
        }
    }

    /**
     * Enable the event
     */
    public function alterEnable(): bool
    {
        return $this->alterStatus('ENABLE');
    }

    /**
     * Disable the event
     */
    public function alterDisable(): bool
    {
        return $this->alterStatus('DISABLE');
    }

    /**
     * Get event information
     */
    public function getInfo(): ?array
    {
        $sql = "SELECT * FROM information_schema.events 
                WHERE event_name = ? 
                AND event_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->eventName]);
            return $result->fetch('assoc');
        } catch (\Exception $e) {
            return null;
        }
    }
} 