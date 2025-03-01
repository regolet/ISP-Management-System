<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Store the requested URL for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
    
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Check if session has expired (optional, set to 30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Destroy session and redirect to login
    session_unset();
    session_destroy();
    header('Location: login.php?msg=session_expired');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Check user role for restricted pages
function checkUserRole($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header('Location: unauthorized.php');
        exit();
    }
}
?>
