<?php
require_once dirname(__DIR__, 3) . '/app/init.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/views/layouts/sidebar.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/ClientController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/SubscriptionController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Controllers
$clientController = new \App\Controllers\ClientController($db);
$subscriptionController = new \App\Controllers\SubscriptionController($db);

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

// Get client information if client_id exists
$client = null;
if (isset($subscription['client_id']) && !empty($subscription['client_id'])) {
    $client = $clientController->getClient($subscription['client_id']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Subscription - ISP Management System</title>

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
                <h1 class="h3 mb-0">Subscription Details</h1>
                <div>
                    <a href="/forms/subscriptions/edit.php?id=<?php echo $subscription['id']; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit Subscription
                    </a>
                    <a href="/subscriptions.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Subscriptions
                    </a>
                </div>
            </div>

            <!-- Subscription Details -->
            <div class="row">
                <!-- Subscription Information -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Subscription Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Subscription Number</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($subscription['subscription_number'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Status</h6>
                                    <p class="mb-0">
                                        <?php
                                        $statusClass = 'secondary';
                                        if ($subscription['status'] === 'active') $statusClass = 'success';
                                        if ($subscription['status'] === 'inactive') $statusClass = 'warning';
                                        if ($subscription['status'] === 'suspended') $statusClass = 'danger';
                                        if ($subscription['status'] === 'cancelled') $statusClass = 'dark';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($subscription['status'])); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Plan Name</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($subscription['plan_name'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Monthly Fee</h6>
                                    <p class="mb-0">$<?php echo number_format($subscription['plan_price'] ?? 0, 2); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Billing Cycle</h6>
                                    <p class="mb-0"><?php echo ucfirst(htmlspecialchars($subscription['billing_cycle'] ?? 'N/A')); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Start Date</h6>
                                    <p class="mb-0"><?php echo !empty($subscription['start_date']) ? date('F d, Y', strtotime($subscription['start_date'])) : 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Created At</h6>
                                    <p class="mb-0"><?php echo !empty($subscription['created_at']) ? date('F d, Y H:i', strtotime($subscription['created_at'])) : 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Last Updated</h6>
                                    <p class="mb-0"><?php echo !empty($subscription['updated_at']) ? date('F d, Y H:i', strtotime($subscription['updated_at'])) : 'N/A'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Information -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Client Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($client): ?>
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Name</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                                </div>
                                <?php if (!empty($client['email'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">Email</h6>
                                        <p class="mb-0">
                                            <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($client['phone'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">Phone</h6>
                                        <p class="mb-0">
                                            <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>"><?php echo htmlspecialchars($client['phone']); ?></a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="/forms/clients/view.php?id=<?php echo $client['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user me-2"></i>View Client Profile
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>No client information available.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="/forms/subscriptions/edit.php?id=<?php echo $subscription['id']; ?>" class="btn btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Subscription
                                </a>
                                <?php if ($auth->hasRole('admin')): ?>
                                    <button type="button" class="btn btn-danger" onclick="deleteSubscription(<?php echo $subscription['id']; ?>)">
                                        <i class="fas fa-trash me-2"></i>Delete Subscription
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Initialize global configuration -->
    <script>
        // Initialize global configuration
        window.APP_CONFIG = {
            baseUrl: <?php echo json_encode(rtrim((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 3), '/')); ?>,
            csrfToken: <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>,
            userRole: <?php echo json_encode($_SESSION['role'] ?? ''); ?>
        };
    </script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
    <script>
        // Delete subscription
        function deleteSubscription(id) {
            if (confirm('Are you sure you want to delete this subscription? This action cannot be undone.')) {
                fetch(`/api/subscriptions.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Subscription deleted successfully!');
                        window.location.href = '/subscriptions.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the subscription.');
                });
            }
        }
    </script>
</body>
</html>