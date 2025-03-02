<?php
// Define root path
define('APP_ROOT', __DIR__ . '/app');

// Debug autoloading
function debug_autoload($class) {
    // Convert namespace separators to directory separators
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    
    // First try loading from app directory
    $appPath = APP_ROOT . DIRECTORY_SEPARATOR . $file;
    if (file_exists($appPath)) {
        require $appPath;
        error_log("Loaded class from: " . $appPath);
        return true;
    }
    
    // Then try loading from root directory
    $rootPath = __DIR__ . DIRECTORY_SEPARATOR . $file;
    if (file_exists($rootPath)) {
        require $rootPath;
        error_log("Loaded class from: " . $rootPath);
        return true;
    }
    
    error_log("Failed to load class: " . $class);
    error_log("Tried paths:");
    error_log("  - " . $appPath);
    error_log("  - " . $rootPath);
    return false;
}

// Register autoloader
spl_autoload_register('debug_autoload');

// Start session
session_start();

// Load helper functions
require_once APP_ROOT . '/helpers/Functions.php';

// Import core classes
require_once APP_ROOT . '/core/Application.php';
require_once APP_ROOT . '/core/Request.php';
require_once APP_ROOT . '/core/Response.php';
require_once APP_ROOT . '/core/Router.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/StaticFileHandler.php';

use App\Core\Application;

// Get application instance
$app = Application::getInstance();

// Set error handler
$app->getRouter()->setErrorHandler(function($code) {
    http_response_code($code);
    require APP_ROOT . '/views/errors/' . $code . '.php';
});

// Load routes
require APP_ROOT . '/routes/web.php';
require APP_ROOT . '/routes/api.php';

// Run application
$app->run();
