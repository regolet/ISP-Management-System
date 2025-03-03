<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class AuthMiddleware extends Middleware 
{
    private Application $app;

    public function __construct() 
    {
        $this->app = Application::getInstance();
    }

    /**
     * Handle authentication check
     */
    public function handle(array $args = []): bool 
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            // Store intended URL for redirect after login
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $this->app->getResponse()->json([
                    'error' => 'Unauthorized',
                    'redirect' => '/login'
                ], 401);
                return false;
            }
            
            // Redirect to login page
            $this->app->getResponse()->redirect('/login');
            return false;
        }

        // Check session expiration
        $sessionTimeout = $this->app->getConfig()->get('session.lifetime', 1800); // 30 minutes default
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
            // Session expired, destroy it
            session_unset();
            session_destroy();
            
            // Start new session for flash message
            session_start();
            $_SESSION['error'] = 'Your session has expired. Please login again.';
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $this->app->getResponse()->json([
                    'error' => 'Session expired',
                    'redirect' => '/login'
                ], 401);
                return false;
            }
            
            // Redirect to login page
            $this->app->getResponse()->redirect('/login');
            return false;
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();

        // Check if user account is still active
        $userModel = new \App\Models\User();
        $user = $userModel->find($_SESSION['user_id']);
        
        if (!$user || $user['status'] !== 'active') {
            // User account inactive or deleted
            session_unset();
            session_destroy();
            
            // Start new session for flash message
            session_start();
            $_SESSION['error'] = 'Your account is no longer active. Please contact administrator.';
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $this->app->getResponse()->json([
                    'error' => 'Account inactive',
                    'redirect' => '/login'
                ], 401);
                return false;
            }
            
            // Redirect to login page
            $this->app->getResponse()->redirect('/login');
            return false;
        }

        // Check if password needs to be changed
        if ($user['force_password_change'] ?? false) {
            // Skip password change check for password change page and AJAX requests
            if (!str_contains($_SERVER['REQUEST_URI'], '/change-password') && !$this->app->getRequest()->isAjax()) {
                $_SESSION['warning'] = 'You must change your password before continuing.';
                $this->app->getResponse()->redirect('/change-password');
                return false;
            }
        }

        return true;
    }
}
