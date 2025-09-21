<?php

namespace SimpleMDB\CaseBuilder;

use SimpleMDB\Interfaces\CaseBuilderInterface;

/**
 * FluentCaseBuilder
 * 
 * Truly fluent CASE statement builder with natural language syntax
 */
class FluentCaseBuilder implements CaseBuilderInterface
{
    protected ?string $caseColumn = null;
    protected array $whenConditions = [];
    protected array $bindings = [];
    protected ?array $elseClause = null;
    protected bool $isSearched = false;
    public string $quoteChar = '`'; // MySQL default, override in PostgreSQL

    public function __construct(string $quoteChar = '`')
    {
        $this->quoteChar = $quoteChar;
    }

    // Fluent static builders
    public static function whenColumn(string $column): FluentWhenBuilder
    {
        return new FluentWhenBuilder(new self(), $column);
    }

    public static function value(string $column): FluentValueBuilder
    {
        $case = new self();
        $case->caseColumn = $column;
        $case->isSearched = false;
        return new FluentValueBuilder($case, $column);
    }

    // Traditional interface methods
    public function case(?string $column = null): CaseBuilderInterface
    {
        $this->caseColumn = $column;
        $this->isSearched = $column === null;
        return $this;
    }

    public function when(string $condition, $value): CaseBuilderInterface
    {
        $this->whenConditions[] = [
            'condition' => $condition,
            'value' => $value
        ];
        return $this;
    }

    public function else($value): CaseBuilderInterface
    {
        $this->elseClause = ['value' => $value];
        return $this;
    }

    public function end(?string $alias = null): string
    {
        $sql = $this->toSql();
        if ($alias) {
            $sql .= " AS {$this->quoteChar}{$alias}{$this->quoteChar}";
        }
        return $sql;
    }

    public function toSql(): string
    {
        if (empty($this->whenConditions)) {
            throw new \InvalidArgumentException('CASE statement must have at least one WHEN condition');
        }

        $sql = 'CASE';
        
        if (!$this->isSearched && $this->caseColumn) {
            $sql .= " {$this->quoteChar}{$this->caseColumn}{$this->quoteChar}";
        }

        foreach ($this->whenConditions as $when) {
            $sql .= " WHEN {$when['condition']} THEN {$this->formatValue($when['value'])}";
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

    public function reset(): CaseBuilderInterface
    {
        $this->caseColumn = null;
        $this->whenConditions = [];
        $this->bindings = [];
        $this->elseClause = null;
        $this->isSearched = false;
        return $this;
    }

    // Helper methods
    public function addBinding($value): self
    {
        if (!in_array($value, ['NULL', 'TRUE', 'FALSE']) && !is_numeric($value)) {
            $this->bindings[] = $value;
        }
        return $this;
    }

    public function formatValue($value): string
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
            if (str_starts_with($value, $this->quoteChar) || str_contains($value, '(')) {
                return $value;
            }
            return '?';
        }
        
        return '?';
    }

    // Stub methods for interface compatibility
    public function whenEquals(string $column, $value, $result): CaseBuilderInterface { return $this; }
    public function whenNotEquals(string $column, $value, $result): CaseBuilderInterface { return $this; }
    public function whenGreaterThan(string $column, $value, $result): CaseBuilderInterface { return $this; }
    public function whenLessThan(string $column, $value, $result): CaseBuilderInterface { return $this; }
    public function whenBetween(string $column, $min, $max, $result): CaseBuilderInterface { return $this; }
    public function whenIn(string $column, array $values, $result): CaseBuilderInterface { return $this; }
    public function whenLike(string $column, string $pattern, $result): CaseBuilderInterface { return $this; }
    public function whenNull(string $column, $result): CaseBuilderInterface { return $this; }
    public function whenNotNull(string $column, $result): CaseBuilderInterface { return $this; }
    public function whenValue($value, $result): CaseBuilderInterface { return $this; }
    public function whenAny(array $conditions, $result): CaseBuilderInterface { return $this; }
    public function whenAll(array $conditions, $result): CaseBuilderInterface { return $this; }
    public function elseNull(): CaseBuilderInterface { return $this; }
    public function elseValue($value): CaseBuilderInterface { return $this; }
    public function elseColumn(string $column): CaseBuilderInterface { return $this; }
    public static function simple(string $column): CaseBuilderInterface { return new self(); }
    public static function searched(): CaseBuilderInterface { return new self(); }
}

