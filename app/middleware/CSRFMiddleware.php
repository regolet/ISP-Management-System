<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class CSRFMiddleware extends Middleware 
{
    protected $app;
    protected $excludedPaths = [
        '/api/' // Exclude API routes from CSRF protection
    ];

    public function __construct() 
    {
        $this->app = Application::getInstance();
    }

    /**
     * Handle CSRF protection
     */
    public function handle(array $args = []): bool 
    {
        $request = $this->app->getRequest();
        $path = $request->getPath();

        // Skip CSRF check for excluded paths
        foreach ($this->excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        // Only check POST, PUT, DELETE requests
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!$token || $token !== $_SESSION['csrf_token']) {
                if ($request->isAjax()) {
                    $this->json(['error' => 'Invalid CSRF token'], 403);
                } else {
                    $this->forbidden('Invalid CSRF token');
                }
                return false;
            }
        }

        // Generate new CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return true;
    }
}
