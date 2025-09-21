<?php

namespace SimpleMDB\Query\MySQL;

use SimpleMDB\Interfaces\QueryBuilderInterface;
use SimpleMDB\DatabaseInterface;

/**
 * MySQLQueryBuilder
 * 
 * MySQL-specific implementation of QueryBuilderInterface
 * Handles MySQL syntax, operators, and functions
 */
class MySQLQueryBuilder implements QueryBuilderInterface
{
    protected array $select = [];
    protected ?string $from = null;
    protected array $where = [];
    protected array $bindings = [];
    protected array $joins = [];
    protected array $orderBy = [];
    protected array $groupBy = [];
    protected array $having = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected bool $distinct = false;
    protected array $insertData = [];
    protected ?string $insertTable = null;
    protected ?string $updateTable = null;
    protected array $updateData = [];
    protected ?string $deleteTable = null;
    protected array $unions = [];

    public function select(array $columns = ['*']): QueryBuilderInterface
    {
        $this->select = $columns;
        return $this;
    }

    public function from(string $table): QueryBuilderInterface
    {
        $this->from = $table;
        return $this;
    }

    public function where(string $condition, array $bindings = []): QueryBuilderInterface
    {
        $this->where[] = ['type' => 'AND', 'condition' => $condition];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function andWhere(string $condition, array $bindings = []): QueryBuilderInterface
    {
        return $this->where($condition, $bindings);
    }

    public function orWhere(string $condition, array $bindings = []): QueryBuilderInterface
    {
        $this->where[] = ['type' => 'OR', 'condition' => $condition];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function whereIn(string $column, array $values): QueryBuilderInterface
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = ['type' => 'AND', 'condition' => "`{$column}` IN ({$placeholders})"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): QueryBuilderInterface
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = ['type' => 'AND', 'condition' => "`{$column}` NOT IN ({$placeholders})"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereBetween(string $column, array $values): QueryBuilderInterface
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException('whereBetween requires exactly 2 values');
        }
        
        $this->where[] = ['type' => 'AND', 'condition' => "`{$column}` BETWEEN ? AND ?"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereLike(string $column, string $value): QueryBuilderInterface
    {
        $this->where[] = ['type' => 'AND', 'condition' => "`{$column}` LIKE ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): QueryBuilderInterface
    {
        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $table,
            'condition' => $condition
        ];
        return $this;
    }

    public function leftJoin(string $table, string $condition): QueryBuilderInterface
    {
        return $this->join($table, $condition, 'LEFT');
    }

    public function rightJoin(string $table, string $condition): QueryBuilderInterface
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    public function innerJoin(string $table, string $condition): QueryBuilderInterface
    {
        return $this->join($table, $condition, 'INNER');
    }

    public function orderBy(string $column, string $direction = 'ASC'): QueryBuilderInterface
    {
        $this->orderBy[] = "`{$column}` " . strtoupper($direction);
        return $this;
    }

    public function groupBy(array $columns): QueryBuilderInterface
    {
        $this->groupBy = array_map(fn($col) => "`{$col}`", $columns);
        return $this;
    }

    public function having(string $condition, array $bindings = []): QueryBuilderInterface
    {
        $this->having[] = $condition;
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function limit(int $limit): QueryBuilderInterface
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): QueryBuilderInterface
    {
        $this->offset = $offset;
        return $this;
    }

    public function distinct(): QueryBuilderInterface
    {
        $this->distinct = true;
        return $this;
    }

    public function insert(array $data): QueryBuilderInterface
    {
        $this->insertData = $data;
        return $this;
    }

    public function into(string $table): QueryBuilderInterface
    {
        $this->insertTable = $table;
        return $this;
    }

    public function update(string $table): QueryBuilderInterface
    {
        $this->updateTable = $table;
        return $this;
    }

    public function set(array $data): QueryBuilderInterface
    {
        $this->updateData = $data;
        return $this;
    }

    public function delete(string $table): QueryBuilderInterface
    {
        $this->deleteTable = $table;
        return $this;
    }

    public function execute(DatabaseInterface $db): array
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        
        $result = $db->query($sql, $bindings);
        
        // Return appropriate result based on query type
        if ($this->deleteTable || $this->updateTable || $this->insertTable) {
            return ['affected_rows' => $result->affectedRows()];
        }
        
        return $result->fetchAll();
    }

    public function toSql(): string
    {
        if ($this->insertTable && $this->insertData) {
            return $this->buildInsertSql();
        }
        
        if ($this->updateTable && $this->updateData) {
            return $this->buildUpdateSql();
        }
        
        if ($this->deleteTable) {
            return $this->buildDeleteSql();
        }
        
        return $this->buildSelectSql();
    }

    public function getBindings(): array
    {
        $bindings = $this->bindings;
        
        // Add insert data bindings
        if ($this->insertData) {
            $bindings = array_merge($bindings, array_values($this->insertData));
        }
        
        // Add update data bindings
        if ($this->updateData) {
            $bindings = array_merge(array_values($this->updateData), $bindings);
        }
        
        return $bindings;
    }

    public function reset(): QueryBuilderInterface
    {
        $this->select = [];
        $this->from = null;
        $this->where = [];
        $this->bindings = [];
        $this->joins = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->having = [];
        $this->limit = null;
        $this->offset = null;
        $this->distinct = false;
        $this->insertData = [];
        $this->insertTable = null;
        $this->updateTable = null;
        $this->updateData = [];
        $this->deleteTable = null;
        $this->unions = [];
        return $this;
    }

    public static function create(): QueryBuilderInterface
    {
        return new self();
    }

    public function raw(string $sql): QueryBuilderInterface
    {
        // For now, just add to select
        $this->select[] = $sql;
        return $this;
    }

    public function subQuery(QueryBuilderInterface $query): QueryBuilderInterface
    {
        $this->select[] = "(" . $query->toSql() . ")";
        $this->bindings = array_merge($this->bindings, $query->getBindings());
        return $this;
    }

    public function union(QueryBuilderInterface $query): QueryBuilderInterface
    {
        $this->unions[] = ['type' => 'UNION', 'query' => $query];
        return $this;
    }

    public function unionAll(QueryBuilderInterface $query): QueryBuilderInterface
    {
        $this->unions[] = ['type' => 'UNION ALL', 'query' => $query];
        return $this;
    }

    public function count(string $column = '*'): QueryBuilderInterface
    {
        $this->select = ["COUNT({$column}) as count"];
        return $this;
    }

    public function sum(string $column): QueryBuilderInterface
    {
        $this->select = ["SUM(`{$column}`) as sum"];
        return $this;
    }

    public function avg(string $column): QueryBuilderInterface
    {
        $this->select = ["AVG(`{$column}`) as avg"];
        return $this;
    }

    public function max(string $column): QueryBuilderInterface
    {
        $this->select = ["MAX(`{$column}`) as max"];
        return $this;
    }

    public function min(string $column): QueryBuilderInterface
    {
        $this->select = ["MIN(`{$column}`) as min"];
        return $this;
    }

    /**
     * Build SELECT SQL
     */
    protected function buildSelectSql(): string
    {
        $sql = 'SELECT ';
        
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        
        // Add columns
        if (empty($this->select)) {
            $sql .= '*';
        } else {
            $columns = array_map(function($col) {
                return $col === '*' ? '*' : (str_contains($col, '(') ? $col : "`{$col}`");
            }, $this->select);
            $sql .= implode(', ', $columns);
        }
        
        // Add FROM
        if ($this->from) {
            $sql .= " FROM `{$this->from}`";
        }
        
        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['condition']}";
        }
        
        // Add WHERE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $whereParts = [];
            foreach ($this->where as $i => $condition) {
                if ($i === 0) {
                    $whereParts[] = $condition['condition'];
                } else {
                    $whereParts[] = $condition['type'] . ' ' . $condition['condition'];
                }
            }
            $sql .= implode(' ', $whereParts);
        }
        
        // Add GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        
        // Add HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }
        
        // Add ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        // Add LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        // Add OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        // Add UNIONs
        foreach ($this->unions as $union) {
            $sql .= " {$union['type']} " . $union['query']->toSql();
            $this->bindings = array_merge($this->bindings, $union['query']->getBindings());
        }
        
        return $sql;
    }