/**
 * FluentWhenBuilder - Handles "when column" fluent syntax
 */
class FluentWhenBuilder
{
    private FluentCaseBuilder $caseBuilder;
    private string $column;

    public function __construct(FluentCaseBuilder $caseBuilder, string $column)
    {
        $this->caseBuilder = $caseBuilder;
        $this->column = $column;
        $this->caseBuilder->case(); // Start searched CASE
    }

    public function equals($value): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} = ?";
        $this->caseBuilder->addBinding($value);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function notEquals($value): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} != ?";
        $this->caseBuilder->addBinding($value);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function greaterThan($value): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} > ?";
        $this->caseBuilder->addBinding($value);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function lessThan($value): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} < ?";
        $this->caseBuilder->addBinding($value);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function between($min, $max): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} BETWEEN ? AND ?";
        $this->caseBuilder->addBinding($min);
        $this->caseBuilder->addBinding($max);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function in(array $values): FluentThenBuilder
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} IN ({$placeholders})";
        foreach ($values as $value) {
            $this->caseBuilder->addBinding($value);
        }
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function like(string $pattern): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} LIKE ?";
        $this->caseBuilder->addBinding($pattern);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function isNull(): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} IS NULL";
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function isNotNull(): FluentThenBuilder
    {
        $condition = "{$this->caseBuilder->quoteChar}{$this->column}{$this->caseBuilder->quoteChar} IS NOT NULL";
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }
}

/**
 * FluentColumnBuilder - Handles simple CASE "value" syntax
 */
class FluentValueBuilder
{
    private FluentCaseBuilder $caseBuilder;
    private string $column;

    public function __construct(FluentCaseBuilder $caseBuilder, string $column)
    {
        $this->caseBuilder = $caseBuilder;
        $this->column = $column;
    }

    public function is($value): FluentThenBuilder
    {
        $condition = $this->caseBuilder->formatValue($value);
        return new FluentThenBuilder($this->caseBuilder, $condition);
    }

    public function isActive(): FluentThenBuilder
    {
        return $this->is(1);
    }

    public function isInactive(): FluentThenBuilder
    {
        return $this->is(0);
    }

    public function isTrue(): FluentThenBuilder
    {
        return $this->is(true);
    }

    public function isFalse(): FluentThenBuilder
    {
        return $this->is(false);
    }
}

/**
 * FluentThenBuilder - Handles the "then" part of CASE
 */
class FluentThenBuilder
{
    private FluentCaseBuilder $caseBuilder;
    private string $condition;

    public function __construct(FluentCaseBuilder $caseBuilder, string $condition)
    {
        $this->caseBuilder = $caseBuilder;
        $this->condition = $condition;
    }

    public function then($value): FluentCaseBuilder
    {
        $this->caseBuilder->when($this->condition, $value);
        return $this->caseBuilder;
    }

    public function thenValue($value): FluentCaseBuilder
    {
        return $this->then($value);
    }

    public function thenColumn(string $column): FluentCaseBuilder
    {
        return $this->then("{$this->caseBuilder->quoteChar}{$column}{$this->caseBuilder->quoteChar}");
    }

    public function thenNull(): FluentCaseBuilder
    {
        return $this->then('NULL');
    }

    public function thenTrue(): FluentCaseBuilder
    {
        return $this->then('TRUE');
    }

    public function thenFalse(): FluentCaseBuilder
    {
        return $this->then('FALSE');
    }

    public function thenConcat(string ...$columns): FluentCaseBuilder
    {
        $quotedColumns = array_map(fn($col) => "{$this->caseBuilder->quoteChar}{$col}{$this->caseBuilder->quoteChar}", $columns);
        return $this->then('CONCAT(' . implode(', ', $quotedColumns) . ')');
    }
}
