<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\DatabaseObjects\Interfaces\FunctionInterface;
use SimpleMDB\DatabaseObjects\Interfaces\ProcedureInterface;
use SimpleMDB\DatabaseObjects\Interfaces\ViewInterface;
use SimpleMDB\DatabaseObjects\Interfaces\EventInterface;
use SimpleMDB\DatabaseObjects\Interfaces\TriggerInterface;

/**
 * Interface for database object manager operations
 */
interface DatabaseObjectManagerInterface
{
    /**
     * Create a new database object manager instance
     */
    public function __construct(DatabaseInterface $db);

    /**
     * Create function builder
     */
    public function function(string $functionName): FunctionInterface;

    /**
     * Create procedure builder
     */
    public function procedure(string $procedureName): ProcedureInterface;

    /**
     * Create view builder
     */
    public function view(string $viewName): ViewInterface;

    /**
     * Create event builder
     */
    public function event(string $eventName): EventInterface;

    /**
     * Create trigger builder
     */
    public function trigger(string $triggerName): TriggerInterface;

    /**
     * Get all functions in the database
     */
    public function getFunctions(): array;

    /**
     * Get all procedures in the database
     */
    public function getProcedures(): array;

    /**
     * Get all views in the database
     */
    public function getViews(): array;

    /**
     * Get all events in the database
     */
    public function getEvents(): array;

    /**
     * Get all triggers in the database
     */
    public function getTriggers(): array;

    /**
     * Get all database objects
     */
    public function getAllObjects(): array;

    /**
     * Drop all functions
     */
    public function dropAllFunctions(): bool;

    /**
     * Drop all procedures
     */
    public function dropAllProcedures(): bool;

    /**
     * Drop all views
     */
    public function dropAllViews(): bool;

    /**
     * Drop all events
     */
    public function dropAllEvents(): bool;

    /**
     * Drop all triggers
     */
    public function dropAllTriggers(): bool;

    /**
     * Drop all database objects
     */
    public function dropAllObjects(): bool;

    /**
     * Check if database has any objects
     */
    public function hasObjects(): bool;

    /**
     * Get object counts by type
     */
    public function getObjectCounts(): array;

    /**
     * Get database connection
     */
    public function getDatabase(): DatabaseInterface;

    /**
     * Set database connection
     */
    public function setDatabase(DatabaseInterface $db): self;

    /**
     * Get object by name and type
     */
    public function getObject(string $name, string $type): ?array;

    /**
     * Check if object exists
     */
    public function objectExists(string $name, string $type): bool;

    /**
     * Get object definition
     */
    public function getObjectDefinition(string $name, string $type): ?string;

    /**
     * Get object information
     */
    public function getObjectInfo(string $name, string $type): ?array;

    /**
     * Drop object by name and type
     */
    public function dropObject(string $name, string $type): bool;

    /**
     * Rename object
     */
    public function renameObject(string $oldName, string $newName, string $type): bool;

    /**
     * Get supported object types
     */
    public function getSupportedObjectTypes(): array;

    /**
     * Validate object name
     */
    public function validateObjectName(string $name, string $type): bool;

    /**
     * Get object dependencies
     */
    public function getObjectDependencies(string $name, string $type): array;
} 