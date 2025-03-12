<?php
// Prevent endless redirects
session_start();

// Make sure we haven't been here before to prevent redirect loops
if (isset($_SESSION['redirect_count']) && $_SESSION['redirect_count'] > 3) {
    echo "<html><body>";
    echo "<h1>Redirect Loop Detected</h1>";
    echo "<p>The system detected too many redirects. This could be due to:</p>";
    echo "<ul>";
    echo "<li>Missing or incorrect JavaScript files</li>";
    echo "<li>Authentication issues</li>";
    echo "<li>Server configuration problems</li>";
    echo "</ul>";
    echo "<p>Please check your server logs for more information.</p>";
    echo "<p><a href='lcp.php'>Try accessing the LCP page directly</a></p>";
    echo "</body></html>";
    
    // Reset the redirect counter
    $_SESSION['redirect_count'] = 0;
    exit;
}

// Increment redirect counter
$_SESSION['redirect_count'] = ($_SESSION['redirect_count'] ?? 0) + 1;

// Normal index behavior
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController(); // Use fully qualified name

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Redirect to dashboard
header("Location: dashboard.php");
exit();
?>
