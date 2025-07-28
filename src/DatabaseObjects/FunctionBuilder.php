<?php

namespace SimpleMDB\DatabaseObjects;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Exceptions\SchemaException;
use SimpleMDB\DatabaseObjects\Interfaces\FunctionInterface;

/**
 * Fluent interface for creating and managing database functions
 */
class FunctionBuilder implements FunctionInterface
{
    private DatabaseInterface $db;
    private string $functionName;
    private array $parameters = [];
    private string $returnType = '';
    private string $body = '';
    private string $characteristic = 'DETERMINISTIC';
    private string $sqlDataAccess = 'CONTAINS SQL';
    private string $security = 'DEFINER';
    private string $definer = '';
    private string $comment = '';
    private bool $ifNotExists = false;

    public function __construct(DatabaseInterface $db, string $functionName)
    {
        $this->db = $db;
        $this->functionName = $functionName;
    }

    /**
     * Add a parameter to the function
     */
    public function parameter(string $name, string $type, bool $isOut = false): self
    {
        $direction = $isOut ? 'OUT' : 'IN';
        $this->parameters[] = [
            'name' => $name,
            'type' => $type,
            'direction' => $direction
        ];
        return $this;
    }

    /**
     * Add an IN parameter (default)
     */
    public function inParameter(string $name, string $type): self
    {
        return $this->parameter($name, $type, false);
    }

    /**
     * Add an OUT parameter
     */
    public function outParameter(string $name, string $type): self
    {
        return $this->parameter($name, $type, true);
    }

    /**
     * Set the return type
     */
    public function returns(string $type): self
    {
        $this->returnType = $type;
        return $this;
    }

    /**
     * Set the function body
     */
    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set function characteristics
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
     * Create the function
     */
    public function create(): bool
    {
        $sql = $this->generateCreateSql();
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to create function '{$this->functionName}': " . $e->getMessage());
        }
    }

    /**
     * Generate the CREATE FUNCTION SQL
     */
    public function generateCreateSql(): string
    {
        if (empty($this->returnType)) {
            throw new SchemaException("Return type is required for function '{$this->functionName}'");
        }

        if (empty($this->body)) {
            throw new SchemaException("Function body is required for function '{$this->functionName}'");
        }

        $sql = "CREATE FUNCTION ";
        
        if ($this->ifNotExists) {
            $sql .= "IF NOT EXISTS ";
        }
        
        $sql .= "`{$this->functionName}`(";
        
        // Add parameters
        $paramStrings = [];
        foreach ($this->parameters as $param) {
            $paramStrings[] = "{$param['direction']} `{$param['name']}` {$param['type']}";
        }
        $sql .= implode(', ', $paramStrings);
        
        $sql .= ") RETURNS {$this->returnType} ";
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
     * Drop the function
     */
    public function drop(): bool
    {
        $sql = "DROP FUNCTION IF EXISTS `{$this->functionName}`";
        
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to drop function '{$this->functionName}': " . $e->getMessage());
        }
    }

    /**
     * Check if function exists
     */
    public function exists(): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.routines 
                WHERE routine_type = 'FUNCTION' 
                AND routine_name = ? 
                AND routine_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->functionName]);
            return $result->fetchColumn(0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Execute the function with parameters
     */
    public function execute(array $parameters = []): mixed
    {
        $paramPlaceholders = str_repeat('?,', count($parameters) - 1) . '?';
        $sql = "SELECT `{$this->functionName}`({$paramPlaceholders}) as result";
        
        try {
            $result = $this->db->query($sql, $parameters);
            return $result->fetchColumn(0);
        } catch (\Exception $e) {
            throw new SchemaException("Failed to execute function '{$this->functionName}': " . $e->getMessage());
        }
    }

    /**
     * Call the function (alias for execute)
     */
    public function call(array $parameters = []): mixed
    {
        return $this->execute($parameters);
    }

    /**
     * Get function definition
     */
    public function getDefinition(): ?string
    {
        $sql = "SELECT ROUTINE_DEFINITION FROM information_schema.routines 
                WHERE routine_type = 'FUNCTION' 
                AND routine_name = ? 
                AND routine_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->functionName]);
            return $result->fetchColumn(0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get function parameters
     */
    public function getParameters(): array
    {
        $sql = "SELECT PARAMETER_NAME, PARAMETER_MODE, DATA_TYPE 
                FROM information_schema.parameters 
                WHERE specific_name = ? 
                AND specific_schema = DATABASE()
                ORDER BY ordinal_position";
        
        try {
            $result = $this->db->query($sql, [$this->functionName]);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get function return type
     */
    public function getReturnType(): ?string
    {
        $sql = "SELECT DATA_TYPE FROM information_schema.routines 
                WHERE routine_type = 'FUNCTION' 
                AND routine_name = ? 
                AND routine_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->functionName]);
            return $result->fetchColumn(0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Modify function characteristics
     */
    public function alterDeterministic(): bool
    {
        $sql = "ALTER FUNCTION `{$this->functionName}` DETERMINISTIC";
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to alter function '{$this->functionName}': " . $e->getMessage());
        }
    }

    public function alterNotDeterministic(): bool
    {
        $sql = "ALTER FUNCTION `{$this->functionName}` NOT DETERMINISTIC";
        try {
            $this->db->query($sql);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to alter function '{$this->functionName}': " . $e->getMessage());
        }
    }

    public function alterComment(string $comment): bool
    {
        $sql = "ALTER FUNCTION `{$this->functionName}` COMMENT ?";
        try {
            $this->db->query($sql, [$comment]);
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to alter function '{$this->functionName}': " . $e->getMessage());
        }
    }

    /**
     * Rename the function
     */
    public function rename(string $newName): bool
    {
        $sql = "RENAME FUNCTION `{$this->functionName}` TO `{$newName}`";
        try {
            $this->db->query($sql);
            $this->functionName = $newName;
            return true;
        } catch (\Exception $e) {
            throw new SchemaException("Failed to rename function '{$this->functionName}': " . $e->getMessage());
        }
    }

    /**
     * Get function information
     */
    public function getInfo(): ?array
    {
        $sql = "SELECT * FROM information_schema.routines 
                WHERE routine_type = 'FUNCTION' 
                AND routine_name = ? 
                AND routine_schema = DATABASE()";
        
        try {
            $result = $this->db->query($sql, [$this->functionName]);
            return $result->fetch('assoc');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the function name
     */
    public function getName(): string
    {
        return $this->functionName;
    }

    /**
     * Get the database connection
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->db;
    }
} 