<?php

namespace SimpleMDB;

use SimpleMDB\Interfaces\QueryBuilderInterface;
use SimpleMDB\Interfaces\CaseBuilderInterface;

/**
 * EnhancedSimpleQuery
 * 
 * Enhanced version of SimpleQuery with better integration of all components
 */
class EnhancedSimpleQuery
{
    private QueryBuilderInterface $queryBuilder;
    private ?DatabaseInterface $database = null;

    public function __construct(?DatabaseInterface $db = null, ?string $type = null)
    {
        $this->database = $db;
        $this->queryBuilder = QueryBuilderFactory::create($db, $type);
    }

    /**
     * Create a new enhanced query instance
     */
    public static function create(?DatabaseInterface $db = null, ?string $type = null): self
    {
        return new self($db, $type);
    }

    /**
     * Create a CASE statement builder
     */
    public function case(string $column = null): CaseBuilderInterface
    {
        return CaseBuilderFactory::create($this->database)->case($column);
    }

    /**
     * Create a searched CASE statement
     */
    public function caseWhen(): CaseBuilderInterface
    {
        return CaseBuilderFactory::create($this->database)->case();
    }

    /**
     * Add a CASE statement to SELECT
     */
    public function selectCase(CaseBuilderInterface $case, string $alias): self
    {
        $caseSQL = $case->end($alias);
        $this->queryBuilder->raw($caseSQL);
        return $this;
    }

    /**
     * Create a subquery
     */
    public function subQuery(callable $callback): QueryBuilderInterface
    {
        $subQuery = QueryBuilderFactory::create($this->database);
        $callback($subQuery);
        return $subQuery;
    }

    /**
     * Add a complex WHERE with subquery
     */
    public function whereExists(callable $callback): self
    {
        $subQuery = $this->subQuery($callback);
        $this->queryBuilder->where("EXISTS (" . $subQuery->toSql() . ")", $subQuery->getBindings());
        return $this;
    }

    /**
     * Add a WHERE IN with subquery
     */
    public function whereInSubQuery(string $column, callable $callback): self
    {
        $subQuery = $this->subQuery($callback);
        $this->queryBuilder->where("$column IN (" . $subQuery->toSql() . ")", $subQuery->getBindings());
        return $this;
    }

    /**
     * Execute with automatic connection management
     */
    public function execute(?DatabaseInterface $db = null): array
    {
        $connection = $db ?? $this->database;
        if (!$connection) {
            throw new \InvalidArgumentException('Database connection required for execution');
        }
        return $this->queryBuilder->execute($connection);
    }

    /**
     * Get the underlying query builder
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder;
    }

    /**
     * Delegate all other methods to the query builder
     */
    public function __call(string $method, array $arguments)
    {
        $result = $this->queryBuilder->$method(...$arguments);
        
        // Return $this for fluent interface if the result is the query builder
        if ($result === $this->queryBuilder) {
            return $this;
        }
        
        return $result;
    }
}

