<?php
namespace App\Controllers;

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        // Get database connection
        $database = new \Database(); // Assuming Database class is global or autoloaded
        $this->db = $database->getConnection();

        // Initialize User model with case-insensitive path
        if (file_exists(__DIR__ . '/../Models/User.php')) {
            require_once __DIR__ . '/../Models/User.php';
        } elseif (file_exists(__DIR__ . '/../models/User.php')) {
            require_once __DIR__ . '/../models/User.php';
        } else {
            error_log("User model file not found");
            // Continue without the User model - some functionality will be limited
        }
        
        if (class_exists('\\App\\Models\\User')) {
            $this->user = new \App\Models\User($this->db); // Use fully qualified name
        } else {
            error_log("User class not found");
            // Continue without the User model - some functionality will be limited
        }
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        error_log("AuthController::isLoggedIn() - Entry"); // Log entry
        if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
            $inactive = 1800; // 30 minutes
            $session_life = time() - $_SESSION['last_activity'];

            if ($session_life > $inactive) {
                // Session expired
                error_log("AuthController::isLoggedIn() - Session expired"); // Log session expired
                $this->logout();
                return false;
            }

            // Update last activity time
            $_SESSION['last_activity'] = time();
            error_log("AuthController::isLoggedIn() - Session valid - User ID: " . $_SESSION['user_id']); // Log valid session
            return true;
        }
        error_log("AuthController::isLoggedIn() - Not logged in - Session variables not set"); // Log not logged in
        return false;
    }

    /**
     * Attempt to log in a user
     */
    public function login($username, $password) {
        error_log("AuthController::login() - Entry"); // Log entry
        error_log("AuthController::login() - Username: " . $username); // Log username

        try {
            // Find user by username
            $userFound = $this->user->findByUsername($username);
            error_log("AuthController::login() - User found: " . ($userFound ? 'yes' : 'no')); // Log user found

            if ($userFound) {
                // Verify password
                $passwordVerified = $this->user->verifyPassword($password);
                error_log("AuthController::login() - Password verified: " . ($passwordVerified ? 'yes' : 'no')); // Log password verified

                if ($passwordVerified) {
                    // Set session variables
                    $_SESSION['user_id'] = $this->user->id;
                    $_SESSION['username'] = $this->user->username;
                    $_SESSION['role'] = $this->user->role;
                    $_SESSION['last_activity'] = time();

                    // Always generate a new CSRF token on login
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Log successful login
                    $this->logLoginAttempt($username, true);

                    error_log("AuthController::login() - Login successful"); // Log success
                    return true;
                }
            }

            // Log failed login attempt
            $this->logLoginAttempt($username, false);
            error_log("AuthController::login() - Login failed"); // Log failure
            return false;
        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log out the current user
     */
    public function logout() {
        // Log the logout activity if user was logged in
        if (isset($_SESSION['user_id'])) {
            $this->logActivity('logout', 'User logged out successfully');
        }

        // Clear all session variables
        $_SESSION = array();

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }

        // Destroy the session
        session_destroy();
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if user is an admin
     * @return bool True if user is an admin, false otherwise
     */
    public function isAdmin() {
        $role = $this->getUserRole();
        return $role === 'admin';
    }
    
    /**
     * Get the current user's role
     * @return string|null The user's role or null if not logged in
     */
    public function getUserRole() {
        if (isset($_SESSION['role'])) {
            return $_SESSION['role'];
        }
        
        // Fallback to check if user is logged in but role is not set
        if (isset($_SESSION['user_id']) && $this->user !== null) {
            try {
                // Try to get user role from database
                $user = $this->user->getById($_SESSION['user_id']);
                if ($user && isset($user['role'])) {
                    // Update session with role
                    $_SESSION['role'] = $user['role'];
                    return $user['role'];
                }
            } catch (\Exception $e) {
                error_log("Error getting user role: " . $e->getMessage());
            }
        }
        
        return null;
    }

    /**
     * Log login attempts
     */
    private function logLoginAttempt($username, $success) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs
                (user_id, activity_type, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");

            $user_id = $success ? $this->user->id : null;
            $activity_type = $success ? 'login_success' : 'login_failed';
            $description = $success
                ? "Successful login for user: $username"
                : "Failed login attempt for username: $username";

            $stmt->execute([
                $user_id,
                $activity_type,
                $description,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
        }
    }

    /**
     * Log activity
     */
    private function logActivity($type, $description) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs
                (user_id, activity_type, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $type,
                $description,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }

    /**
     * Get user's last login time
     */
    public function getLastLoginTime($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT created_at
                FROM activity_logs
                WHERE user_id = ?
                AND activity_type = 'login_success'
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([$user_id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? $result['created_at'] : null;
        } catch (\Exception $e) {
            error_log("Error getting last login time: " . $e->getMessage());
            return null;
        }
    }
}
