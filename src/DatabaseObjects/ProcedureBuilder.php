<?php

namespace SimpleMDB\DatabaseObjects;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Fluent interface for creating and managing database procedures
 */
class ProcedureBuilder
{
    private DatabaseInterface $db;
    private string $procedureName;
    private array $parameters = [];
    private string $body = '';
    private string $characteristic = 'DETERMINISTIC';
    private string $sqlDataAccess = 'CONTAINS SQL';
    private string $security = 'DEFINER';
    private string $definer = '';
    private string $comment = '';
    private bool $ifNotExists = false;

    public function __construct(DatabaseInterface $db, string $procedureName)
    {
        $this->db = $db;
        $this->procedureName = $procedureName;
    }

    /**
     * Add a parameter to the procedure
     */
    public function parameter(string $name, string $type, string $direction = 'IN'): self
    {
        $this->parameters[] = [
            'name' => $name,
            'type' => $type,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * Add an IN parameter (default)
     */
    public function inParameter(string $name, string $type): self
    {
        return $this->parameter($name, $type, 'IN');
    }

    /**
     * Add an OUT parameter
     */
    public function outParameter(string $name, string $type): self
    {
        return $this->parameter($name, $type, 'OUT');
    }

    /**
     * Add an INOUT parameter
     */
    public function inoutParameter(string $name, string $type): self
    {
        return $this->parameter($name, $type, 'INOUT');
    }

    /**
     * Set the procedure body
     */
    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set procedure characteristics
     */
    public function deterministic(): self
    {
        $this->characteristic = 'DETERMINISTIC';
        return $this;
    }

    public function notDeterministic(): self
    {
        $this->characteristic = 'NOT DETERMINISTIC';
        return $this;
    }

    /**
     * Set SQL data access
     */
    public function containsSql(): self
    {
        $this->sqlDataAccess = 'CONTAINS SQL';
        return $this;
    }

    public function noSql(): self
    {
        $this->sqlDataAccess = 'NO SQL';
        return $this;
    }

    public function readsSqlData(): self
    {
        $this->sqlDataAccess = 'READS SQL DATA';
        return $this;
    }

    public function modifiesSqlData(): self
    {
        $this->sqlDataAccess = 'MODIFIES SQL DATA';
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
     * Create the procedure
     */
    public function create(): bool
    {
        $sql = $this->generateCreateProcedureSql();
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to create procedure '{$this->procedureName}': " . $e->getMessage());
        }
    }

    /**
     * Generate the CREATE PROCEDURE SQL
     */
    public function generateCreateProcedureSql(): string
    {
        if (empty($this->body)) {
            throw new SchemaException("Procedure body is required for procedure '{$this->procedureName}'");
        }

        $sql = "CREATE PROCEDURE ";
        
        if ($this->ifNotExists) {
            $sql .= "IF NOT EXISTS ";
        }
        
        $sql .= "`{$this->procedureName}`(";
        
        // Add parameters
        $paramStrings = [];
        foreach ($this->parameters as $param) {
            $paramStrings[] = "{$param['direction']} `{$param['name']}` {$param['type']}";
        }
        $sql .= implode(', ', $paramStrings);
        
        $sql .= ") ";
        $sql .= "{$this->characteristic} ";
        $sql .= "{$this->sqlDataAccess} ";
        
        if ($this->security === 'DEFINER' && !empty($this->definer)) {
            $sql .= "DEFINER = {$this->definer} ";
        } else {
            $sql .= "{$this->security} ";
        }
        
        if (!empty($this->comment)) {
            $sql .= "COMMENT '{$this->comment}' ";
        }
        
        $sql .= "{$this->body}";
        
        return $sql;
    }

    /**
     * Drop the procedure
     */
    public function drop(): bool
    {
        $sql = "DROP PROCEDURE IF EXISTS `{$this->procedureName}`";
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to drop procedure '{$this->procedureName}': " . $e->getMessage());
        }
    }

    /**
     * Check if procedure exists
     */
    public function exists(): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.routines 
                WHERE routine_type = 'PROCEDURE' 
                AND routine_name = ? 
                AND routine_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->procedureName]);
            return $result->fetchColumn(0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Call the procedure
     */
    public function call(array $parameters = []): mixed
    {
        $paramPlaceholders = str_repeat('?,', count($parameters) - 1) . '?';
        $sql = "CALL `{$this->procedureName}`({$paramPlaceholders})";
        
        try {
            return $this->db->query($sql, $parameters);
        } catch (\Exception $e) {
            throw new SchemaException("Failed to call procedure '{$this->procedureName}': " . $e->getMessage());
        }
    }
} 