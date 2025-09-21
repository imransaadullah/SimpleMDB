<?php

namespace SimpleMDB\Schema\PostgreSQL;

use SimpleMDB\Interfaces\ForeignKeyDefinitionInterface;

/**
 * PostgreSQLForeignKeyDefinition
 * 
 * PostgreSQL-specific implementation of foreign key definitions
 */
class PostgreSQLForeignKeyDefinition implements ForeignKeyDefinitionInterface
{
    private PostgreSQLSchemaBuilder $schemaBuilder;
    private string $column;
    private ?string $referencedTable = null;
    private ?string $referencedColumn = null;
    private ?string $onDelete = null;
    private ?string $onUpdate = null;
    private ?string $name = null;

    public function __construct(PostgreSQLSchemaBuilder $schemaBuilder, string $column)
    {
        $this->schemaBuilder = $schemaBuilder;
        $this->column = $column;
    }

    public function references(string $column): ForeignKeyDefinitionInterface
    {
        $this->referencedColumn = $column;
        return $this;
    }

    public function on(string $table): ForeignKeyDefinitionInterface
    {
        $this->referencedTable = $table;
        
        // Add the foreign key to the schema builder
        $this->schemaBuilder->addForeignKey([
            'column' => $this->column,
            'reference_table' => $this->referencedTable,
            'reference_column' => $this->referencedColumn,
            'on_delete' => $this->onDelete,
            'on_update' => $this->onUpdate,
            'name' => $this->name
        ]);
        
        return $this;
    }

    public function onDelete(string $action): ForeignKeyDefinitionInterface
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function onUpdate(string $action): ForeignKeyDefinitionInterface
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    public function name(string $name): ForeignKeyDefinitionInterface
    {
        $this->name = $name;
        return $this;
    }
}

