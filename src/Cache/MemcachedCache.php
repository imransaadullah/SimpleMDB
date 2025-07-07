<?php

namespace SimpleMDB\Cache;

use SimpleMDB\CacheInterface;
use SimpleMDB\Traits\LoggerAwareTrait;

/**
 * Memcached cache implementation
 */
class MemcachedCache implements CacheInterface
{
    use LoggerAwareTrait;

    private object $memcached;
    private string $prefix;
    private int $defaultTtl;

    public function __construct(
        array $servers = [['127.0.0.1', 11211]],
        string $prefix = 'simplemdb:',
        int $defaultTtl = 3600,
        array $options = []
    ) {
        if (!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not installed');
        }
        
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
        
        $this->memcached = new \Memcached();
        
        // Set options
        $defaultOptions = [
            \Memcached::OPT_COMPRESSION => true,
            \Memcached::OPT_SERIALIZER => \Memcached::SERIALIZER_PHP,
            \Memcached::OPT_CONNECT_TIMEOUT => 1000,
            \Memcached::OPT_SEND_TIMEOUT => 1000,
            \Memcached::OPT_RECV_TIMEOUT => 1000,
            \Memcached::OPT_RETRY_TIMEOUT => 60,
            \Memcached::OPT_BINARY_PROTOCOL => true,
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_TCP_NODELAY => true,
            \Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        foreach ($options as $option => $value) {
            $this->memcached->setOption($option, $value);
        }
        
        // Add servers
        foreach ($servers as $server) {
            $host = $server[0];
            $port = $server[1] ?? 11211;
            $weight = $server[2] ?? 0;
            
            $this->memcached->addServer($host, $port, $weight);
        }
        
        $this->log('info', 'Connected to Memcached', [
            'servers' => $servers,
            'prefix' => $prefix
        ]);
    }

    public function get(string $key)
    {
        $prefixedKey = $this->prefixKey($key);
        $value = $this->memcached->get($prefixedKey);
        
        if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
            $this->log('debug', 'Cache hit', ['key' => $key]);
            return $value;
        }
        
        $this->log('debug', 'Cache miss', [
            'key' => $key,
            'result_code' => $this->memcached->getResultCode()
        ]);
        
        return null;
    }

