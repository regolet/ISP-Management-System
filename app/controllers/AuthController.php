<?php
namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller 
{
    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * Show login page
     */
    public function showLogin() 
    {
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

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
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if (empty($username) || empty($password)) {
            return $this->view('auth/login', [
                'error' => 'Username and password are required',
                'username' => $username,
                'title' => 'Login - ISP Management System'
            ]);
        }

$userModel = new \App\Models\User();
$user = $userModel->findByUsername($username);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    return $this->redirectToDashboard();
        }

        return $this->view('auth/login', [
            'error' => 'Invalid username or password',
            'username' => $username,
            'title' => 'Login - ISP Management System'
        ]);
    }

    /**
     * Handle logout
     */
    public function logout() 
    {
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
        $redirectPath = match($_SESSION['role'] ?? '') {
            'admin' => '/admin/dashboard',
            'staff' => '/staff/dashboard',
            'customer' => '/customer/dashboard',
            default => '/login'
        };

        return $this->redirect($redirectPath);
    }
}
