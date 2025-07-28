<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Expression;

/**
 * Interface for query building operations
 */
interface QueryBuilderInterface
{
    /**
     * Create a new query instance
     */
    public static function create(): self;

    /**
     * SELECT operations
     */
    public function select(array $fields): self;
    public function selectWithAlias(array $fieldsWithAliases): self;
    public function count(string $field = '*', string $alias = 'count'): self;
    public function sum(string $field, string $alias = 'sum'): self;
    public function avg(string $field, string $alias = 'avg'): self;

    /**
     * FROM operations
     */
    public function from(string $table): self;
    public function fromWithAlias(string $table, string $alias): self;
    public function into(string $table): self;

    /**
     * WHERE operations
     */
    public function where(string $condition, array $params = []): self;
    public function addCondition(bool $condition, callable $callback): self;

    /**
     * JOIN operations
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self;
    public function leftJoin(string $table, string $condition): self;
    public function rightJoin(string $table, string $condition): self;
    public function fullJoin(string $table, string $condition): self;

    /**
     * GROUP BY operations
     */
    public function groupBy(array $fields): self;
    public function having(string $condition, array $params = []): self;

    /**
     * ORDER BY operations
     */
    public function orderBy(string $field, string $direction = 'ASC'): self;

    /**
     * LIMIT operations
     */
    public function limit(int $count, int $offset = 0): self;
    public function paginate(int $limit, int $offset = 0): self;

    /**
     * UNION operations
     */
    public function union(QueryBuilderInterface $query, bool $all = false): self;
    public function subquery(QueryBuilderInterface $query, string $alias): self;

    /**
     * Common Table Expressions (CTEs)
     */
    public function with(string $name, QueryBuilderInterface $query): self;

    /**
     * Window functions
     */
    public function window(string $name, array $partitionBy = [], array $orderBy = []): self;
    public function over(?string $windowName = null, array $partitionBy = [], array $orderBy = []): Expression;
    public function rowNumber(array $partitionBy = [], array $orderBy = []): Expression;
    public function rank(array $partitionBy = [], array $orderBy = []): Expression;
    public function denseRank(array $partitionBy = [], array $orderBy = []): Expression;
    public function lag(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): Expression;
    public function lead(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): Expression;
    public function firstValue(string $column, array $partitionBy = [], array $orderBy = []): Expression;
    public function lastValue(string $column, array $partitionBy = [], array $orderBy = []): Expression;

    /**
     * INSERT operations
     */
    public function insert(array $data): self;

    /**
     * UPDATE operations
     */
    public function update(): self;
    public function set(array $data): self;
    public function table(string $table): self;

    /**
     * DELETE operations
     */
    public function delete(): self;

    /**
     * SQL generation and execution
     */
    public function toSql(): string;
    public function getParams(): array;
    public function execute(DatabaseInterface $db, string $fetchType = 'assoc');
    public function executeInTransaction(DatabaseInterface $db, string $fetchType = 'assoc');

    /**
     * Caching
     */
    public function enableCache(bool $enable = true): self;

    /**
     * Utility methods
     */
    public static function escapeIdentifier(string $identifier): string;
} 