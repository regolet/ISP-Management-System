<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class CSRFMiddleware extends Middleware 
{
    protected $app;
    protected $except = [
        // Add routes that should be excluded from CSRF protection
        '/api/*'  // Exclude API routes
    ];

    public function __construct() 
    {
        $this->app = Application::getInstance();
    }

    /**
     * Handle CSRF protection
     */
    public function handle($args = []) 
    {
        // Skip CSRF check for excluded routes
        $currentPath = $this->app->getRequest()->getPath();
        foreach ($this->except as $pattern) {
            if ($pattern === '*' || $this->matchPattern($pattern, $currentPath)) {
                return true;
            }
        }

        // Skip CSRF check for GET, HEAD, OPTIONS requests
        if (in_array($this->app->getRequest()->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Get token from request
        $token = $this->app->getRequest()->getPost('csrf_token') ?? 
                $this->app->getRequest()->getHeader('X-CSRF-TOKEN') ?? 
                $this->app->getRequest()->getHeader('X-XSRF-TOKEN');

        // Verify token
        if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            // Get response instance
            $response = $this->app->getResponse();
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $response->json([
                    'error' => 'CSRF token mismatch',
                    'message' => 'Invalid security token'
                ], 419);
                return false;
            }
            
            // Set error message
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            
            // Redirect back
            $response->redirect($this->app->getRequest()->getReferer() ?: '/');
            return false;
        }

        return true;
    }

    /**
     * Match route pattern
     */
    protected function matchPattern($pattern, $path) 
    {
        $pattern = str_replace('*', '.*', $pattern);
        return (bool)preg_match('#^' . $pattern . '$#', $path);
    }

    /**
     * Get CSRF token
     */
    public static function getToken() 
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Generate CSRF field
     */
    public static function field() 
    {
        return '<input type="hidden" name="csrf_token" value="' . self::getToken() . '">';
    }

    /**
     * Generate CSRF meta tag
     */
    public static function meta() 
    {
        return '<meta name="csrf-token" content="' . self::getToken() . '">';
    }

    /**
     * Add route to except list
     */
    public function except($routes) 
    {
        $routes = (array)$routes;
        $this->except = array_merge($this->except, $routes);
    }

    /**
     * Get except list
     */
    public function getExcept() 
    {
        return $this->except;
    }

    /**
     * Clear except list
     */
    public function clearExcept() 
    {
        $this->except = [];
    }

    /**
     * Set except list
     */
    public function setExcept($routes) 
    {
        $this->except = (array)$routes;
    }

    /**
     * Remove route from except list
     */
    public function removeExcept($route) 
    {
        $key = array_search($route, $this->except);
        if ($key !== false) {
            unset($this->except[$key]);
        }
    }
}
