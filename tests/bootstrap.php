<?php

// Ensure errors are displayed during testing
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Include Composer's autoloader
$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

// Register test namespace
$autoloader->addPsr4('Tests\\', __DIR__);

// Define constants
define('BASE_PATH', realpath(__DIR__ . '/..'));
define('APP_PATH', BASE_PATH . '/app');
define('TEST_PATH', BASE_PATH . '/tests');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Create storage directories
$directories = [
    STORAGE_PATH,
    STORAGE_PATH . '/cache',
    STORAGE_PATH . '/logs',
    STORAGE_PATH . '/tests'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Set up test environment
putenv('APP_ENV=testing');

// Initialize test application
$app = \App\Core\Application::getInstance();

// Set up test configuration
$config = \App\Core\Config::getInstance();
$config->set('database.test', [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => ''
]);

// Register test services in container
$container = $app->getContainer();
$container->singleton(\App\Core\Config::class, fn() => $config);

// Helper functions
if (!function_exists('base_path')) {
    function base_path($path = '') {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return APP_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('test_path')) {
    function test_path($path = '') {
        return TEST_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return STORAGE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

// Clean up function
register_shutdown_function(function() {
    // Clean up test storage
    if (is_dir(STORAGE_PATH . '/tests')) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(STORAGE_PATH . '/tests', \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir(STORAGE_PATH . '/tests');
    }
});

// Ensure PHPUnit is loaded
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    throw new RuntimeException('PHPUnit is not installed. Run composer require --dev phpunit/phpunit');
}
