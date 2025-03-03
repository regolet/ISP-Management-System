<?php
namespace App\Core\Routing;

class RouteCollection
{
    private array $routes = [];
    private array $namedRoutes = [];

    public function add(Route $route): void
    {
        $this->routes[] = $route;
    }

    public function addNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    public function match(string $path, string $method): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($path, $method)) {
                return $route;
            }
        }

        return null;
    }

    public function getNamedRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function getByMethod(string $method): array
    {
        return array_filter($this->routes, function(Route $route) use ($method) {
            return $route->getMethod() === strtoupper($method);
        });
    }

    public function getByPath(string $path): array
    {
        return array_filter($this->routes, function(Route $route) use ($path) {
            return $route->getPath() === $path;
        });
    }

    public function getByMethodAndPath(string $method, string $path): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->getMethod() === strtoupper($method) && $route->getPath() === $path) {
                return $route;
            }
        }

        return null;
    }

    public function getAllPaths(): array
    {
        return array_map(function(Route $route) {
            return $route->getPath();
        }, $this->routes);
    }

    public function getAllMethods(): array
    {
        return array_unique(array_map(function(Route $route) {
            return $route->getMethod();
        }, $this->routes));
    }

    public function toArray(): array
    {
        return array_map(function(Route $route) {
            return [
                'method' => $route->getMethod(),
                'path' => $route->getPath(),
                'pattern' => $route->getPattern(),
                'middleware' => $route->getMiddleware()
            ];
        }, $this->routes);
    }

    public function __toString(): string
    {
        $routes = array_map(function(Route $route) {
            return (string) $route;
        }, $this->routes);

        return implode("\n", $routes);
    }
}
