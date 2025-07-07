<?php

namespace SimpleMDB;

class CaseBuilder
{
    private array $whens = [];
    private $else = null;
    private array $bindings = [];

    public function when($condition, $then): self
    {
        if ($condition instanceof Expression) {
            $this->whens[] = [
                'condition' => $condition->getExpression(),
                'then' => $then instanceof Expression ? $then->getExpression() : '?',
            ];
            $this->bindings = array_merge(
                $this->bindings,
                $condition->getBindings(),
                $then instanceof Expression ? $then->getBindings() : [$then]
            );
        } else {
            $this->whens[] = [
                'condition' => '?',
                'then' => $then instanceof Expression ? $then->getExpression() : '?',
            ];
            $this->bindings[] = $condition;
            if (!($then instanceof Expression)) {
                $this->bindings[] = $then;
            } else {
                $this->bindings = array_merge($this->bindings, $then->getBindings());
            }
        }
        return $this;
    }

    public function else($value): self
    {
        if ($value instanceof Expression) {
            $this->else = $value->getExpression();
            $this->bindings = array_merge($this->bindings, $value->getBindings());
        } else {
            $this->else = '?';
            $this->bindings[] = $value;
        }
        return $this;
    }

    public function end(): Expression
    {
        $sql = 'CASE';
        foreach ($this->whens as $when) {
            $sql .= " WHEN {$when['condition']} THEN {$when['then']}";
        }
        if ($this->else !== null) {
            $sql .= " ELSE {$this->else}";
        }
        $sql .= ' END';

        return new Expression($sql, $this->bindings);
    }
} 