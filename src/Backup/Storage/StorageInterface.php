<?php

namespace SimpleMDB\Backup\Storage;

/**
 * Interface for backup storage adapters
 */
interface StorageInterface
{
    /**
     * Store backup data
     */
    public function store(string $path, $data, array $metadata = []): string;
    
    /**
     * Retrieve backup data
     */
    public function retrieve(string $id): ?string;
    
    /**
     * Delete backup
     */
    public function delete(string $id): bool;
    
    /**
     * Check if backup exists
     */
    public function exists(string $id): bool;
    
    /**
     * Get backup metadata
     */
    public function getMetadata(string $id): array;
    
    /**
     * List all backups
     */
    public function list(): array;
    
    /**
     * Get storage statistics
     */
    public function getStats(): array;
} 