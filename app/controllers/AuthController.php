<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Admin\AuditLog;

class AuthController extends Controller 
{
    private $userModel;
    private $auditLogModel;

    public function __construct() 
    {
        parent::__construct();
        $this->userModel = new User();
        $this->auditLogModel = new AuditLog();
    }

    /**
     * Show login page
     */
    public function showLogin() 
    {
        // If already logged in, redirect to appropriate dashboard
        if (isset($_SESSION['user_id'])) {
            return $this->redirectToDashboard();
        }

        return $this->view('auth/login', [
            'title' => 'Login - ISP Management System'
        ]);
    }

    /**
     * Handle login attempt
     */
    public function login() 
    {
        $username = $this->getPost('username');
        $password = $this->getPost('password');

        if (empty($username) || empty($password)) {
            return $this->view('auth/login', [
                'error' => 'Username and password are required',
                'username' => $username
            ]);
        }

        try {
            $user = $this->userModel->findByUsername($username);
            
            if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Log successful login
                $this->auditLogModel->logAction(
                    $user['id'],
                    'login',
                    'auth',
                    null,
                    null,
                    ['username' => $username]
                );

                // Update last login
                $this->userModel->update($user['id'], [
                    'last_login' => date('Y-m-d H:i:s')
                ]);

                // Redirect to appropriate dashboard
                return $this->redirectToDashboard();

            } else {
                // Log failed login attempt
                $this->auditLogModel->logAction(
                    null,
                    'login_failed',
                    'auth',
                    null,
                    null,
                    ['username' => $username]
                );

                return $this->view('auth/login', [
                    'error' => 'Invalid username or password',
                    'username' => $username
                ]);
            }

        } catch (\Exception $e) {
            return $this->view('auth/login', [
                'error' => 'System error occurred. Please try again later.',
                'username' => $username
            ]);
        }
    }

    /**
     * Handle logout
     */
    public function logout() 
    {
        // Log the logout action
        if (isset($_SESSION['user_id'])) {
            $this->auditLogModel->logAction(
                $_SESSION['user_id'],
                'logout',
                'auth'
            );
        }

        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session for flash message
        session_start();
        $_SESSION['info'] = 'You have been logged out successfully';

        return $this->redirect('/login');
    }

    /**
     * Redirect to appropriate dashboard based on role
     */
    private function redirectToDashboard() 
    {
        $redirectPath = match($_SESSION['role']) {
            'admin' => '/admin/dashboard',
            'staff' => '/staff/dashboard',
            'customer' => '/customer/dashboard',
            default => '/login'
        };

        return $this->redirect($redirectPath);
    }
}
