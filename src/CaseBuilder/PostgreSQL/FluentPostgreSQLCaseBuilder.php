<?php

namespace SimpleMDB\CaseBuilder\PostgreSQL;

use SimpleMDB\CaseBuilder\FluentCaseBuilder;

/**
 * FluentPostgreSQLCaseBuilder
 * 
 * PostgreSQL-specific fluent CASE builder with double-quote quoting and advanced features
 */
class FluentPostgreSQLCaseBuilder extends FluentCaseBuilder
{
    public function __construct()
    {
        parent::__construct('"'); // PostgreSQL uses double quotes
    }

    /**
     * PostgreSQL-specific fluent builders
     */
    public static function column(string $column): FluentPostgreSQLWhenBuilder
    {
        return new FluentPostgreSQLWhenBuilder(new self(), $column);
    }

    public static function simpleCase(string $column): FluentPostgreSQLValueBuilder
    {
        $case = new self();
        $case->caseColumn = $column;
        $case->isSearched = false;
        return new FluentPostgreSQLValueBuilder($case, $column);
    }
}

/**
 * FluentPostgreSQLWhenBuilder - PostgreSQL-specific when conditions
 */
class FluentPostgreSQLWhenBuilder
{
    private FluentPostgreSQLCaseBuilder $caseBuilder;
    private string $column;

    public function __construct(FluentPostgreSQLCaseBuilder $caseBuilder, string $column)
    {
        $this->caseBuilder = $caseBuilder;
        $this->column = $column;
        $this->caseBuilder->case(); // Start searched CASE
    }

    public function equals($value): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" = ?";
        $this->caseBuilder->addBinding($value);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function notEquals($value): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" != ?";
        $this->caseBuilder->addBinding($value);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function greaterThan($value): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" > ?";
        $this->caseBuilder->addBinding($value);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function lessThan($value): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" < ?";
        $this->caseBuilder->addBinding($value);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function between($min, $max): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" BETWEEN ? AND ?";
        $this->caseBuilder->addBinding($min);
        $this->caseBuilder->addBinding($max);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function in(array $values): FluentPostgreSQLThenBuilder
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $condition = "\"{$this->column}\" IN ({$placeholders})";
        foreach ($values as $value) {
            $this->caseBuilder->addBinding($value);
        }
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function like(string $pattern): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" LIKE ?";
        $this->caseBuilder->addBinding($pattern);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function ilike(string $pattern): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" ILIKE ?";
        $this->caseBuilder->addBinding($pattern);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isNull(): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" IS NULL";
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isNotNull(): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" IS NOT NULL";
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    // PostgreSQL-specific conditions
    public function hasJsonbKey(string $key): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" ? ?";
        $this->caseBuilder->addBinding($key);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function containsJsonb(array $data): FluentPostgreSQLThenBuilder
    {
        $condition = "\"{$this->column}\" @> ?";
        $this->caseBuilder->addBinding(json_encode($data));
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function containsArrayValue($value): FluentPostgreSQLThenBuilder
    {
        $condition = "? = ANY(\"{$this->column}\")";
        $this->caseBuilder->addBinding($value);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function arrayHasLength(int $length): FluentPostgreSQLThenBuilder
    {
        $condition = "array_length(\"{$this->column}\", 1) = ?";
        $this->caseBuilder->addBinding($length);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isActive(): FluentPostgreSQLThenBuilder
    {
        return $this->equals(true);
    }

    public function isInactive(): FluentPostgreSQLThenBuilder
    {
        return $this->equals(false);
    }
}

/**
 * FluentPostgreSQLValueBuilder - PostgreSQL simple CASE values
 */
class FluentPostgreSQLValueBuilder
{
    private FluentPostgreSQLCaseBuilder $caseBuilder;
    private string $column;

    public function __construct(FluentPostgreSQLCaseBuilder $caseBuilder, string $column)
    {
        $this->caseBuilder = $caseBuilder;
        $this->column = $column;
    }

    public function is($value): FluentPostgreSQLThenBuilder
    {
        $condition = $this->caseBuilder->formatValue($value);
        return new FluentPostgreSQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isActive(): FluentPostgreSQLThenBuilder
    {
        return $this->is(true);
    }

    public function isInactive(): FluentPostgreSQLThenBuilder
    {
        return $this->is(false);
    }

    public function isTrue(): FluentPostgreSQLThenBuilder
    {
        return $this->is(true);
    }

    public function isFalse(): FluentPostgreSQLThenBuilder
    {
        return $this->is(false);
    }
}

/**
 * FluentPostgreSQLThenBuilder - PostgreSQL-specific then values
 */
class FluentPostgreSQLThenBuilder
{
    private FluentPostgreSQLCaseBuilder $caseBuilder;
    private string $condition;

    public function __construct(FluentPostgreSQLCaseBuilder $caseBuilder, string $condition)
    {
        $this->caseBuilder = $caseBuilder;
        $this->condition = $condition;
    }

    public function then($value): FluentPostgreSQLCaseBuilder
    {
        $this->caseBuilder->when($this->condition, $value);
        return $this->caseBuilder;
    }

    public function thenShow(string $text): FluentPostgreSQLCaseBuilder
    {
        return $this->then($text);
    }

    public function thenColumn(string $column): FluentPostgreSQLCaseBuilder
    {
        return $this->then("\"{$column}\"");
    }

    public function thenNull(): FluentPostgreSQLCaseBuilder
    {
        return $this->then('NULL');
    }

    public function thenTrue(): FluentPostgreSQLCaseBuilder
    {
        return $this->then('TRUE');
    }

    public function thenFalse(): FluentPostgreSQLCaseBuilder
    {
        return $this->then('FALSE');
    }

    public function thenActive(): FluentPostgreSQLCaseBuilder
    {
        return $this->then('Active');
    }

    public function thenInactive(): FluentPostgreSQLCaseBuilder
    {
        return $this->then('Inactive');
    }

    public function thenConcat(string ...$columns): FluentPostgreSQLCaseBuilder
    {
        $quotedColumns = array_map(fn($col) => "\"{$col}\"", $columns);
        return $this->then('CONCAT(' . implode(', ', $quotedColumns) . ')');
    }

    // PostgreSQL-specific then methods
    public function thenJsonbValue(array $data): FluentPostgreSQLCaseBuilder
    {
        return $this->then("'" . json_encode($data) . "'::jsonb");
    }

    public function thenArray(array $values): FluentPostgreSQLCaseBuilder
    {
        $formattedValues = implode(',', array_map(fn($v) => "'" . addslashes($v) . "'", $values));
        return $this->then("ARRAY[{$formattedValues}]");
    }
}