    public function set(string $key, $value, int $ttl = null): bool
    {
        $prefixedKey = $this->prefixKey($key);
        $ttl = $ttl ?? $this->defaultTtl;
        
        $result = $this->memcached->set($prefixedKey, $value, $ttl);
        
        if ($result) {
            $this->log('debug', 'Cache set', [
                'key' => $key,
                'ttl' => $ttl,
                'size' => strlen(serialize($value))
            ]);
        } else {
            $this->log('error', 'Cache set failed', [
                'key' => $key,
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
        }
        
        return $result;
    }

    public function delete(string $key): bool
    {
        $prefixedKey = $this->prefixKey($key);
        $result = $this->memcached->delete($prefixedKey);
        
        if ($result) {
            $this->log('debug', 'Cache delete', ['key' => $key]);
        } else {
            $this->log('error', 'Cache delete failed', [
                'key' => $key,
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
        }
        
        return $result;
    }

    public function clear(): bool
    {
        $result = $this->memcached->flush();
        
        if ($result) {
            $this->log('info', 'Cache cleared');
        } else {
            $this->log('error', 'Cache clear failed', [
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
        }
        
        return $result;
    }

    public function has(string $key): bool
    {
        $prefixedKey = $this->prefixKey($key);
        $this->memcached->get($prefixedKey);
        
        return $this->memcached->getResultCode() === Memcached::RES_SUCCESS;
    }

    /**
     * Get multiple keys at once
     */
    public function getMultiple(array $keys): array
    {
        $prefixedKeys = [];
        $keyMap = [];
        
        foreach ($keys as $key) {
            $prefixedKey = $this->prefixKey($key);
            $prefixedKeys[] = $prefixedKey;
            $keyMap[$prefixedKey] = $key;
        }
        
        $values = $this->memcached->getMulti($prefixedKeys);
        
        if ($this->memcached->getResultCode() !== Memcached::RES_SUCCESS) {
            $this->log('error', 'getMultiple failed', [
                'keys' => $keys,
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
            return [];
        }
        
        $result = [];
        foreach ($values as $prefixedKey => $value) {
            $originalKey = $keyMap[$prefixedKey];
            $result[$originalKey] = $value;
        }
        
        return $result;
    }

    /**
     * Set multiple keys at once
     */
    public function setMultiple(array $items, int $ttl = null): bool
    {
        $prefixedItems = [];
        $ttl = $ttl ?? $this->defaultTtl;
        
        foreach ($items as $key => $value) {
            $prefixedKey = $this->prefixKey($key);
            $prefixedItems[$prefixedKey] = $value;
        }
        
        $result = $this->memcached->setMulti($prefixedItems, $ttl);
        
        if ($result) {
            $this->log('debug', 'setMultiple success', [
                'count' => count($items),
                'ttl' => $ttl
            ]);
        } else {
            $this->log('error', 'setMultiple failed', [
                'count' => count($items),
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
        }
        
        return $result;
    }

    /**
     * Delete multiple keys at once
     */
    public function deleteMultiple(array $keys): bool
    {
        $prefixedKeys = array_map([$this, 'prefixKey'], $keys);
        $result = $this->memcached->deleteMulti($prefixedKeys);
        
        if ($result) {
            $this->log('debug', 'deleteMultiple success', [
                'count' => count($keys)
            ]);
        } else {
            $this->log('error', 'deleteMultiple failed', [
                'count' => count($keys),
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
        }
        
        return $result;
    }

    /**
     * Increment a numeric value
     */
    public function increment(string $key, int $value = 1): int
    {
        $prefixedKey = $this->prefixKey($key);
        $result = $this->memcached->increment($prefixedKey, $value);
        
        if ($result === false) {
            $this->log('error', 'Increment failed', [
                'key' => $key,
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
            return 0;
        }
        
        return $result;
    }

    /**
     * Decrement a numeric value
     */
    public function decrement(string $key, int $value = 1): int
    {
        $prefixedKey = $this->prefixKey($key);
        $result = $this->memcached->decrement($prefixedKey, $value);
        
        if ($result === false) {
            $this->log('error', 'Decrement failed', [
                'key' => $key,
                'result_code' => $this->memcached->getResultCode(),
                'result_message' => $this->memcached->getResultMessage()
            ]);
            return 0;
        }
        
        return $result;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = $this->memcached->getStats();
        
        if (empty($stats)) {
            return [
                'type' => 'memcached',
                'error' => 'Unable to retrieve stats'
            ];
        }
        
        $totalStats = [
            'type' => 'memcached',
            'servers' => count($stats),
            'total_items' => 0,
            'total_connections' => 0,
            'total_gets' => 0,
            'total_sets' => 0,
            'total_hits' => 0,
            'total_misses' => 0,
            'total_bytes' => 0,
            'total_bytes_read' => 0,
            'total_bytes_written' => 0,
            'server_details' => []
        ];
        
        foreach ($stats as $server => $serverStats) {
            $totalStats['total_items'] += $serverStats['curr_items'] ?? 0;
            $totalStats['total_connections'] += $serverStats['curr_connections'] ?? 0;
            $totalStats['total_gets'] += $serverStats['cmd_get'] ?? 0;
            $totalStats['total_sets'] += $serverStats['cmd_set'] ?? 0;
            $totalStats['total_hits'] += $serverStats['get_hits'] ?? 0;
            $totalStats['total_misses'] += $serverStats['get_misses'] ?? 0;
            $totalStats['total_bytes'] += $serverStats['bytes'] ?? 0;
            $totalStats['total_bytes_read'] += $serverStats['bytes_read'] ?? 0;
            $totalStats['total_bytes_written'] += $serverStats['bytes_written'] ?? 0;
            
            $totalStats['server_details'][$server] = $serverStats;
        }
        
        // Calculate hit rate
        $totalRequests = $totalStats['total_hits'] + $totalStats['total_misses'];
        $totalStats['hit_rate'] = $totalRequests > 0 ? 
            round(($totalStats['total_hits'] / $totalRequests) * 100, 2) : 0;
        
        return $totalStats;
    }

    /**
     * Get the Memcached instance for advanced operations
     */
    public function getMemcached(): Memcached
    {
        return $this->memcached;
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        $this->memcached->quit();
        $this->log('info', 'Memcached connection closed');
    }

    /**
     * Prefix a key with the configured prefix
     */
    private function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }
} 