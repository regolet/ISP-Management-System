<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;
use App\Models\Staff\Staff;

class StaffMiddleware extends Middleware 
{
    protected $app;
    protected $staffModel;

    public function __construct() 
    {
        $this->app = Application::getInstance();
        $this->staffModel = new Staff();
    }

    /**
     * Handle staff access check
     */
    public function handle($args = []) 
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check if user is staff
        if ($_SESSION['role'] !== 'staff') {
            // Get response instance
            $response = $this->app->getResponse();
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $response->json([
                    'error' => 'Forbidden',
                    'message' => 'Staff access required'
                ], 403);
                return false;
            }
            
            // Set error message
            $_SESSION['error'] = 'Staff access required.';
            
            // Redirect based on role
            $redirectPath = match($_SESSION['role']) {
                'admin' => '/admin/dashboard',
                'customer' => '/customer/dashboard',
                default => '/login'
            };
            
            $response->redirect($redirectPath);
            return false;
        }

        // Check if staff record exists and is active
        $staff = $this->staffModel->findByUserId($_SESSION['user_id']);
        
        if (!$staff || $staff['status'] !== 'active') {
            // Get response instance
            $response = $this->app->getResponse();
            
            // Check if AJAX request
            if ($this->app->getRequest()->isAjax()) {
                $response->json([
                    'error' => 'Forbidden',
                    'message' => 'Staff account is inactive'
                ], 403);
                return false;
            }
            
            // Set error message
            $_SESSION['error'] = 'Your staff account is inactive. Please contact administrator.';
            
            // Destroy session
            session_unset();
            session_destroy();
            session_start();
            
            $response->redirect('/login');
            return false;
        }

        // Store staff data in session if not already set
        if (!isset($_SESSION['staff_id'])) {
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['department'] = $staff['department'];
            $_SESSION['position'] = $staff['position'];
        }

        // Check specific permissions if provided
        if (isset($args['permissions'])) {
            $permissions = (array)$args['permissions'];
            
            foreach ($permissions as $permission) {
                if (!$this->staffModel->hasPermission($staff['id'], $permission)) {
                    // Get response instance
                    $response = $this->app->getResponse();
                    
                    // Check if AJAX request
                    if ($this->app->getRequest()->isAjax()) {
                        $response->json([
                            'error' => 'Forbidden',
                            'message' => 'Insufficient permissions'
                        ], 403);
                        return false;
                    }
                    
                    // Set error message
                    $_SESSION['error'] = 'You do not have the required permissions.';
                    
                    $response->redirect('/staff/dashboard');
                    return false;
                }
            }
        }

        // Check department access if provided
        if (isset($args['departments'])) {
            $departments = (array)$args['departments'];
            
            if (!in_array($staff['department'], $departments)) {
                // Get response instance
                $response = $this->app->getResponse();
                
                // Check if AJAX request
                if ($this->app->getRequest()->isAjax()) {
                    $response->json([
                        'error' => 'Forbidden',
                        'message' => 'Department access required'
                    ], 403);
                    return false;
                }
                
                // Set error message
                $_SESSION['error'] = 'You do not have access to this department.';
                
                $response->redirect('/staff/dashboard');
                return false;
            }
        }

        // Check position access if provided
        if (isset($args['positions'])) {
            $positions = (array)$args['positions'];
            
            if (!in_array($staff['position'], $positions)) {
                // Get response instance
                $response = $this->app->getResponse();
                
                // Check if AJAX request
                if ($this->app->getRequest()->isAjax()) {
                    $response->json([
                        'error' => 'Forbidden',
                        'message' => 'Position access required'
                    ], 403);
                    return false;
                }
                
                // Set error message
                $_SESSION['error'] = 'You do not have the required position access.';
                
                $response->redirect('/staff/dashboard');
                return false;
            }
        }

        return true;
    }
}
