<?php

namespace SimpleMDB\Query\PostgreSQL;

use SimpleMDB\Query\MySQL\MySQLQueryBuilder;

/**
 * PostgreSQLQueryBuilder
 * 
 * PostgreSQL-specific implementation of QueryBuilderInterface
 * Extends MySQLQueryBuilder and overrides PostgreSQL-specific methods
 */
class PostgreSQLQueryBuilder extends MySQLQueryBuilder
{
    /**
     * Build SELECT SQL with PostgreSQL-specific syntax
     */
    protected function buildSelectSql(): string
    {
        $sql = 'SELECT ';
        
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        
        // Add columns with PostgreSQL quoting
        if (empty($this->select)) {
            $sql .= '*';
        } else {
            $columns = array_map(function($col) {
                return $col === '*' ? '*' : (str_contains($col, '(') ? $col : "\"{$col}\"");
            }, $this->select);
            $sql .= implode(', ', $columns);
        }
        
        // Add FROM with PostgreSQL quoting
        if ($this->from) {
            $sql .= " FROM \"{$this->from}\"";
        }
        
        // Add JOINs with PostgreSQL quoting
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN \"{$join['table']}\" ON {$join['condition']}";
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
        
        // Add GROUP BY with PostgreSQL quoting
        if (!empty($this->groupBy)) {
            $groupColumns = array_map(fn($col) => "\"{$col}\"", $this->groupBy);
            $sql .= ' GROUP BY ' . implode(', ', $groupColumns);
        }
        
        // Add HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }
        
        // Add ORDER BY with PostgreSQL quoting
        if (!empty($this->orderBy)) {
            $orderColumns = array_map(function($orderClause) {
                // Extract column and direction
                $parts = explode(' ', $orderClause);
                $column = str_replace('`', '"', $parts[0]); // Convert MySQL backticks to PostgreSQL quotes
                $direction = $parts[1] ?? 'ASC';
                return "{$column} {$direction}";
            }, $this->orderBy);
            $sql .= ' ORDER BY ' . implode(', ', $orderColumns);
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
     * Build INSERT SQL with PostgreSQL-specific syntax
     */
    protected function buildInsertSql(): string
    {
        $columns = '"' . implode('", "', array_keys($this->insertData)) . '"';
        $placeholders = implode(', ', array_fill(0, count($this->insertData), '?'));
        
        return "INSERT INTO \"{$this->insertTable}\" ({$columns}) VALUES ({$placeholders})";
    }

    /**
     * Build UPDATE SQL with PostgreSQL-specific syntax
     */
    protected function buildUpdateSql(): string
    {
        $setParts = [];
        foreach (array_keys($this->updateData) as $column) {
            $setParts[] = "\"{$column}\" = ?";
        }
        
        $sql = "UPDATE \"{$this->updateTable}\" SET " . implode(', ', $setParts);
        
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
     * Build DELETE SQL with PostgreSQL-specific syntax
     */
    protected function buildDeleteSql(): string
    {
        $sql = "DELETE FROM \"{$this->deleteTable}\"";
        
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

    /**
     * Override whereIn with PostgreSQL-specific quoting
     */
    public function whereIn(string $column, array $values): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" IN ({$placeholders})"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Override whereNotIn with PostgreSQL-specific quoting
     */
    public function whereNotIn(string $column, array $values): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" NOT IN ({$placeholders})"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Override whereBetween with PostgreSQL-specific quoting
     */
    public function whereBetween(string $column, array $values): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException('whereBetween requires exactly 2 values');
        }
        
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" BETWEEN ? AND ?"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Override whereLike with PostgreSQL-specific quoting
     */
    public function whereLike(string $column, string $value): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" LIKE ?"];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * PostgreSQL-specific ILIKE operator
     */
    public function whereILike(string $column, string $value): PostgreSQLQueryBuilder
    {
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" ILIKE ?"];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Override orderBy with PostgreSQL-specific quoting
     */
    public function orderBy(string $column, string $direction = 'ASC'): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->orderBy[] = "\"{$column}\" " . strtoupper($direction);
        return $this;
    }

    /**
     * Override groupBy with PostgreSQL-specific quoting
     */
    public function groupBy(array $columns): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->groupBy = $columns; // Store without quotes, will be added in buildSelectSql
        return $this;
    }

    /**
     * Override sum with PostgreSQL-specific quoting
     */
    public function sum(string $column): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->select = ["SUM(\"{$column}\") as sum"];
        return $this;
    }

    /**
     * Override avg with PostgreSQL-specific quoting
     */
    public function avg(string $column): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->select = ["AVG(\"{$column}\") as avg"];
        return $this;
    }

    /**
     * Override max with PostgreSQL-specific quoting
     */
    public function max(string $column): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->select = ["MAX(\"{$column}\") as max"];
        return $this;
    }

    /**
     * Override min with PostgreSQL-specific quoting
     */
    public function min(string $column): \SimpleMDB\Interfaces\QueryBuilderInterface
    {
        $this->select = ["MIN(\"{$column}\") as min"];
        return $this;
    }

    /**
     * PostgreSQL-specific JSONB operators
     */
    public function whereJsonb(string $column, string $operator, $value): PostgreSQLQueryBuilder
    {
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" {$operator} ?"];
        $this->bindings[] = is_array($value) ? json_encode($value) : $value;
        return $this;
    }

    /**
     * PostgreSQL-specific array operations
     */
    public function whereArrayContains(string $column, $value): PostgreSQLQueryBuilder
    {
        $this->where[] = ['type' => 'AND', 'condition' => "\"{$column}\" @> ?"];
        $this->bindings[] = is_array($value) ? '{' . implode(',', $value) . '}' : $value;
        return $this;
    }

    /**
     * PostgreSQL-specific array length
     */
    public function whereArrayLength(string $column, int $length): PostgreSQLQueryBuilder
    {
        $this->where[] = ['type' => 'AND', 'condition' => "array_length(\"{$column}\", 1) = ?"];
        $this->bindings[] = $length;
        return $this;
    }

    /**
     * PostgreSQL-specific full-text search
     */
    public function whereFullText(string $column, string $query): PostgreSQLQueryBuilder
    {
        $this->where[] = ['type' => 'AND', 'condition' => "to_tsvector(\"{$column}\") @@ plainto_tsquery(?)"];
        $this->bindings[] = $query;
        return $this;
    }
}

