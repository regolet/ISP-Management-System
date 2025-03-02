<?php
// Define root path
define('APP_ROOT', dirname(__DIR__));

// Load composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Load configuration
$config = require_once APP_ROOT . '/config/app.php';

// Initialize application
$app = new App\Core\Application($config);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load routes
require_once APP_ROOT . '/routes/web.php';
require_once APP_ROOT . '/routes/api.php';

// Run application
try {
    $app->run();
} catch (\Exception $e) {
    // Log error
    error_log("Application Error: " . $e->getMessage());
    
    // Show error page based on environment
    if ($config['debug']) {
        // Show detailed error in development
        echo "<h1>Application Error</h1>";
        echo "<pre>";
        echo $e->getMessage() . "\n\n";
        echo $e->getTraceAsString();
        echo "</pre>";
    } else {
        // Show generic error in production
        http_response_code(500);
        require_once APP_ROOT . '/views/errors/500.php';
    }
}
