<?php
namespace App\Core;

class Router 
{
    private $routes = [];
    private $errorHandler;
    private $currentPrefix = '';
    private $currentMiddleware = [];

    /**
     * Add route
     */
    public function add($method, $path, $callback) 
    {
        // Add prefix to path
        $path = $this->currentPrefix . $path;
        
        // Convert path parameters to regex pattern
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?<$1>[^/]+)', $path);
        $pattern = "#^{$pattern}$#";

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'callback' => $callback,
            'middleware' => $this->currentMiddleware
        ];

        return $this;
    }

    /**
     * Add GET route
     */
    public function get($path, $callback) 
    {
        return $this->add('GET', $path, $callback);
    }

    /**
     * Add POST route
     */
    public function post($path, $callback) 
    {
        return $this->add('POST', $path, $callback);
    }

    /**
     * Add PUT route
     */
    public function put($path, $callback) 
    {
        return $this->add('PUT', $path, $callback);
    }

    /**
     * Add DELETE route
     */
    public function delete($path, $callback) 
    {
        return $this->add('DELETE', $path, $callback);
    }

    /**
     * Group routes with prefix and/or middleware
     */
    public function group($options, $callback) 
    {
        // Store current state
        $previousPrefix = $this->currentPrefix;
        $previousMiddleware = $this->currentMiddleware;

        // Update state with group options
        if (isset($options['prefix'])) {
            $this->currentPrefix .= $options['prefix'];
        }

        if (isset($options['middleware'])) {
            $middleware = (array)$options['middleware'];
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        }

        // Execute group callback
        $callback($this);

        // Restore previous state
        $this->currentPrefix = $previousPrefix;
        $this->currentMiddleware = $previousMiddleware;

        return $this;
    }

    /**
     * Set error handler
     */
    public function setErrorHandler($callback) 
    {
        $this->errorHandler = $callback;
    }

    /**
     * Handle 404 error
     */
    private function handleNotFound() 
    {
        if ($this->errorHandler) {
            call_user_func($this->errorHandler, 404);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }

    /**
     * Get all registered routes
     */
    public function getRoutes() 
    {
        return $this->routes;
    }

    /**
     * Get route by path and method
     */
    public function getRoute($path, $method) 
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Resolve route
     */
    public function resolve($path, $method) 
    {
        // Try to handle as static file first
        if ($method === 'GET') {
            // Check if path starts with /css/ or /js/
            if (preg_match('#^/(css|js)/#', $path)) {
                if (StaticFileHandler::handle($path)) {
                    return;
                }
            }
        }

        // Remove query string
        $path = parse_url($path, PHP_URL_PATH);
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        if (empty($path)) $path = '/';

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Apply middleware
                foreach ($route['middleware'] as $middleware) {
                    // Handle both string and class name formats
                    if (is_string($middleware)) {
                        // Split middleware name and parameters if string format
                        $parts = explode(':', $middleware);
                        $name = $parts[0];
                        $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

                        // Create middleware instance
                        if (str_contains($name, '\\')) {
                            // If full class name is provided
                            $instance = new $name();
                        } else {
                            // If only middleware name is provided
                            $class = "App\\Middleware\\{$name}";
                            $instance = new $class();
                        }
                    } else {
                        // If middleware is already a class reference
                        $instance = new $middleware();
                        $args = [];
                    }

                    // Handle middleware
                    if (!$instance->handle($args)) {
                        return;
                    }
                }

                // Execute route callback
                if (is_array($route['callback'])) {
                    [$class, $method] = $route['callback'];
                    $controller = new $class();
                    return call_user_func_array([$controller, $method], $params);
                } else {
                    return call_user_func_array($route['callback'], $params);
                }
            }
        }

        // No route found
        $this->handleNotFound();
    }
}