    /**
     * Build INSERT SQL
     */
    protected function buildInsertSql(): string
    {
        $columns = implode('`, `', array_keys($this->insertData));
        $placeholders = implode(', ', array_fill(0, count($this->insertData), '?'));
        
        return "INSERT INTO `{$this->insertTable}` (`{$columns}`) VALUES ({$placeholders})";
    }

    /**
     * Build UPDATE SQL
     */
    protected function buildUpdateSql(): string
    {
        $setParts = [];
        foreach (array_keys($this->updateData) as $column) {
            $setParts[] = "`{$column}` = ?";
        }
        
        $sql = "UPDATE `{$this->updateTable}` SET " . implode(', ', $setParts);
        
        // Add WHERE for UPDATE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $whereParts = [];
            foreach ($this->where as $i => $condition) {
                if ($i === 0) {
                    $whereParts[] = $condition['condition'];
                } else {
                    $whereParts[] = $condition['type'] . ' ' . $condition['condition'];
                }
            }
            $sql .= implode(' ', $whereParts);
        }
        
        return $sql;
    }

    /**
     * Build DELETE SQL
     */
    protected function buildDeleteSql(): string
    {
        $sql = "DELETE FROM `{$this->deleteTable}`";
        
        // Add WHERE for DELETE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $whereParts = [];
            foreach ($this->where as $i => $condition) {
                if ($i === 0) {
                    $whereParts[] = $condition['condition'];
                } else {
                    $whereParts[] = $condition['type'] . ' ' . $condition['condition'];
                }
            }
            $sql .= implode(' ', $whereParts);
        }
        
        return $sql;
    }
}

