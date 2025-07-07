<?php

namespace SimpleMDB;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleMDB\Traits\LoggerAwareTrait;

class SimpleQuery
{
    use LoggerAwareTrait;

    private $select = [];
    private $from = '';
    private $where = [];
    private $join = [];
    private $orderBy = [];
    private $limit = '';
    private $groupBy = [];
    private $having = [];
    private $unions = [];
    private $cache = [];
    private $cacheEnabled = true;
    private $queryType = 'SELECT';
    private $table = '';
    private $data = [];
    private $ctes = [];
    private $windows = [];
    private $partitions = [];

    /**
     * Create a new query instance
     */
    public static function create(): self
    {
        return new self();
    }

    public function select(array $fields): self
    {
        $this->queryType = 'SELECT';
        $this->select = $fields;
        return $this;
    }

    public function from(string $table): self
    {
        if ($this->queryType === 'DELETE') {
            $this->table = $table;
        } else {
            $this->from = $table;
        }
        return $this;
    }

    public function where(string $condition, array $params = []): self
    {
        $this->where[] = ['condition' => $condition, 'params' => $params];
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->join[] = "$type JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition): self
    {
        $this->join[] = "LEFT JOIN $table ON $condition";
        return $this;
    }

    public function rightJoin(string $table, string $condition): self
    {
        $this->join[] = "RIGHT JOIN $table ON $condition";
        return $this;
    }

    public function fullJoin(string $table, string $condition): self
    {
        $this->join[] = "FULL JOIN $table ON $condition";
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$field $direction";
        return $this;
    }

    public function limit(int $count, int $offset = 0): self
    {
        $this->limit = "$offset, $count";
        return $this;
    }

    public function paginate(int $limit, int $offset = 0): self
    {
        $this->limit($limit, $offset);
        return $this;
    }

    public function groupBy(array $fields): self
    {
        $this->groupBy = $fields;
        return $this;
    }

    public function having(string $condition, array $params = []): self
    {
        $this->having[] = ['condition' => $condition, 'params' => $params];
        return $this;
    }

    public function union(SimpleQuery $query, bool $all = false): self
    {
        $this->unions[] = ['query' => $query, 'all' => $all];
        return $this;
    }

    public function subquery(SimpleQuery $query, string $alias): self
    {
        $this->from = '(' . $query->toSql() . ') AS ' . $alias;
        return $this;
    }

    public function selectWithAlias(array $fieldsWithAliases): self
    {
        $this->select = array_map(function($field, $alias) {
            return is_string($alias) ? "$field AS $alias" : $field;
        }, array_keys($fieldsWithAliases), $fieldsWithAliases);
        return $this;
    }

    public function fromWithAlias(string $table, string $alias): self
    {
        $this->from = "$table AS $alias";
        return $this;
    }

    public function with(string $name, SimpleQuery $query): self
    {
        $this->ctes[$name] = $query;
        return $this;
    }

    public function window(string $name, array $partitionBy = [], array $orderBy = []): self
    {
        $this->windows[$name] = [
            'partition_by' => $partitionBy,
            'order_by' => $orderBy
        ];
        return $this;
    }

    public function over(?string $windowName = null, array $partitionBy = [], array $orderBy = []): Expression
    {
        if ($windowName !== null && isset($this->windows[$windowName])) {
            return new Expression("OVER {$windowName}");
        }

        $sql = 'OVER (';
        if (!empty($partitionBy)) {
            $sql .= 'PARTITION BY ' . implode(', ', $partitionBy);
        }
        if (!empty($orderBy)) {
            $sql .= (!empty($partitionBy) ? ' ' : '') . 'ORDER BY ' . implode(', ', $orderBy);
        }
        $sql .= ')';

        return new Expression($sql);
    }

    public function rowNumber(array $partitionBy = [], array $orderBy = []): Expression
    {
        return new Expression(
            'ROW_NUMBER() ' . $this->over(null, $partitionBy, $orderBy)->getExpression()
        );
    }

    public function rank(array $partitionBy = [], array $orderBy = []): Expression
    {
        return new Expression(
            'RANK() ' . $this->over(null, $partitionBy, $orderBy)->getExpression()
        );
    }

    public function denseRank(array $partitionBy = [], array $orderBy = []): Expression
    {
        return new Expression(
            'DENSE_RANK() ' . $this->over(null, $partitionBy, $orderBy)->getExpression()
        );
    }

    public function lag(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): Expression
    {
        $sql = "LAG({$column}, {$offset}";
        if ($defaultValue !== null) {
            $sql .= ", " . ($defaultValue instanceof Expression ? $defaultValue->getExpression() : '?');
        }
        $sql .= ") " . $this->over(null, $partitionBy, $orderBy)->getExpression();
        
        return new Expression($sql, $defaultValue !== null && !($defaultValue instanceof Expression) ? [$defaultValue] : []);
    }

    public function lead(string $column, int $offset = 1, $defaultValue = null, array $partitionBy = [], array $orderBy = []): Expression
    {
        $sql = "LEAD({$column}, {$offset}";
        if ($defaultValue !== null) {
            $sql .= ", " . ($defaultValue instanceof Expression ? $defaultValue->getExpression() : '?');
        }
        $sql .= ") " . $this->over(null, $partitionBy, $orderBy)->getExpression();
        
        return new Expression($sql, $defaultValue !== null && !($defaultValue instanceof Expression) ? [$defaultValue] : []);
    }

    public function firstValue(string $column, array $partitionBy = [], array $orderBy = []): Expression
    {
        return new Expression(
            "FIRST_VALUE({$column}) " . $this->over(null, $partitionBy, $orderBy)->getExpression()
        );
    }

    public function lastValue(string $column, array $partitionBy = [], array $orderBy = []): Expression
    {
        return new Expression(
            "LAST_VALUE({$column}) " . $this->over(null, $partitionBy, $orderBy)->getExpression()
        );
    }

    protected function buildCTEs(): string
    {
        if (empty($this->ctes)) {
            return '';
        }

        $cteSql = 'WITH ';
        $first = true;
        foreach ($this->ctes as $name => $query) {
            if (!$first) {
                $cteSql .= ', ';
            }
            $cteSql .= "{$name} AS (" . $query->toSql() . ")";
            $first = false;
        }
        return $cteSql;
    }

    public function toSql(): string
    {
        $sql = $this->buildCTEs();
        
        switch ($this->queryType) {
            case 'INSERT':
                $fields = array_keys($this->data);
                $placeholders = array_fill(0, count($fields), '?');
                $sql .= ($sql ? ' ' : '') . "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                break;
                
            case 'UPDATE':
                $setParts = [];
                foreach (array_keys($this->data) as $field) {
                    $setParts[] = "$field = ?";
                }
                $sql .= ($sql ? ' ' : '') . "UPDATE {$this->table} SET " . implode(', ', $setParts);
                if (!empty($this->where)) {
                    $conditions = array_map(fn($w) => $w['condition'], $this->where);
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }
                break;
                
            case 'DELETE':
                $sql .= ($sql ? ' ' : '') . "DELETE FROM {$this->table}";
                if (!empty($this->where)) {
                    $conditions = array_map(fn($w) => $w['condition'], $this->where);
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }
                break;
                
            case 'SELECT':
            default:
                $sql .= ($sql ? ' ' : '') . 'SELECT ' . (empty($this->select) ? '*' : implode(', ', $this->select));
                $sql .= ' FROM ' . $this->from;

                if (!empty($this->join)) {
                    $sql .= ' ' . implode(' ', $this->join);
                }

                if (!empty($this->where)) {
                    $conditions = array_map(fn($w) => $w['condition'], $this->where);
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }

                if (!empty($this->groupBy)) {
                    $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
                }

                if (!empty($this->having)) {
                    $conditions = array_map(fn($h) => $h['condition'], $this->having);
                    $sql .= ' HAVING ' . implode(' AND ', $conditions);
                }

                if (!empty($this->windows)) {
                    $sql .= ' WINDOW ';
                    $windowParts = [];
                    foreach ($this->windows as $name => $window) {
                        $windowDef = $name . ' AS (';
                        if (!empty($window['partition_by'])) {
                            $windowDef .= 'PARTITION BY ' . implode(', ', $window['partition_by']);
                        }
                        if (!empty($window['order_by'])) {
                            $windowDef .= (!empty($window['partition_by']) ? ' ' : '') . 'ORDER BY ' . implode(', ', $window['order_by']);
                        }
                        $windowDef .= ')';
                        $windowParts[] = $windowDef;
                    }
                    $sql .= implode(', ', $windowParts);
                }

                if (!empty($this->orderBy)) {
                    $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
                }

                if ($this->limit) {
                    $sql .= ' LIMIT ' . $this->limit;
                }

                foreach ($this->unions as $union) {
                    $sql .= $union['all'] ? ' UNION ALL ' : ' UNION ';
                    $sql .= $union['query']->toSql();
                }
                break;
        }

        return $sql;
    }

    public function getParams(): array
    {
        $params = [];
        
        // Add CTE parameters first
        foreach ($this->ctes as $query) {
            $params = array_merge($params, $query->getParams());
        }
        
        switch ($this->queryType) {
            case 'INSERT':
                $params = array_merge($params, array_values($this->data));
                break;
                
            case 'UPDATE':
                $params = array_merge($params, array_values($this->data));
                foreach ($this->where as $w) {
                    $params = array_merge($params, $w['params']);
                }
                break;
                
            case 'DELETE':
                foreach ($this->where as $w) {
                    $params = array_merge($params, $w['params']);
                }
                break;
                
            case 'SELECT':
            default:
                foreach ($this->where as $w) {
                    $params = array_merge($params, $w['params']);
                }
                foreach ($this->having as $h) {
                    $params = array_merge($params, $h['params']);
                }
                foreach ($this->unions as $union) {
                    $params = array_merge($params, $union['query']->getParams());
                }
                break;
        }
        
        return $params;
    }

    private function getCacheKey(): string
    {
        return md5($this->toSql() . json_encode($this->getParams()));
    }

    public function enableCache(bool $enable = true): self
    {
        $this->cacheEnabled = $enable;
        return $this;
    }

    public function execute(DatabaseInterface $db, string $fetchType = 'assoc')
    {
        $cacheKey = $this->getCacheKey();
        if ($this->cacheEnabled && isset($this->cache[$cacheKey]) && $this->queryType === 'SELECT') {
            $this->log('debug', 'Cache hit for SQL: '.$this->toSql());
            return $this->cache[$cacheKey];
        }

        try {
            $sql = $this->toSql();
            $params = $this->getParams();
            $start = microtime(true);
            $stmt = $db->prepare($sql)->execute($params);
            $time = microtime(true) - $start;
            $this->log('debug', $sql, ['params'=>$params,'time'=>$time]);
            
            if ($this->queryType === 'SELECT') {
                $results = $stmt->fetchAll($fetchType);
                if ($this->cacheEnabled) {
                    $this->cache[$cacheKey] = $results;
                }
                return $results;
            } else {
                // For INSERT, UPDATE, DELETE - execute the query
                $result = [
                    'affectedRows' => $stmt->affectedRows(),
                    'queryType' => $this->queryType
                ];
                
                // For INSERT, try to get the last insert ID
                if ($this->queryType === 'INSERT') {
                    $result['lastInsertId'] = $db->lastInsertId();
                }
                
                return $result;
            }
        } catch (Exception $e) {
            throw new Exception("Query execution failed: " . $e->getMessage() . "\nSQL: $sql\nParams: " . json_encode($params));
        }
    }

    public function executeInTransaction(DatabaseInterface $db, string $fetchType = 'assoc')
    {
        try {
            $db->beginTransaction();
            $results = $this->execute($db, $fetchType);
            $db->commit();
            return $results;
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception("Transaction failed: " . $e->getMessage());
        }
    }

    public function addCondition(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    public function count(string $field = '*', string $alias = 'count'): self
    {
        $this->select[] = "COUNT($field) AS $alias";
        return $this;
    }

    public function sum(string $field, string $alias = 'sum'): self
    {
        $this->select[] = "SUM($field) AS $alias";
        return $this;
    }

    public function avg(string $field, string $alias = 'avg'): self
    {
        $this->select[] = "AVG($field) AS $alias";
        return $this;
    }

    public function insert(array $data): self
    {
        $this->queryType = 'INSERT';
        $this->data = $data;
        return $this;
    }

    public function into(string $table): self
    {
        if ($this->queryType !== 'INSERT') {
            throw new Exception('into() can only be used with INSERT queries');
        }
        $this->table = $table;
        return $this;
    }

    public function update(): self
    {
        $this->queryType = 'UPDATE';
        return $this;
    }

    public function set(array $data): self
    {
        if ($this->queryType !== 'UPDATE') {
            throw new Exception('set() can only be used with UPDATE queries');
        }
        $this->data = $data;
        return $this;
    }

    public function table(string $table): self
    {
        if ($this->queryType !== 'UPDATE') {
            throw new Exception('table() can only be used with UPDATE queries');
        }
        $this->table = $table;
        return $this;
    }

    public function delete(): self
    {
        $this->queryType = 'DELETE';
        return $this;
    }
} 