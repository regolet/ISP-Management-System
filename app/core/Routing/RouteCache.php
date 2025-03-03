<?php
namespace App\Core\Routing;

class RouteCache
{
    private array $cache = [];
    private int $ttl;
    private string $cacheDir;

    public function __construct(int $ttl = 3600)
    {
        $this->ttl = $ttl;
        $this->cacheDir = dirname(__DIR__, 3) . '/storage/cache/routes';
        $this->ensureCacheDirectory();
        $this->loadCache();
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    private function loadCache(): void
    {
        $cacheFile = $this->getCacheFilePath();
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $this->ttl)) {
            $content = file_get_contents($cacheFile);
            if ($content !== false) {
                $this->cache = unserialize($content) ?: [];
            }
        }
    }

    private function getCacheFilePath(): string
    {
        return $this->cacheDir . '/routes.cache';
    }

    public function get(string $path, string $method)
    {
        $key = $this->getCacheKey($path, $method);
        if (isset($this->cache[$key])) {
            $cacheEntry = $this->cache[$key];
            if (time() - $cacheEntry['time'] < $this->ttl) {
                return $cacheEntry['response'];
            }
            unset($this->cache[$key]);
        }
        return null;
    }

    public function set(string $path, string $method, $response): void
    {
        $key = $this->getCacheKey($path, $method);
        $this->cache[$key] = [
            'time' => time(),
            'response' => $response
        ];
        $this->saveCache();
    }

    private function getCacheKey(string $path, string $method): string
    {
        return md5($method . '|' . $path);
    }

    private function saveCache(): void
    {
        $cacheFile = $this->getCacheFilePath();
        file_put_contents($cacheFile, serialize($this->cache), LOCK_EX);
    }

    public function clear(): void
    {
        $this->cache = [];
        $cacheFile = $this->getCacheFilePath();
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function has(string $path, string $method): bool
    {
        $key = $this->getCacheKey($path, $method);
        if (isset($this->cache[$key])) {
            if (time() - $this->cache[$key]['time'] < $this->ttl) {
                return true;
            }
            unset($this->cache[$key]);
        }
        return false;
    }

    public function forget(string $path, string $method): void
    {
        $key = $this->getCacheKey($path, $method);
        unset($this->cache[$key]);
        $this->saveCache();
    }

    public function getTTL(): int
    {
        return $this->ttl;
    }

    public function setTTL(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getCacheSize(): int
    {
        return count($this->cache);
    }

    public function getLastModified(string $path, string $method): ?int
    {
        $key = $this->getCacheKey($path, $method);
        return isset($this->cache[$key]) ? $this->cache[$key]['time'] : null;
    }

    public function isExpired(string $path, string $method): bool
    {
        $key = $this->getCacheKey($path, $method);
        if (!isset($this->cache[$key])) {
            return true;
        }
        return (time() - $this->cache[$key]['time']) >= $this->ttl;
    }

    public function __destruct()
    {
        $this->saveCache();
    }
}
