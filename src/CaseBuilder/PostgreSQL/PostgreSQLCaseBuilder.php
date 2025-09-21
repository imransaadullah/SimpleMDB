<?php

namespace SimpleMDB\CaseBuilder\PostgreSQL;

use SimpleMDB\CaseBuilder\MySQL\MySQLCaseBuilder;
use SimpleMDB\Interfaces\CaseBuilderInterface;

/**
 * PostgreSQLCaseBuilder
 * 
 * PostgreSQL-specific implementation of CASE statement building
 * Inherits all expressive methods from MySQL implementation with PostgreSQL-specific quoting
 */
class PostgreSQLCaseBuilder extends MySQLCaseBuilder
{
    /**
     * Override expressive condition builders for PostgreSQL quoting
     */
    public function whenEquals(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" = ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenNotEquals(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" != ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenGreaterThan(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" > ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenLessThan(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" < ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenBetween(string $column, $min, $max, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" BETWEEN ? AND ?", $result);
        $this->addBinding($min);
        $this->addBinding($max);
        return $this;
    }

    public function whenIn(string $column, array $values, $result): CaseBuilderInterface
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->when("\"{$column}\" IN ({$placeholders})", $result);
        foreach ($values as $value) {
            $this->addBinding($value);
        }
        return $this;
    }

    public function whenLike(string $column, string $pattern, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" LIKE ?", $result);
        $this->addBinding($pattern);
        return $this;
    }

    public function whenILike(string $column, string $pattern, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" ILIKE ?", $result);
        $this->addBinding($pattern);
        return $this;
    }

    public function whenNull(string $column, $result): CaseBuilderInterface
    {
        return $this->when("\"{$column}\" IS NULL", $result);
    }

    public function whenNotNull(string $column, $result): CaseBuilderInterface
    {
        return $this->when("\"{$column}\" IS NOT NULL", $result);
    }

    /**
     * PostgreSQL-specific JSONB operations
     */
    public function whenJsonbHasKey(string $column, string $key, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" ? ?", $result);
        $this->addBinding($key);
        return $this;
    }

    public function whenJsonbContains(string $column, array $data, $result): CaseBuilderInterface
    {
        $this->when("\"{$column}\" @> ?", $result);
        $this->addBinding(json_encode($data));
        return $this;
    }

    public function whenArrayContains(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("? = ANY(\"{$column}\")", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenArrayLength(string $column, int $length, $result): CaseBuilderInterface
    {
        $this->when("array_length(\"{$column}\", 1) = ?", $result);
        $this->addBinding($length);
        return $this;
    }

    /**
     * Override multiple conditions for PostgreSQL quoting
     */
    public function whenAny(array $conditions, $result): CaseBuilderInterface
    {
        $conditionStrings = [];
        foreach ($conditions as $condition => $value) {
            if (is_numeric($condition)) {
                $conditionStrings[] = $value;
            } else {
                $conditionStrings[] = "\"{$condition}\" = ?";
                $this->addBinding($value);
            }
        }
        return $this->when('(' . implode(' OR ', $conditionStrings) . ')', $result);
    }

    public function whenAll(array $conditions, $result): CaseBuilderInterface
    {
        $conditionStrings = [];
        foreach ($conditions as $condition => $value) {
            if (is_numeric($condition)) {
                $conditionStrings[] = $value;
            } else {
                $conditionStrings[] = "\"{$condition}\" = ?";
                $this->addBinding($value);
            }
        }
        return $this->when('(' . implode(' AND ', $conditionStrings) . ')', $result);
    }

    public function elseColumn(string $column): CaseBuilderInterface
    {
        return $this->else("\"{$column}\"");
    }

    public function elseConcat(string ...$columns): CaseBuilderInterface
    {
        $quotedColumns = array_map(fn($col) => "\"{$col}\"", $columns);
        return $this->else('CONCAT(' . implode(', ', $quotedColumns) . ')');
    }

    public function end(string $alias = null): string
    {
        $sql = $this->toSql();
        if ($alias) {
            $sql .= " AS \"$alias\"";  // PostgreSQL uses double quotes
        }
        return $sql;
    }

    protected function formatValue($value): string
    {
        if ($value === null || $value === 'NULL') {
            return 'NULL';
        }
        
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        if (is_numeric($value)) {
            return (string)$value;
        }
        
        if (is_string($value)) {
            // Check if it's a PostgreSQL column reference or function call
            if (str_starts_with($value, '"') || str_contains($value, '(')) {
                return $value;
            }
            return '?';
        }
        
        return '?';
    }

    /**
     * PostgreSQL-specific static builders
     */
    public static function booleanToText(string $column, string $trueText = 'Yes', string $falseText = 'No'): string
    {
        return (new self())->case($column)
            ->whenValue(true, $trueText)
            ->whenValue(false, $falseText)
            ->else($falseText)
            ->end();
    }
}
