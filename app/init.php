<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    
    session_start();
}

// Load configuration
$config = require_once __DIR__ . '/../config/app.php';

// Set error reporting based on debug mode
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Load helpers
require_once __DIR__ . '/helpers.php';

// Ensure AuthController is loaded
if (file_exists(__DIR__ . '/Controllers/AuthController.php')) {
    require_once __DIR__ . '/Controllers/AuthController.php';
} elseif (file_exists(__DIR__ . '/controllers/AuthController.php')) {
    require_once __DIR__ . '/controllers/AuthController.php';
}

// Initialize database connection
require_once __DIR__ . '/../config/database.php';
$database = new Database();

// Get database connection
$db = $database->getConnection();

// Check if database connection was successful
if ($db === null) {
    error_log("Failed to establish database connection in init.php");
    die("Database connection failed. Please check the database configuration.");
}

// Security measures
// Generate CSRF token if not exists or regenerate periodically
if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time'] > 3600)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Verify CSRF token for POST, PUT, DELETE requests
if (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    // Get token from various sources
    $token = $_POST['csrf_token'] ?? null;
    
    // Check header if not in POST
    if ($token === null) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $token = $headers['X-CSRF-TOKEN'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? null;
    }
    
    // Check custom header if not in standard headers
    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    
    // Verify token
    if ($token === null || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Skip CSRF check for login page and API endpoints with their own CSRF checks
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        $skipCsrfCheck = in_array($currentScript, ['login.php', 'api.php']);
        
        if (!$skipCsrfCheck) {
            error_log("CSRF token verification failed. Expected: " . ($_SESSION['csrf_token'] ?? 'not set') . ", Got: " . ($token ?? 'not provided'));
            http_response_code(403);
            
            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                die(json_encode(['error' => 'Invalid CSRF token']));
            } else {
                die('Invalid CSRF token');
            }
        }
    }
}

// Session security
// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check session timeout only if user is logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity'])) {
        $inactive = 1800; // 30 minutes
        if (time() - $_SESSION['last_activity'] > $inactive) {
            // Session expired
            session_unset();
            session_destroy();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // AJAX request
                http_response_code(401);
                die(json_encode(['error' => 'Session expired']));
            } else {
                // Regular request
                header("Location: /login.php?expired=1");
                exit();
            }
        }
    }
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// XSS Protection Headers
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Content Security Policy
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com; " .
       "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
       "font-src 'self' https://cdnjs.cloudflare.com; " .
       "img-src 'self' data: https:; " .
       "connect-src 'self';";
header("Content-Security-Policy: " . $csp);

// Function to handle uncaught exceptions
function handleException($exception) {
    error_log($exception);
    if (config('app.debug')) {
        echo "<h1>Error</h1>";
        echo "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>An error occurred</h1>";
        echo "<p>Please try again later or contact support if the problem persists.</p>";
    }
    exit();
}
set_exception_handler('handleException');

// Function to handle fatal errors
function handleFatalError() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        error_log(print_r($error, true));
        if (config('app.debug')) {
            echo "<h1>Fatal Error</h1>";
            echo "<p>" . htmlspecialchars($error['message']) . "</p>";
            echo "<p>File: " . htmlspecialchars($error['file']) . " Line: " . $error['line'] . "</p>";
        } else {
            echo "<h1>An error occurred</h1>";
            echo "<p>Please try again later or contact support if the problem persists.</p>";
        }
    }
}
register_shutdown_function('handleFatalError');

// Initialize global variables
$GLOBALS['notifications'] = [];
$GLOBALS['errors'] = [];

// Function to add notification
function add_notification($message, $type = 'info') {
    $GLOBALS['notifications'][] = [
        'message' => $message,
        'type' => $type
    ];
}

// Function to add error
function add_error($message) {
    $GLOBALS['errors'][] = $message;
}

// Function to get base URL
function base_url($path = '') {
    $base_url = rtrim(config('app.url'), '/');
    return $base_url . '/' . ltrim($path, '/');
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to require authentication
function require_auth() {
    if (!is_logged_in()) {
        redirect_with_message('/login.php', 'Please login to continue', 'warning');
    }
}

// Function to require admin role
function require_admin() {
    require_auth();
    if ($_SESSION['role'] !== 'admin') {
        redirect_with_message('/dashboard.php', 'Access denied', 'error');
    }
}
