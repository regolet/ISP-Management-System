<?php
require_once dirname(__DIR__, 3) . '/app/init.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/views/layouts/sidebar.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/SubscriptionController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/ClientController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/PlanController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    header("Location: /subscriptions.php?error=permission_denied");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Controllers
$subscriptionController = new \App\Controllers\SubscriptionController($db);
$clientController = new \App\Controllers\ClientController($db);
$planController = new \App\Controllers\PlanController($db);

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: /subscriptions.php?error=missing_id");
    exit();
}

$subscriptionId = (int)$_GET['id'];
$subscription = $subscriptionController->getSubscription($subscriptionId);

// Check if subscription exists
if (!$subscription) {
    header("Location: /subscriptions.php?error=subscription_not_found");
    exit();
}

// Get client and plan details
$client = $clientController->getClient($subscription['client_id']);
$plan = $planController->getPlan($subscription['plan_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: /subscriptions.php?error=invalid_token");
        exit();
    }
    
    try {
        // Debug log
        error_log("Attempting to delete subscription with ID: " . $subscriptionId);
        
        $result = $subscriptionController->deleteSubscription($subscriptionId);
        
        // Debug log
        error_log("Subscription delete result: " . print_r($result, true));
        
        if ($result['success']) {
            // Set success message in session
            $_SESSION['flash_message'] = 'Subscription deleted successfully!';
            $_SESSION['flash_message_type'] = 'success';
            
            // Redirect after successful deletion
            header("Location: /subscriptions.php");
            exit();
        } else {
            // Set error message in session
            $_SESSION['flash_message'] = 'Error: ' . $result['message'];
            $_SESSION['flash_message_type'] = 'danger';
            
            // Redirect with error
            header("Location: /subscriptions.php?error=delete_failed");
            exit();
        }
    } catch (\Exception $e) {
        error_log("Exception in subscription deletion: " . $e->getMessage());
        
        // Set error message in session
        $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
        $_SESSION['flash_message_type'] = 'danger';
        
        // Redirect with error
        header("Location: /subscriptions.php?error=exception");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Subscription - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Immediate backdrop cleanup -->
    <script src="/assets/js/backdrop-cleanup.js"></script>
    
    <!-- Render Sidebar -->
    <?php renderSidebar('subscriptions'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Delete Subscription</h1>
                <a href="/subscriptions.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Subscriptions
                </a>
            </div>

            <!-- Delete Confirmation Card -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Confirm Deletion</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. All data associated with this subscription will be permanently deleted.
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Subscription Details</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Subscription Number</th>
                                    <td><?php echo htmlspecialchars($subscription['subscription_number'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Client</th>
                                    <td><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Plan</th>
                                    <td><?php echo htmlspecialchars($plan['name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Start Date</th>
                                    <td><?php echo htmlspecialchars($subscription['start_date'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($subscription['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($subscription['status'] === 'inactive'): ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php elseif ($subscription['status'] === 'suspended'): ?>
                                            <span class="badge bg-warning">Suspended</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        
                        <div class="d-flex justify-content-end">
                            <a href="/subscriptions.php" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
</body>
</html>