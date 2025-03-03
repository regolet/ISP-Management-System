<?php
/**
 * ISP Management System - Entry Point
 */

// Define application root path
define('APP_ROOT', dirname(__DIR__));

// Define the application start time
define('APP_START', microtime(true));

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Start output buffering
ob_start();

// Start session
session_start();

// Require the Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load environment variables
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            // Handle variable interpolation
            $value = preg_replace_callback('/\${([^}]+)}/', function($matches) {
                return getenv($matches[1]) ?: '';
            }, $value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Initialize the application
$app = \App\Core\Application::getInstance();

try {
    // Register error handler
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    // Register shutdown function
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            ob_clean();
            if (getenv('APP_DEBUG') === 'true') {
                echo "<pre>";
                print_r($error);
                echo "</pre>";
            } else {
                http_response_code(500);
                echo "Internal Server Error";
            }
        }
    });

    // Register core middleware
    $app->registerMiddleware(\App\Middleware\CSRFMiddleware::class);
    $app->registerMiddleware(\App\Middleware\CacheMiddleware::class);

    // Load routes
    $router = $app->getRouter();

    // Web routes
    require_once APP_PATH . '/routes/web.php';

    // API routes
    if (file_exists(APP_PATH . '/routes/api.php')) {
        require_once APP_PATH . '/routes/api.php';
    }

    // Set execution time header if in debug mode
    if (getenv('APP_DEBUG') === 'true') {
        header("X-Execution-Time: " . number_format((microtime(true) - APP_START) * 1000, 2) . "ms");
    }

    // Handle the request
    $app->run();

} catch (\Throwable $e) {
    // Handle any uncaught exceptions
    ob_clean();
    if (getenv('APP_DEBUG') === 'true') {
        throw $e;
    } else {
        error_log($e->getMessage());
        http_response_code(500);
        echo "Internal Server Error";
    }
}

// Flush output buffer
ob_end_flush();
