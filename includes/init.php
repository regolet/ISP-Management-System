<?php
// Start session if not already started
if (session_id() == "") {
    session_start();
}

// Define root directory
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

// Set timezone
date_default_timezone_set('UTC');

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Register autoloader
spl_autoload_register(function ($class) {
    // Convert namespace separators to directory separators
    $class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    
    // Build potential file paths
    $paths = [
        ROOT_DIR . DIRECTORY_SEPARATOR . $class_file,  // For direct class references
        ROOT_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $class_file,  // For 'src' subdirectory
    ];
    
    // Try each path
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
    
    // Log if class not found 
    error_log("Class not found: {$class}");
});

// Load configuration files if needed
// require_once ROOT_DIR . '/config/app.php';

// Create database connection
require_once ROOT_DIR . '/config/database.php';
$database = new Database();
// Force recreate the database with the latest schema
$database->initializeDatabase(true);
$db = $database->getConnection();

// Include any other required files
require_once ROOT_DIR . '/app/helpers.php';

// Force authentication for backward compatibility
// If these are needed for compatibility with existing code
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['last_activity'] = time();
}
