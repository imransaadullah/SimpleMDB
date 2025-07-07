<?php

namespace SimpleMDB;

class Expression
{
    private string $expression;
    private array $bindings;

    public function __construct(string $expression, array $bindings = [])
    {
        $this->expression = $expression;
        $this->bindings = $bindings;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public static function raw(string $expression, array $bindings = []): self
    {
        return new self($expression, $bindings);
    }

    public static function case(): CaseBuilder
    {
        return new CaseBuilder();
    }

    public static function exists(SimpleQuery $query): self
    {
        return new self("EXISTS (" . $query->toSql() . ")", $query->getParams());
    }

    public static function notExists(SimpleQuery $query): self
    {
        return new self("NOT EXISTS (" . $query->toSql() . ")", $query->getParams());
    }

    public function __toString(): string
    {
        return $this->expression;
    }
} 