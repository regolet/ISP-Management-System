<?php
// Include initialization file
require_once '../app/init.php';

// Check if user is logged in
require_auth();

// Manually include the DashboardController class
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create an instance of the DashboardController
$dashboardController = new App\Controllers\DashboardController($db);

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

// Include header
include __DIR__ . '/../views/layouts/header.php';

// Include sidebar
include __DIR__ . '/../views/layouts/sidebar.php';

// Load dashboard content
require_once __DIR__ . '/dashboard-content.php';
renderDashboardContent($dashboardData);

// Include footer
include __DIR__ . '/../views/layouts/footer.php';

?>
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