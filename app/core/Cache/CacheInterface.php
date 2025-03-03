<?php
namespace App\Core\Cache;

interface CacheInterface
{
    /**
     * Get a value from cache
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Delete a value from cache
     */
    public function delete(string $key): bool;

    /**
     * Clear all values from cache
     */
    public function clear(): bool;

    /**
     * Get multiple values from cache
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Set multiple values in cache
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Delete multiple values from cache
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool;

    /**
     * Get or set a value in cache
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Get the cache driver name
     */
    public function getDriver(): string;

    /**
     * Set the cache prefix
     */
    public function setPrefix(string $prefix): void;

    /**
     * Get the cache prefix
     */
    public function getPrefix(): string;
}
