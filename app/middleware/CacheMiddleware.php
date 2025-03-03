<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;
use App\Core\Cache\FileCache;

class CacheMiddleware extends Middleware 
{
    protected $app;
    protected $cache;
    protected $excludedPaths = [
        '/admin/',  // Don't cache admin pages
        '/api/',    // Don't cache API responses
        '/staff/'   // Don't cache staff pages
    ];

    public function __construct() 
    {
        $this->app = Application::getInstance();
        $this->cache = new FileCache();
    }

    /**
     * Handle request caching
     */
    public function handle(array $args = []): bool 
    {
        $request = $this->app->getRequest();
        
        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return true;
        }

        $path = $request->getPath();

        // Skip cache for excluded paths
        foreach ($this->excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        // Skip cache for authenticated users
        if ($this->isAuthenticated()) {
            return true;
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($path);

        // Try to get from cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->response()->html($cached);
            return false;
        }

        return true;
    }

    /**
     * Generate cache key from request path and query parameters
     */
    private function generateCacheKey(string $path): string
    {
        $query = $this->request()->getQuery();
        ksort($query); // Sort query parameters for consistent cache keys
        return md5($path . '?' . http_build_query($query));
    }
}
