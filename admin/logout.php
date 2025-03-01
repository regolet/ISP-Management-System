<?php
require_once '../init.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Store user_id before destroying session
    try {
        log_activity($user_id, 'logout', 'User logged out');
    } catch (Exception $e) {
        error_log("Logout activity logging failed: " . $e->getMessage());
    }
    session_destroy();
    session_start(); // Start a new session for potential messages
    $_SESSION['success'] = "You have been successfully logged out.";
}

header("Location: ../login.php");
exit();
?>
