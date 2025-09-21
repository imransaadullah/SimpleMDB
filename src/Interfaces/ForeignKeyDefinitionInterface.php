<?php

namespace SimpleMDB\Interfaces;

/**
 * ForeignKeyDefinitionInterface
 * 
 * Defines the contract for foreign key definitions across different database engines.
 */
interface ForeignKeyDefinitionInterface
{
    /**
     * Set the referenced table and column
     */
    public function references(string $column): self;

    /**
     * Set the referenced table
     */
    public function on(string $table): self;

    /**
     * Set the ON DELETE action
     */
    public function onDelete(string $action): self;

    /**
     * Set the ON UPDATE action
     */
    public function onUpdate(string $action): self;

    /**
     * Set foreign key name
     */
    public function name(string $name): self;
}

