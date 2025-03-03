<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use PDO;

class AuthController extends Controller 
{
    private $db;

    public function __construct() 
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDB()->getConnection();
    }

    public function showLogin() 
    {
        if (isset($_SESSION['user_id'])) {
            return $this->redirectToDashboard();
        }

        return $this->view('auth/login', [
            'title' => 'Login - ISP Management System'
        ]);
    }

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

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

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
        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->view('auth/login', [
                'error' => 'An error occurred during login',
                'username' => $username,
                'title' => 'Login - ISP Management System'
            ]);
        }
    }

    public function register() 
    {
        try {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');
            $email = $this->request->getPost('email');

            if (empty($username) || empty($password) || empty($email)) {
                return $this->view('auth/register', [
                    'error' => 'All fields are required',
                    'title' => 'Register - ISP Management System'
                ]);
            }

            // Check if username exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                return $this->view('auth/register', [
                    'error' => 'Username already exists',
                    'email' => $email,
                    'title' => 'Register - ISP Management System'
                ]);
            }

            // Check if email exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                return $this->view('auth/register', [
                    'error' => 'Email already exists',
                    'username' => $username,
                    'title' => 'Register - ISP Management System'
                ]);
            }

            // Insert new user - role and status will use default values
            $sql = "INSERT INTO users (username, password, email, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $email
            ]);

            if (!$success) {
                throw new \Exception("Failed to create user account");
            }

            return $this->redirect('/login');

        } catch (\Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return $this->view('auth/register', [
                'error' => 'An error occurred during registration',
                'username' => $username ?? '',
                'email' => $email ?? '',
                'title' => 'Register - ISP Management System'
            ]);
        }
    }

    public function logout() 
    {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['info'] = 'You have been logged out successfully';
        return $this->redirect('/login');
    }

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
