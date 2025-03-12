
<?php
// Prevent endless redirects
session_start();

// Reset redirect counter if we're on a new page request
if (!isset($_SESSION['last_request_uri']) || $_SESSION['last_request_uri'] !== $_SERVER['REQUEST_URI']) {
    $_SESSION['redirect_count'] = 0;
    $_SESSION['last_request_uri'] = $_SERVER['REQUEST_URI'];
}

// Make sure we haven't been here before to prevent redirect loops
if (isset($_SESSION['redirect_count']) && $_SESSION['redirect_count'] > 3) {
    // Reset the redirect counter
    $_SESSION['redirect_count'] = 0;
    
    echo "<html><body>";
    echo "<h1>Redirect Loop Detected</h1>";
    echo "<p>The system detected too many redirects. This could be due to:</p>";
    echo "<ul>";
    echo "<li>Missing or incorrect JavaScript files</li>";
    echo "<li>Authentication issues</li>";
    echo "<li>Server configuration problems</li>";
    echo "</ul>";
    
    echo "<p>Debug information:</p>";
    echo "<pre>";
    echo "URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "\n";
    echo "Session data: \n";
    foreach ($_SESSION as $key => $value) {
        if (is_string($value)) {
            echo "$key: " . htmlspecialchars($value) . "\n";
        } else {
            echo "$key: (complex data)\n";
        }
    }
    echo "</pre>";
    
    echo "<p><a href='/login.php?clear=1'>Go to login page</a></p>";
    echo "</body></html>";
    exit;
}

// Increment redirect counter
$_SESSION['redirect_count'] = ($_SESSION['redirect_count'] ?? 0) + 1;

// Include necessary files
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    // Clear redirect counter before redirecting to login
    $_SESSION['redirect_count'] = 0;
    header("Location: /login.php");
    exit();
}

// Clear redirect counter before redirecting to dashboard
$_SESSION['redirect_count'] = 0;
header("Location: /dashboard.php");
exit();
?>
