<?php

namespace SimpleMDB\Interfaces;

use SimpleMDB\CacheInterface;

/**
 * Interface for cache manager operations
 */
interface CacheManagerInterface
{
    /**
     * Create a new cache manager instance
     */
    public function __construct(CacheInterface $cache);

    /**
     * Get a value from cache
     */
    public function get(string $key);

    /**
     * Set a value in cache with tags
     */
    public function set(string $key, $value, array $tags = [], int $ttl = 3600): bool;

    /**
     * Invalidate cache by tag
     */
    public function invalidateTag(string $tag): void;

    /**
     * Invalidate cache by multiple tags
     */
    public function invalidateTags(array $tags): void;

    /**
     * Delete a value from cache
     */
    public function delete(string $key): bool;

    /**
     * Clear all cache
     */
    public function clear(): bool;

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool;

    /**
     * Get cache statistics
     */
    public function getStats(): array;

    /**
     * Get cache tags
     */
    public function getTags(): array;

    /**
     * Get cache keys by tag
     */
    public function getKeysByTag(string $tag): array;

    /**
     * Get cache keys by multiple tags
     */
    public function getKeysByTags(array $tags): array;

    /**
     * Get cache manager configuration
     */
    public function getConfig(): array;

    /**
     * Set cache manager configuration
     */
    public function setConfig(array $config): self;

    /**
     * Get underlying cache instance
     */
    public function getCache(): CacheInterface;

    /**
     * Set underlying cache instance
     */
    public function setCache(CacheInterface $cache): self;

    /**
     * Check if cache manager is enabled
     */
    public function isEnabled(): bool;

    /**
     * Enable/disable cache manager
     */
    public function setEnabled(bool $enabled): self;
} 