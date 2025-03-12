<?php
// Start session
session_start();

// Autoloader function with browser output debugging
function autoload($class) {
    $projectRoot = '../'; 
    $path = $projectRoot . str_replace('\\', '/', $class) . '.php'; 

    error_log("Autoloader: Class: " . $class); 
    error_log("Autoloader: Path: " . $path); 

    if (file_exists($path)) {
        error_log("Autoloader: File found: " . $path); 
        require_once $path;
    } else {
        error_log("Autoloader: File NOT found: " . $path); 
    }
}

// Register autoloader
spl_autoload_register('autoload');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Redirect to login page
header("Location: login.php");
exit();
