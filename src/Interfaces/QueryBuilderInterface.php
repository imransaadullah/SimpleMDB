<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * QueryBuilderInterface
 * 
 * Defines the contract for query builders across different database engines.
 * Each database engine can implement this interface with its specific SQL syntax.
 */
interface QueryBuilderInterface
{
    /**
     * Set the columns to select
     */
    public function select(array $columns = ['*']): self;

    /**
     * Set the table to select from
     */
    public function from(string $table): self;

    /**
     * Add a WHERE clause
     */
    public function where(string $condition, array $bindings = []): self;

    /**
     * Add an AND WHERE clause
     */
    public function andWhere(string $condition, array $bindings = []): self;

    /**
     * Add an OR WHERE clause
     */
    public function orWhere(string $condition, array $bindings = []): self;

    /**
     * Add a WHERE IN clause
     */
    public function whereIn(string $column, array $values): self;

    /**
     * Add a WHERE NOT IN clause
     */
    public function whereNotIn(string $column, array $values): self;

    /**
     * Add a WHERE BETWEEN clause
     */
    public function whereBetween(string $column, array $values): self;

    /**
     * Add a WHERE LIKE clause
     */
    public function whereLike(string $column, string $value): self;

    /**
     * Add a JOIN clause
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self;

    /**
     * Add a LEFT JOIN clause
     */
    public function leftJoin(string $table, string $condition): self;

    /**
     * Add a RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $condition): self;

    /**
     * Add a INNER JOIN clause
     */
    public function innerJoin(string $table, string $condition): self;

    /**
     * Add an ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self;

    /**
     * Add a GROUP BY clause
     */
    public function groupBy(array $columns): self;

    /**
     * Add a HAVING clause
     */
    public function having(string $condition, array $bindings = []): self;

    /**
     * Set the LIMIT
     */
    public function limit(int $limit): self;

    /**
     * Set the OFFSET
     */
    public function offset(int $offset): self;

    /**
     * Add DISTINCT clause
     */
    public function distinct(): self;

    /**
     * Set data for INSERT
     */
    public function insert(array $data): self;

    /**
     * Set table for INSERT
     */
    public function into(string $table): self;

    /**
     * Set table for UPDATE
     */
    public function update(string $table): self;

    /**
     * Set data for UPDATE
     */
    public function set(array $data): self;

    /**
     * Set table for DELETE
     */
    public function delete(string $table): self;

    /**
     * Execute the query
     */
    public function execute(DatabaseInterface $db): array;

    /**
     * Get the generated SQL
     */
    public function toSql(): string;

    /**
     * Get the bindings
     */
    public function getBindings(): array;

    /**
     * Reset the query builder
     */
    public function reset(): self;

    /**
     * Create a new instance
     */
    public static function create(): self;

    /**
     * Add a raw SQL expression
     */
    public function raw(string $sql): self;

    /**
     * Add a subquery
     */
    public function subQuery(QueryBuilderInterface $query): self;

    /**
     * Add UNION clause
     */
    public function union(QueryBuilderInterface $query): self;

    /**
     * Add UNION ALL clause
     */
    public function unionAll(QueryBuilderInterface $query): self;

    /**
     * Count records
     */
    public function count(string $column = '*'): self;

    /**
     * Sum records
     */
    public function sum(string $column): self;

    /**
     * Average records
     */
    public function avg(string $column): self;

    /**
     * Maximum value
     */
    public function max(string $column): self;

    /**
     * Minimum value
     */
    public function min(string $column): self;
}