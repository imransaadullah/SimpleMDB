<?php

namespace SimpleMDB\CaseBuilder\MySQL;

use SimpleMDB\Interfaces\CaseBuilderInterface;

/**
 * MySQLCaseBuilder
 * 
 * Expressive MySQL-specific implementation of CASE statement building
 * Supports fluent, readable syntax for complex conditional logic
 */
class MySQLCaseBuilder implements CaseBuilderInterface
{
    private ?string $caseColumn = null;
    private array $whenConditions = [];
    private array $bindings = [];
    private ?array $elseClause = null;
    private bool $isSearched = false;

    public function case(string $column = null): CaseBuilderInterface
    {
        $this->caseColumn = $column;
        $this->isSearched = $column === null;
        return $this;
    }

    public function when(string $condition, $value): CaseBuilderInterface
    {
        $this->whenConditions[] = [
            'condition' => $condition,
            'value' => $value,
            'bindings' => $this->extractBindings($value)
        ];
        
        return $this;
    }

    /**
     * Expressive condition builders for searched CASE
     */
    public function whenEquals(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("`{$column}` = ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenNotEquals(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("`{$column}` != ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenGreaterThan(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("`{$column}` > ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenLessThan(string $column, $value, $result): CaseBuilderInterface
    {
        $this->when("`{$column}` < ?", $result);
        $this->addBinding($value);
        return $this;
    }

    public function whenBetween(string $column, $min, $max, $result): CaseBuilderInterface
    {
        $this->when("`{$column}` BETWEEN ? AND ?", $result);
        $this->addBinding($min);
        $this->addBinding($max);
        return $this;
    }

    public function whenIn(string $column, array $values, $result): CaseBuilderInterface
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->when("`{$column}` IN ({$placeholders})", $result);
        foreach ($values as $value) {
            $this->addBinding($value);
        }
        return $this;
    }

    public function whenLike(string $column, string $pattern, $result): CaseBuilderInterface
    {
        $this->when("`{$column}` LIKE ?", $result);
        $this->addBinding($pattern);
        return $this;
    }

    public function whenNull(string $column, $result): CaseBuilderInterface
    {
        return $this->when("`{$column}` IS NULL", $result);
    }

    public function whenNotNull(string $column, $result): CaseBuilderInterface
    {
        return $this->when("`{$column}` IS NOT NULL", $result);
    }

    /**
     * Expressive value builders for simple CASE
     */
    public function whenValue($value, $result): CaseBuilderInterface
    {
        if ($this->isSearched) {
            throw new \InvalidArgumentException('whenValue() can only be used with simple CASE statements');
        }
        return $this->when($this->formatValue($value), $result);
    }

    /**
     * Multiple conditions with OR
     */
    public function whenAny(array $conditions, $result): CaseBuilderInterface
    {
        $conditionStrings = [];
        foreach ($conditions as $condition => $value) {
            if (is_numeric($condition)) {
                // Simple condition string
                $conditionStrings[] = $value;
            } else {
                // Column => value mapping
                $conditionStrings[] = "`{$condition}` = ?";
                $this->addBinding($value);
            }
        }
        return $this->when('(' . implode(' OR ', $conditionStrings) . ')', $result);
    }

    /**
     * Multiple conditions with AND
     */
    public function whenAll(array $conditions, $result): CaseBuilderInterface
    {
        $conditionStrings = [];
        foreach ($conditions as $condition => $value) {
            if (is_numeric($condition)) {
                // Simple condition string
                $conditionStrings[] = $value;
            } else {
                // Column => value mapping
                $conditionStrings[] = "`{$condition}` = ?";
                $this->addBinding($value);
            }
        }
        return $this->when('(' . implode(' AND ', $conditionStrings) . ')', $result);
    }

    /**
     * Conditional WHEN based on callback
     */
    public function whenCondition(callable $callback, $result): CaseBuilderInterface
    {
        $condition = $callback($this);
        return $this->when($condition, $result);
    }

    public function else($value): CaseBuilderInterface
    {
        $this->elseClause = [
            'value' => $value,
            'bindings' => $this->extractBindings($value)
        ];
        return $this;
    }

    /**
     * Expressive else methods
     */
    public function elseNull(): CaseBuilderInterface
    {
        return $this->else('NULL');
    }

    public function elseValue($value): CaseBuilderInterface
    {
        return $this->else($value);
    }

    public function elseColumn(string $column): CaseBuilderInterface
    {
        return $this->else("`{$column}`");
    }

    public function elseConcat(string ...$columns): CaseBuilderInterface
    {
        $quotedColumns = array_map(fn($col) => "`{$col}`", $columns);
        return $this->else('CONCAT(' . implode(', ', $quotedColumns) . ')');
    }

    public function end(string $alias = null): string
    {
        $sql = $this->toSql();
        if ($alias) {
            $sql .= " AS `$alias`";
        }
        return $sql;
    }

    /**
     * Expressive static builders
     */
    public static function simple(string $column): CaseBuilderInterface
    {
        return (new self())->case($column);
    }

    public static function searched(): CaseBuilderInterface
    {
        return (new self())->case();
    }

    public static function status(string $column): CaseBuilderInterface
    {
        return (new self())->case($column);
    }

    public static function priority(string $column): CaseBuilderInterface
    {
        return (new self())->case($column);
    }

    public static function category(string $column): CaseBuilderInterface
    {
        return (new self())->case($column);
    }

    /**
     * Quick builders for common patterns
     */
    public static function booleanToText(string $column, string $trueText = 'Yes', string $falseText = 'No'): string
    {
        return (new self())->case($column)
            ->whenValue(1, $trueText)
            ->whenValue(0, $falseText)
            ->else($falseText)
            ->end();
    }

    public static function statusLabels(string $column, array $statusMap): string
    {
        $case = (new self())->case($column);
        foreach ($statusMap as $status => $label) {
            $case->whenValue($status, $label);
        }
        return $case->else('Unknown')->end();
    }

    public static function priceCategory(string $column, array $ranges): string
    {
        $case = (new self())->case();
        foreach ($ranges as $range => $label) {
            if (str_contains($range, '-')) {
                [$min, $max] = explode('-', $range);
                $case->whenBetween($column, (float)$min, (float)$max, $label);
            } elseif (str_starts_with($range, '>')) {
                $value = (float)substr($range, 1);
                $case->whenGreaterThan($column, $value, $label);
            } elseif (str_starts_with($range, '<')) {
                $value = (float)substr($range, 1);
                $case->whenLessThan($column, $value, $label);
            }
        }
        return $case->else('Other')->end();
    }

    public function reset(): CaseBuilderInterface
    {
        $this->caseColumn = null;
        $this->whenConditions = [];
        $this->bindings = [];
        $this->elseClause = null;
        $this->isSearched = false;
        return $this;
    }

    public function toSql(): string
    {
        if (empty($this->whenConditions)) {
            throw new \InvalidArgumentException('CASE statement must have at least one WHEN condition');
        }

        $sql = 'CASE';
        
        if (!$this->isSearched && $this->caseColumn) {
            $sql .= " `{$this->caseColumn}`";
        }

        foreach ($this->whenConditions as $when) {
            $sql .= " WHEN {$when['condition']} THEN ";
            $sql .= $this->formatValue($when['value']);
        }

        if ($this->elseClause !== null) {
            $sql .= ' ELSE ' . $this->formatValue($this->elseClause['value']);
        }

        $sql .= ' END';
        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Helper methods
     */
    protected function addBinding($value): self
    {
        if (!in_array($value, ['NULL', 'TRUE', 'FALSE']) && !is_numeric($value)) {
            $this->bindings[] = $value;
        }
        return $this;
    }

    protected function extractBindings($value): array
    {
        if (is_string($value) && !in_array($value, ['NULL', 'TRUE', 'FALSE']) && !str_starts_with($value, '`')) {
            return [$value];
        }
        return [];
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
            // Check if it's a column reference or function call
            if (str_starts_with($value, '`') || str_contains($value, '(')) {
                return $value;
            }
            // Otherwise it's a literal value that needs binding
            return '?';
        }
        
        return '?';
    }
}
