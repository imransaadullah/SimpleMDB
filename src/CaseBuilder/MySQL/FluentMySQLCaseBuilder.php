<?php

namespace SimpleMDB\CaseBuilder\MySQL;

use SimpleMDB\CaseBuilder\FluentCaseBuilder;

/**
 * FluentMySQLCaseBuilder
 * 
 * MySQL-specific fluent CASE builder with backtick quoting
 */
class FluentMySQLCaseBuilder extends FluentCaseBuilder
{
    public function __construct()
    {
        parent::__construct('`'); // MySQL uses backticks
    }

    /**
     * MySQL-specific fluent builders
     */
    public static function column(string $column): FluentMySQLWhenBuilder
    {
        return new FluentMySQLWhenBuilder(new self(), $column);
    }

    public static function simpleCase(string $column): FluentMySQLValueBuilder
    {
        $case = new self();
        $case->caseColumn = $column;
        $case->isSearched = false;
        return new FluentMySQLValueBuilder($case, $column);
    }
}

/**
 * FluentMySQLWhenBuilder - MySQL-specific when conditions
 */
class FluentMySQLWhenBuilder
{
    private FluentMySQLCaseBuilder $caseBuilder;
    private string $column;

    public function __construct(FluentMySQLCaseBuilder $caseBuilder, string $column)
    {
        $this->caseBuilder = $caseBuilder;
        $this->column = $column;
        $this->caseBuilder->case(); // Start searched CASE
    }

    public function equals($value): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` = ?";
        $this->caseBuilder->addBinding($value);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function notEquals($value): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` != ?";
        $this->caseBuilder->addBinding($value);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function greaterThan($value): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` > ?";
        $this->caseBuilder->addBinding($value);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function lessThan($value): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` < ?";
        $this->caseBuilder->addBinding($value);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function between($min, $max): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` BETWEEN ? AND ?";
        $this->caseBuilder->addBinding($min);
        $this->caseBuilder->addBinding($max);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function in(array $values): FluentMySQLThenBuilder
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $condition = "`{$this->column}` IN ({$placeholders})";
        foreach ($values as $value) {
            $this->caseBuilder->addBinding($value);
        }
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function like(string $pattern): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` LIKE ?";
        $this->caseBuilder->addBinding($pattern);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isNull(): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` IS NULL";
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isNotNull(): FluentMySQLThenBuilder
    {
        $condition = "`{$this->column}` IS NOT NULL";
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isActive(): FluentMySQLThenBuilder
    {
        return $this->equals(1);
    }

    public function isInactive(): FluentMySQLThenBuilder
    {
        return $this->equals(0);
    }

    public function isTrue(): FluentMySQLThenBuilder
    {
        return $this->equals(1);
    }

    public function isFalse(): FluentMySQLThenBuilder
    {
        return $this->equals(0);
    }
}

/**
 * FluentMySQLValueBuilder - Simple CASE value matching
 */
class FluentMySQLValueBuilder
{
    private FluentMySQLCaseBuilder $caseBuilder;
    private string $column;

    public function __construct(FluentMySQLCaseBuilder $caseBuilder, string $column)
    {
        $this->caseBuilder = $caseBuilder;
        $this->column = $column;
    }

    public function is($value): FluentMySQLThenBuilder
    {
        $condition = $this->caseBuilder->formatValue($value);
        return new FluentMySQLThenBuilder($this->caseBuilder, $condition);
    }

    public function isActive(): FluentMySQLThenBuilder
    {
        return $this->is(1);
    }

    public function isInactive(): FluentMySQLThenBuilder
    {
        return $this->is(0);
    }
}

/**
 * FluentMySQLThenBuilder - MySQL-specific then values
 */
class FluentMySQLThenBuilder
{
    private FluentMySQLCaseBuilder $caseBuilder;
    private string $condition;

    public function __construct(FluentMySQLCaseBuilder $caseBuilder, string $condition)
    {
        $this->caseBuilder = $caseBuilder;
        $this->condition = $condition;
    }

    public function then($value): FluentMySQLCaseBuilder
    {
        $this->caseBuilder->when($this->condition, $value);
        return $this->caseBuilder;
    }

    public function thenShow(string $text): FluentMySQLCaseBuilder
    {
        return $this->then($text);
    }

    public function thenColumn(string $column): FluentMySQLCaseBuilder
    {
        return $this->then("`{$column}`");
    }

    public function thenNull(): FluentMySQLCaseBuilder
    {
        return $this->then('NULL');
    }

    public function thenTrue(): FluentMySQLCaseBuilder
    {
        return $this->then('TRUE');
    }

    public function thenFalse(): FluentMySQLCaseBuilder
    {
        return $this->then('FALSE');
    }

    public function thenActive(): FluentMySQLCaseBuilder
    {
        return $this->then('Active');
    }

    public function thenInactive(): FluentMySQLCaseBuilder
    {
        return $this->then('Inactive');
    }

    public function thenConcat(string ...$columns): FluentMySQLCaseBuilder
    {
        $quotedColumns = array_map(fn($col) => "`{$col}`", $columns);
        return $this->then('CONCAT(' . implode(', ', $quotedColumns) . ')');
    }
}
