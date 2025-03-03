<?php
namespace App\Core\Database;

use App\Core\Cache\CacheInterface;

class QueryCache 
{
    private CacheInterface $cache;
    private int $defaultTTL;
    private array $queryTags = [];
    private bool $enabled = true;

    public function __construct(CacheInterface $cache, int $defaultTTL = 3600) 
    {
        $this->cache = $cache;
        $this->defaultTTL = $defaultTTL;
    }

    /**
     * Get cached query result or execute query and cache result
     */
    public function remember(string $sql, array $params, callable $callback, ?int $ttl = null, array $tags = []): mixed 
    {
        if (!$this->enabled) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($sql, $params);
        $ttl = $ttl ?? $this->defaultTTL;

        // Try to get from cache
        $result = $this->cache->get($cacheKey);
        if ($result !== null) {
            return $result;
        }

        // Execute query
        $result = $callback();

        // Store in cache
        $this->cache->set($cacheKey, $result, $ttl);

        // Store cache tags
        if (!empty($tags)) {
            $this->storeTags($cacheKey, $tags);
        }

        return $result;
    }

    /**
     * Store query tags for cache invalidation
     */
    private function storeTags(string $cacheKey, array $tags): void 
    {
        foreach ($tags as $tag) {
            $tagKey = $this->getTagKey($tag);
            $keys = $this->cache->get($tagKey, []);
            $keys[] = $cacheKey;
            $this->cache->set($tagKey, array_unique($keys), $this->defaultTTL);
        }
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags): void 
    {
        foreach ($tags as $tag) {
            $tagKey = $this->getTagKey($tag);
            $keys = $this->cache->get($tagKey, []);
            
            foreach ($keys as $key) {
                $this->cache->delete($key);
            }
            
            $this->cache->delete($tagKey);
        }
    }

    /**
     * Invalidate cache by table
     */
    public function invalidateTable(string $table): void 
    {
        $this->invalidateByTags(["table:$table"]);
    }

    /**
     * Generate cache key for query
     */
    private function generateCacheKey(string $sql, array $params): string 
    {
        return 'query_' . md5($sql . serialize($params));
    }

    /**
     * Generate cache key for tag
     */
    private function getTagKey(string $tag): string 
    {
        return 'tag_' . md5($tag);
    }

    /**
     * Enable query caching
     */
    public function enable(): void 
    {
        $this->enabled = true;
    }

    /**
     * Disable query caching
     */
    public function disable(): void 
    {
        $this->enabled = false;
    }

    /**
     * Check if query caching is enabled
     */
    public function isEnabled(): bool 
    {
        return $this->enabled;
    }

    /**
     * Set default TTL
     */
    public function setDefaultTTL(int $ttl): void 
    {
        $this->defaultTTL = $ttl;
    }

    /**
     * Get default TTL
     */
    public function getDefaultTTL(): int 
    {
        return $this->defaultTTL;
    }

    /**
     * Clear all query cache
     */
    public function clear(): void 
    {
        $this->cache->clear();
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array 
    {
        if (method_exists($this->cache, 'getStats')) {
            return $this->cache->getStats();
        }

        return [
            'enabled' => $this->enabled,
            'defaultTTL' => $this->defaultTTL,
            'driver' => $this->cache->getDriver()
        ];
    }

    /**
     * Cache query with specific tags
     */
    public function tags(array $tags): self 
    {
        $this->queryTags = $tags;
        return $this;
    }

    /**
     * Cache query for specific table
     */
    public function forTable(string $table): self 
    {
        $this->queryTags[] = "table:$table";
        return $this;
    }

    /**
     * Cache query for specific model
     */
    public function forModel(string $model): self 
    {
        $this->queryTags[] = "model:$model";
        return $this;
    }

    /**
     * Get current query tags
     */
    public function getTags(): array 
    {
        return $this->queryTags;
    }

    /**
     * Clear current query tags
     */
    public function clearTags(): void 
    {
        $this->queryTags = [];
    }

    /**
     * Get cache instance
     */
    public function getCache(): CacheInterface 
    {
        return $this->cache;
    }
}
