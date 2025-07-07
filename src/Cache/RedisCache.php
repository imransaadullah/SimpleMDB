<?php

namespace SimpleMDB\Cache;

use SimpleMDB\CacheInterface;
use SimpleMDB\Traits\LoggerAwareTrait;
use Redis;
use RedisException;

/**
 * Redis cache implementation
 */
class RedisCache implements CacheInterface
{
    use LoggerAwareTrait;

    private Redis $redis;
    private string $prefix;
    private array $tagMap = [];
    private int $defaultTtl;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $password = '',
        int $database = 0,
        string $prefix = 'simplemdb:',
        int $defaultTtl = 3600
    ) {
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
        
        $this->redis = new Redis();
        
        try {
            $this->redis->connect($host, $port);
            
            if (!empty($password)) {
                $this->redis->auth($password);
            }
            
            if ($database > 0) {
                $this->redis->select($database);
            }
            
            $this->log('info', 'Connected to Redis', [
                'host' => $host,
                'port' => $port,
                'database' => $database
            ]);
            
        } catch (RedisException $e) {
            $this->log('error', 'Failed to connect to Redis', [
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function get(string $key)
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $data = $this->redis->get($prefixedKey);
            
            if ($data === false) {
                $this->log('debug', 'Cache miss', ['key' => $key]);
                return null;
            }
            
            $decoded = $this->decode($data);
            $this->log('debug', 'Cache hit', ['key' => $key]);
            
            return $decoded;
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis get error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function set(string $key, $value, int $ttl = null): bool
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $encoded = $this->encode($value);
            $ttl = $ttl ?? $this->defaultTtl;
            
            $result = $this->redis->setex($prefixedKey, $ttl, $encoded);
            
            if ($result) {
                $this->log('debug', 'Cache set', [
                    'key' => $key,
                    'ttl' => $ttl,
                    'size' => strlen($encoded)
                ]);
            }
            
            return $result;
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis set error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $result = $this->redis->del($prefixedKey) > 0;
            
            // Remove from tag mappings
            $this->removeFromTagMappings($key);
            
            if ($result) {
                $this->log('debug', 'Cache delete', ['key' => $key]);
            }
            
            return $result;
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis delete error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            // Get all keys with our prefix
            $pattern = $this->prefix . '*';
            $keys = $this->redis->keys($pattern);
            
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
            
            $this->tagMap = [];
            
            $this->log('info', 'Cache cleared', [
                'keys_deleted' => count($keys)
            ]);
            
            return true;
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis clear error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            return $this->redis->exists($prefixedKey) > 0;
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis exists error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Set cache value with tags
     */
    public function setWithTags(string $key, $value, array $tags = [], int $ttl = null): bool
    {
        $result = $this->set($key, $value, $ttl);
        
        if ($result && !empty($tags)) {
            $this->setTagMappings($key, $tags, $ttl);
        }
        
        return $result;
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTag(string $tag): int
    {
        try {
            $tagKey = $this->prefixKey("tags:$tag");
            $keys = $this->redis->sMembers($tagKey);
            
            if (empty($keys)) {
                return 0;
            }
            
            // Delete all keys associated with this tag
            $prefixedKeys = array_map([$this, 'prefixKey'], $keys);
            $deleted = $this->redis->del($prefixedKeys);
            
            // Remove the tag set itself
            $this->redis->del($tagKey);
            
            // Clean up tag mappings
            foreach ($keys as $key) {
                $this->removeFromTagMappings($key);
            }
            
            $this->log('info', 'Tag invalidated', [
                'tag' => $tag,
                'keys_deleted' => $deleted
            ]);
            
            return $deleted;
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis tag invalidation error', [
                'tag' => $tag,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $info = $this->redis->info();
            
            return [
                'type' => 'redis',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
                'redis_version' => $info['redis_version'] ?? 'unknown'
            ];
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis stats error', [
                'error' => $e->getMessage()
            ]);
            return ['type' => 'redis', 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute Redis pipeline for batch operations
     */
    public function pipeline(callable $callback): array
    {
        try {
            $pipe = $this->redis->pipeline();
            $callback($pipe);
            return $pipe->exec();
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis pipeline error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Increment a value
     */
    public function increment(string $key, int $value = 1): int
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            return $this->redis->incrBy($prefixedKey, $value);
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis increment error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Decrement a value
     */
    public function decrement(string $key, int $value = 1): int
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            return $this->redis->decrBy($prefixedKey, $value);
            
        } catch (RedisException $e) {
            $this->log('error', 'Redis decrement error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get Redis instance for advanced operations
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * Close Redis connection
     */
    public function close(): void
    {
        try {
            $this->redis->close();
            $this->log('info', 'Redis connection closed');
            
        } catch (RedisException $e) {
            $this->log('error', 'Error closing Redis connection', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Prefix a key with the configured prefix
     */
    private function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Encode value for storage
     */
    private function encode($value): string
    {
        return serialize($value);
    }

    /**
     * Decode value from storage
     */
    private function decode(string $data)
    {
        return unserialize($data);
    }

    /**
     * Set tag mappings for a key
     */
    private function setTagMappings(string $key, array $tags, ?int $ttl): void
    {
        try {
            foreach ($tags as $tag) {
                $tagKey = $this->prefixKey("tags:$tag");
                $this->redis->sAdd($tagKey, $key);
                
                // Set expiration on tag key if TTL specified
                if ($ttl !== null) {
                    $this->redis->expire($tagKey, $ttl + 60); // Add buffer to tag TTL
                }
            }
            
            $this->tagMap[$key] = $tags;
            
        } catch (RedisException $e) {
            $this->log('error', 'Error setting tag mappings', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove key from tag mappings
     */
    private function removeFromTagMappings(string $key): void
    {
        if (!isset($this->tagMap[$key])) {
            return;
        }
        
        try {
            foreach ($this->tagMap[$key] as $tag) {
                $tagKey = $this->prefixKey("tags:$tag");
                $this->redis->sRem($tagKey, $key);
            }
            
            unset($this->tagMap[$key]);
            
        } catch (RedisException $e) {
            $this->log('error', 'Error removing tag mappings', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }
} 