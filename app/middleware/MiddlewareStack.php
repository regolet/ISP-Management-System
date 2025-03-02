<?php
namespace App\Middleware;

use App\Core\Middleware;

class MiddlewareStack 
{
    private $middlewares = [];

    /**
     * Add middleware to stack
     */
    public function add($middleware, $args = []) 
    {
        $this->middlewares[] = [
            'middleware' => $middleware,
            'args' => $args
        ];
    }

    /**
     * Execute middleware stack
     */
    public function execute() 
    {
        foreach ($this->middlewares as $middleware) {
            $class = $middleware['middleware'];
            $args = $middleware['args'];

            // Create middleware instance
            if (is_string($class)) {
                $instance = new $class();
            } else {
                $instance = $class;
            }

            // Check if instance is valid middleware
            if (!$instance instanceof Middleware) {
                throw new \Exception("Invalid middleware class: " . get_class($instance));
            }

            // Execute middleware
            if (!$instance->handle($args)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear middleware stack
     */
    public function clear() 
    {
        $this->middlewares = [];
    }

    /**
     * Get middleware stack
     */
    public function getStack() 
    {
        return $this->middlewares;
    }

    /**
     * Check if stack is empty
     */
    public function isEmpty() 
    {
        return empty($this->middlewares);
    }

    /**
     * Get stack size
     */
    public function size() 
    {
        return count($this->middlewares);
    }

    /**
     * Remove last middleware from stack
     */
    public function pop() 
    {
        return array_pop($this->middlewares);
    }

    /**
     * Remove first middleware from stack
     */
    public function shift() 
    {
        return array_shift($this->middlewares);
    }

    /**
     * Add middleware to beginning of stack
     */
    public function unshift($middleware, $args = []) 
    {
        array_unshift($this->middlewares, [
            'middleware' => $middleware,
            'args' => $args
        ]);
    }

    /**
     * Get middleware at index
     */
    public function get($index) 
    {
        return $this->middlewares[$index] ?? null;
    }

    /**
     * Remove middleware at index
     */
    public function remove($index) 
    {
        if (isset($this->middlewares[$index])) {
            unset($this->middlewares[$index]);
            $this->middlewares = array_values($this->middlewares);
            return true;
        }
        return false;
    }

    /**
     * Insert middleware at index
     */
    public function insert($index, $middleware, $args = []) 
    {
        array_splice($this->middlewares, $index, 0, [[
            'middleware' => $middleware,
            'args' => $args
        ]]);
    }

    /**
     * Replace middleware at index
     */
    public function replace($index, $middleware, $args = []) 
    {
        if (isset($this->middlewares[$index])) {
            $this->middlewares[$index] = [
                'middleware' => $middleware,
                'args' => $args
            ];
            return true;
        }
        return false;
    }

    /**
     * Find middleware index by class name
     */
    public function findIndex($class) 
    {
        foreach ($this->middlewares as $index => $middleware) {
            if (
                (is_string($middleware['middleware']) && $middleware['middleware'] === $class) ||
                (is_object($middleware['middleware']) && get_class($middleware['middleware']) === $class)
            ) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Check if middleware exists in stack
     */
    public function has($class) 
    {
        return $this->findIndex($class) !== -1;
    }

    /**
     * Get middleware arguments
     */
    public function getArgs($class) 
    {
        $index = $this->findIndex($class);
        return $index !== -1 ? $this->middlewares[$index]['args'] : null;
    }

    /**
     * Set middleware arguments
     */
    public function setArgs($class, $args) 
    {
        $index = $this->findIndex($class);
        if ($index !== -1) {
            $this->middlewares[$index]['args'] = $args;
            return true;
        }
        return false;
    }
}
