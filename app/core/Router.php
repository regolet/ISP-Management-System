<?php
namespace App\Core;

use App\Core\Routing\Route;
use App\Core\Routing\RouteCollection;
use App\Core\Routing\RouteCache;

class Router
{
    private RouteCollection $routes;
    private RouteCache $cache;
    private array $middleware = [];
    private array $globalMiddleware = [];
    private array $groupStack = [];

    public function __construct()
    {
        $this->routes = new RouteCollection();
        $this->cache = new RouteCache();
        $this->initializeGlobalMiddleware();
    }

    private function initializeGlobalMiddleware(): void
    {
        // Add any global middleware here
        $this->globalMiddleware = [
            // Example: \App\Middleware\AuthMiddleware::class
        ];
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function getGroupAttributes(): array
    {
        if (empty($this->groupStack)) {
            return [];
        }

        $result = [
            'prefix' => '',
            'middleware' => []
        ];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $result['prefix'] .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $middleware = is_array($group['middleware']) 
                    ? $group['middleware'] 
                    : [$group['middleware']];
                $result['middleware'] = array_merge($result['middleware'], $middleware);
            }
        }

        $result['prefix'] = '/' . trim($result['prefix'], '/');
        return $result;
    }

    public function get(string $path, $callback): Route
    {
        return $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, $callback): Route
    {
        return $this->addRoute('POST', $path, $callback);
    }

    public function put(string $path, $callback): Route
    {
        return $this->addRoute('PUT', $path, $callback);
    }

    public function delete(string $path, $callback): Route
    {
        return $this->addRoute('DELETE', $path, $callback);
    }

    private function addRoute(string $method, string $path, $callback): Route
    {
        $groupAttributes = $this->getGroupAttributes();
        
        // Combine group prefix with route path
        $path = $groupAttributes['prefix'] . '/' . trim($path, '/');
        $path = '/' . trim($path, '/');
        
        $route = new Route($method, $path, $callback);
        
        // Add group middleware
        if (!empty($groupAttributes['middleware'])) {
            foreach ($groupAttributes['middleware'] as $middleware) {
                $route->middleware($middleware);
            }
        }
        
        $this->routes->add($route);
        return $route;
    }

    public function resolve(string $path, string $method)
    {
        // Clean the path
        $path = parse_url($path, PHP_URL_PATH);
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }

        // Find matching route
        $route = $this->routes->match($path, $method);
        if (!$route) {
            return $this->handleNotFound();
        }

        try {
            // Extract route parameters
            $params = $this->extractRouteParameters($route, $path);

            // Run middleware
            $this->runMiddleware($route);

            // Execute callback
            return $this->executeRoute($route, $params);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function extractRouteParameters(Route $route, string $path): array
    {
        $params = [];
        $pattern = $route->getPattern();
        
        if (preg_match($pattern, $path, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
        }

        return $params;
    }

    private function runMiddleware(Route $route): void
    {
        $middlewareStack = array_merge(
            $this->globalMiddleware,
            $route->getMiddleware()
        );

        foreach ($middlewareStack as $middleware) {
            if (is_string($middleware)) {
                // Parse middleware string for parameters
                $parts = explode(':', $middleware);
                $middlewareClass = $parts[0];
                $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

                // Add namespace if not provided
                if (strpos($middlewareClass, '\\') === false) {
                    $middlewareClass = "\\App\\Middleware\\{$middlewareClass}";
                }

                // Create middleware instance
                $middleware = new $middlewareClass();
            }
            
            // Handle middleware with arguments
            if (!$middleware->handle($args ?? [])) {
                throw new \RuntimeException('Middleware check failed');
            }
        }
    }

    private function executeRoute(Route $route, array $params)
    {
        $callback = $route->getCallback();

        if (is_array($callback)) {
            [$controller, $method] = $callback;
            if (is_string($controller)) {
                $controller = new $controller();
            }
            return $controller->$method(...array_values($params));
        }

        if (is_callable($callback)) {
            return $callback(...array_values($params));
        }

        throw new \RuntimeException('Invalid route callback');
    }

    private function handleNotFound()
    {
        Application::getInstance()->getResponse()->notFound();
    }

    public function registerMiddleware(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }
}
