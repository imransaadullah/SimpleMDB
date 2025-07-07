<?php

namespace SimpleMDB;

use SimpleMDB\Traits\LoggerAwareTrait;

interface CacheInterface
{
    public function get(string $key);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}

class MemoryCache implements CacheInterface
{
    private array $cache = [];
    private array $expiration = [];

    public function get(string $key)
    {
        if (!$this->has($key)) {
            return null;
        }
        return $this->cache[$key];
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $this->cache[$key] = $value;
        $this->expiration[$key] = time() + $ttl;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expiration[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        $this->expiration = [];
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->cache[$key]) || !isset($this->expiration[$key])) {
            return false;
        }
        if (time() > $this->expiration[$key]) {
            $this->delete($key);
            return false;
        }
        return true;
    }
}

class FileCache implements CacheInterface
{
    use LoggerAwareTrait;

    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/') . '/';
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    private function getFilename(string $key): string
    {
        return $this->directory . md5($key) . '.cache';
    }

    public function get(string $key)
    {
        if (!$this->has($key)) {
            return null;
        }
        $filename = $this->getFilename($key);
        $data = unserialize(file_get_contents($filename));
        $value = $data['value'];
        $this->log('debug','Cache get', ['key'=>$key,'hit'=>$this->has($key)]);
        return $value;
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $filename = $this->getFilename($key);
        $data = [
            'value' => $value,
            'expiration' => time() + $ttl
        ];
        $success = file_put_contents($filename, serialize($data)) !== false;
        $this->log('debug','Cache set',['key'=>$key,'ttl'=>$ttl]);
        return $success;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->directory . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function has(string $key): bool
    {
        $filename = $this->getFilename($key);
        if (!file_exists($filename)) {
            return false;
        }
        $data = unserialize(file_get_contents($filename));
        if (time() > $data['expiration']) {
            $this->delete($key);
            return false;
        }
        return true;
    }
}

class CacheManager
{
    use LoggerAwareTrait;

    private CacheInterface $cache;
    private array $queryTags = [];
    private array $taggedKeys = [];

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key)
    {
        $value = $this->cache->get($key);
        $this->log('debug','Cache get', ['key'=>$key,'hit'=>$this->cache->has($key)]);
        return $value;
    }

    public function set(string $key, $value, array $tags = [], int $ttl = 3600): bool
    {
        $success = $this->cache->set($key, $value, $ttl);
        if ($success && !empty($tags)) {
            foreach ($tags as $tag) {
                $this->queryTags[$tag][] = $key;
                $this->taggedKeys[$key][] = $tag;
            }
        }
        $this->log('debug','Cache set',['key'=>$key,'tags'=>$tags,'ttl'=>$ttl]);
        return $success;
    }

    public function invalidateTag(string $tag): void
    {
        if (isset($this->queryTags[$tag])) {
            $this->log('debug','Cache invalidate tag',['tag'=>$tag]);
            foreach ($this->queryTags[$tag] as $key) {
                $this->cache->delete($key);
                if (isset($this->taggedKeys[$key])) {
                    foreach ($this->taggedKeys[$key] as $relatedTag) {
                        $index = array_search($key, $this->queryTags[$relatedTag]);
                        if ($index !== false) {
                            unset($this->queryTags[$relatedTag][$index]);
                        }
                    }
                    unset($this->taggedKeys[$key]);
                }
            }
            unset($this->queryTags[$tag]);
        }
    }

    public function invalidateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->invalidateTag($tag);
        }
    }

    public function delete(string $key): bool
    {
        if (isset($this->taggedKeys[$key])) {
            foreach ($this->taggedKeys[$key] as $tag) {
                $index = array_search($key, $this->queryTags[$tag]);
                if ($index !== false) {
                    unset($this->queryTags[$tag][$index]);
                }
            }
            unset($this->taggedKeys[$key]);
        }
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        $this->queryTags = [];
        $this->taggedKeys = [];
        return $this->cache->clear();
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }
} 