<?php

namespace SimpleMDB;

use SimpleMDB\Interfaces\QueryBuilderInterface;
use SimpleMDB\QueryBuilderFactory;

/**
 * SimpleQuery (New Interface-Based Implementation)
 * 
 * Backward compatibility wrapper that maintains the existing SimpleQuery API
 * while using the new interface-based architecture underneath.
 */
class SimpleQuery_New
{
    private QueryBuilderInterface $queryBuilder;
    private ?string $databaseType = null;

    public function __construct(?string $databaseType = null)
    {
        $this->databaseType = $databaseType;
        $this->queryBuilder = QueryBuilderFactory::create($databaseType);
    }

    /**
     * Create a new query instance
     */
    public static function create(?string $databaseType = null): self
    {
        return new self($databaseType);
    }

    /**
     * Create a MySQL-specific query instance
     */
    public static function createMySQL(): self
    {
        return new self(DatabaseFactory::TYPE_MYSQLI);
    }

    /**
     * Create a PostgreSQL-specific query instance
     */
    public static function createPostgreSQL(): self
    {
        return new self(DatabaseFactory::TYPE_POSTGRESQL);
    }

    // Delegate all methods to the query builder implementation
    public function select(array $fields): self
    {
        $this->queryBuilder->select($fields);
        return $this;
    }

    public function from(string $table): self
    {
        $this->queryBuilder->from($table);
        return $this;
    }

    public function where(string $condition, array $bindings = []): self
    {
        $this->queryBuilder->where($condition, $bindings);
        return $this;
    }

    public function andWhere(string $condition, array $bindings = []): self
    {
        $this->queryBuilder->andWhere($condition, $bindings);
        return $this;
    }

    public function orWhere(string $condition, array $bindings = []): self
    {
        $this->queryBuilder->orWhere($condition, $bindings);
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->queryBuilder->whereIn($column, $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->queryBuilder->whereNotIn($column, $values);
        return $this;
    }

    public function whereBetween(string $column, array $values): self
    {
        $this->queryBuilder->whereBetween($column, $values);
        return $this;
    }

    public function whereLike(string $column, string $value): self
    {
        $this->queryBuilder->whereLike($column, $value);
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->queryBuilder->join($table, $condition, $type);
        return $this;
    }

    public function leftJoin(string $table, string $condition): self
    {
        $this->queryBuilder->leftJoin($table, $condition);
        return $this;
    }

    public function rightJoin(string $table, string $condition): self
    {
        $this->queryBuilder->rightJoin($table, $condition);
        return $this;
    }

    public function innerJoin(string $table, string $condition): self
    {
        $this->queryBuilder->innerJoin($table, $condition);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->queryBuilder->orderBy($column, $direction);
        return $this;
    }

    public function groupBy(array $columns): self
    {
        $this->queryBuilder->groupBy($columns);
        return $this;
    }

    public function having(string $condition, array $bindings = []): self
    {
        $this->queryBuilder->having($condition, $bindings);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->queryBuilder->limit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->queryBuilder->offset($offset);
        return $this;
    }

    public function distinct(): self
    {
        $this->queryBuilder->distinct();
        return $this;
    }

    public function insert(array $data): self
    {
        $this->queryBuilder->insert($data);
        return $this;
    }

    public function into(string $table): self
    {
        $this->queryBuilder->into($table);
        return $this;
    }

    public function update(string $table): self
    {
        $this->queryBuilder->update($table);
        return $this;
    }

    public function set(array $data): self
    {
        $this->queryBuilder->set($data);
        return $this;
    }

    public function delete(string $table): self
    {
        $this->queryBuilder->delete($table);
        return $this;
    }

    public function execute(DatabaseInterface $db): array
    {
        return $this->queryBuilder->execute($db);
    }

    public function toSql(): string
    {
        return $this->queryBuilder->toSql();
    }

    public function getBindings(): array
    {
        return $this->queryBuilder->getBindings();
    }

    public function reset(): self
    {
        $this->queryBuilder->reset();
        return $this;
    }

    public function raw(string $sql): self
    {
        $this->queryBuilder->raw($sql);
        return $this;
    }

    public function subQuery(QueryBuilderInterface $query): self
    {
        $this->queryBuilder->subQuery($query);
        return $this;
    }

    public function union(QueryBuilderInterface $query): self
    {
        $this->queryBuilder->union($query);
        return $this;
    }

    public function unionAll(QueryBuilderInterface $query): self
    {
        $this->queryBuilder->unionAll($query);
        return $this;
    }

    public function count(string $column = '*'): self
    {
        $this->queryBuilder->count($column);
        return $this;
    }

    public function sum(string $column): self
    {
        $this->queryBuilder->sum($column);
        return $this;
    }

    public function avg(string $column): self
    {
        $this->queryBuilder->avg($column);
        return $this;
    }

    public function max(string $column): self
    {
        $this->queryBuilder->max($column);
        return $this;
    }

    public function min(string $column): self
    {
        $this->queryBuilder->min($column);
        return $this;
    }

    /**
     * Get the underlying query builder implementation
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder;
    }

    /**
     * Magic method to handle database-specific methods
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->queryBuilder, $method)) {
            $result = $this->queryBuilder->$method(...$arguments);
            
            // Return $this for fluent interface if the result is the query builder
            if ($result === $this->queryBuilder) {
                return $this;
            }
            
            return $result;
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist on " . get_class($this->queryBuilder));
    }
}

