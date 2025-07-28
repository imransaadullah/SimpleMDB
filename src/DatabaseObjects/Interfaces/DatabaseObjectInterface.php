<?php

namespace SimpleMDB\DatabaseObjects\Interfaces;

use SimpleMDB\DatabaseInterface;

/**
 * Base interface for all database objects
 */
interface DatabaseObjectInterface
{
    /**
     * Create the database object
     */
    public function create(): bool;

    /**
     * Drop the database object
     */
    public function drop(): bool;

    /**
     * Check if the object exists
     */
    public function exists(): bool;

    /**
     * Generate the SQL for creating the object
     */
    public function generateCreateSql(): string;

    /**
     * Get the object name
     */
    public function getName(): string;

    /**
     * Get the database connection
     */
    public function getDatabase(): DatabaseInterface;
} 