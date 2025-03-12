<?php
// Load initialization file
require_once dirname(__DIR__) . '/includes/init.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Log the logout activity if user was logged in
if ($auth->isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    //log_activity('logout', 'User logged out successfully', $user_id); // Assuming log_activity is defined elsewhere
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
//redirect_with_message('login.php', 'You have been successfully logged out.', 'success'); // Assuming redirect_with_message is defined elsewhere
header("Location: login.php");
exit();
