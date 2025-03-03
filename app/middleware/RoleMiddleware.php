<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class RoleMiddleware extends Middleware 
{
    protected $app;

    public function __construct() 
    {
        $this->app = Application::getInstance();
    }

    /**
     * Handle role check
     * @param array $args Array of allowed roles
     * @return bool
     */
    public function handle($args = []) 
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Get user's role
        $userRole = $_SESSION['role'] ?? null;

        // If no roles specified, allow access
        if (empty($args)) {
            return true;
        }

        // Check if user's role is in allowed roles
        if (!in_array($userRole, $args)) {
            // Get response instance
            $response = $this->app->getResponse();
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $response->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to access this resource'
                ], 403);
                return false;
            }
            
            // Set error message
            $_SESSION['error'] = 'You do not have permission to access this page.';
            
            // Redirect to appropriate dashboard based on role
            $redirectPath = match($userRole) {
                'admin' => '/admin/dashboard',
                'staff' => '/staff/dashboard',
                'customer' => '/customer/dashboard',
                default => '/login'
            };
            
            $response->redirect($redirectPath);
            return false;
        }

        // Check specific permissions if provided
        if (isset($args['permissions'])) {
            $permissions = (array)$args['permissions'];
            
            foreach ($permissions as $permission) {
                if (!hasPermission($permission)) {
                    // Get response instance
                    $response = $this->app->getResponse();
                    
                    // Check if AJAX request
                    if ($this->app->getRequest()->isAjax()) {
                        $response->json([
                            'error' => 'Forbidden',
                            'message' => 'You do not have the required permissions'
                        ], 403);
                        return false;
                    }
                    
                    // Set error message
                    $_SESSION['error'] = 'You do not have the required permissions to access this page.';
                    
                    // Redirect to dashboard
                    $redirectPath = match($userRole) {
                        'admin' => '/admin/dashboard',
                        'staff' => '/staff/dashboard',
                        'customer' => '/customer/dashboard',
                        default => '/login'
                    };
                    
                    $response->redirect($redirectPath);
                    return false;
                }
            }
        }

        return true;
    }
}
