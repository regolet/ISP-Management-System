<?php
namespace App\Core\Routing;

class Route
{
    private string $method;
    private string $path;
    private $callback;
    private array $middleware = [];
    private string $pattern;
    private array $parameters = [];
    private array $wheres = [];
    private ?string $name = null;
    private ?string $domain = null;

    public function __construct(string $method, string $path, $callback)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->callback = $callback;
        $this->buildPattern();
    }

    private function buildPattern(): void
    {
        $pattern = $this->path;

        // Replace named parameters with regex patterns
        $pattern = preg_replace_callback('/\{([^:}]+)(?::([^}]+))?\}/', function($matches) {
            $paramName = $matches[1];
            $regex = $this->wheres[$paramName] ?? '[^/]+';
            return "(?P<{$paramName}>{$regex})";
        }, $pattern);

        // Add domain pattern if set
        if ($this->domain) {
            $pattern = $this->domain . $pattern;
        }

        $this->pattern = "#^{$pattern}$#";
    }

    public function middleware(string|array $middleware): self
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function matches(string $path, string $method): bool
    {
        if ($this->method !== strtoupper($method)) {
            return false;
        }

        // Check domain if set
        if ($this->domain !== null) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (!preg_match("#^{$this->domain}$#", $host)) {
                return false;
            }
        }

        if (preg_match($this->pattern, $path, $matches)) {
            // Store matched parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->parameters[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    public function where(string $parameter, string $regex): self
    {
        $this->wheres[$parameter] = $regex;
        $this->buildPattern();
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function domain(string $domain): self
    {
        $this->domain = $domain;
        $this->buildPattern();
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->path = $prefix . $this->path;
        $this->buildPattern();
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function __toString(): string
    {
        $middlewareStr = empty($this->middleware) ? '' : ' [' . implode(', ', $this->middleware) . ']';
        $nameStr = $this->name ? " ({$this->name})" : '';
        return "{$this->method} {$this->path}{$middlewareStr}{$nameStr}";
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'pattern' => $this->pattern,
            'middleware' => $this->middleware,
            'parameters' => $this->parameters,
            'wheres' => $this->wheres,
            'name' => $this->name,
            'domain' => $this->domain
        ];
    }
}
