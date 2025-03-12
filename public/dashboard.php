<?php
// Start session
session_start();

// Load initialization file
require_once dirname(__DIR__) . '/app/init.php';

// Require authentication
require_auth();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Dashboard Controller
$dashboardController = new \App\Controllers\DashboardController($db);

// Get dashboard data
$dashboardData = $dashboardController->getDashboardData();

// Log dashboard access
$dashboardController->logActivity(
    $_SESSION['user_id'],
    null,
    'page_access',
    'Accessed dashboard',
    $_SERVER['REMOTE_ADDR']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('dashboard'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content">
            <?php renderDashboardContent($dashboardData); ?>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/sidebar.js"></script>

    <script>
        // Set global variables
        window.APP_CONFIG = {
            baseUrl: <?php echo json_encode(rtrim((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/')); ?>,
            csrfToken: <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>,
            userRole: <?php echo json_encode($_SESSION['role'] ?? ''); ?>,
            debug: <?php echo defined('DEBUG_MODE') && DEBUG_MODE ? 'true' : 'false'; ?>
        };

        // Handle AJAX errors globally
        $(document).ajaxError(function(event, jqXHR, settings, error) {
            if (jqXHR.status === 401) {
                window.location.href = '/login.php';
            } else {
                console.error('AJAX Error:', error);
            }
        });

        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
            }
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>