<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class GuestMiddleware extends Middleware 
{
    protected $app;

    public function __construct() 
    {
        $this->app = Application::getInstance();
    }

    /**
     * Handle guest access check
     * Ensures that only non-authenticated users can access the route
     */
    public function handle($args = []) 
    {
        // If user is already authenticated
        if (isset($_SESSION['user_id'])) {
            // Get user's role
            $userRole = $_SESSION['role'] ?? null;
            
            // Get response instance
            $response = $this->app->getResponse();
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $response->json([
                    'error' => 'Already authenticated',
                    'redirect' => match($userRole) {
                        'admin' => '/admin/dashboard',
                        'staff' => '/staff/dashboard',
                        'customer' => '/customer/dashboard',
                        default => '/'
                    }
                ], 400);
                return false;
            }
            
            // Redirect to appropriate dashboard based on role
            $redirectPath = match($userRole) {
                'admin' => '/admin/dashboard',
                'staff' => '/staff/dashboard',
                'customer' => '/customer/dashboard',
                default => '/'
            };
            
            $response->redirect($redirectPath);
            return false;
        }

        // Allow access for non-authenticated users
        return true;
    }
}
