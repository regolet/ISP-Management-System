<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/SubscriptionController.php';


require_once dirname(__DIR__) . '/views/layouts/sidebar.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Controllers
$subscriptionController = new \App\Controllers\SubscriptionController($db);


// Get page parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'ASC';

// Get subscriptions data
$subscriptionsData = $subscriptionController->getSubscriptions([
    'page' => $page,
    'per_page' => 10,
    'search' => $search,
    'status' => $status,
    'sort' => $sort,
    'order' => $order
]);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <!--<link href="/assets/css/dashboard.css" rel="stylesheet">-->
</head>
<body>
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
                <h1 class="h3 mb-0">Subscriptions</h1>
                <a href="/forms/subscriptions/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Subscription
                </a>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <input type="text" class="form-control" id="search" name="search"
                                       placeholder="Search subscriptions..." value="<?php echo htmlspecialchars($search); ?>">
                                <?php if (!empty($search)): ?>
                                    <span class="clear-search" onclick="clearSearch()">×</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="sort" onchange="this.form.submit()">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Sort by Date</option>
                                <option value="subscription_number" <?php echo $sort === 'subscription_number' ? 'selected' : ''; ?>>Sort by Number</option>
                                <option value="client_id" <?php echo $sort === 'client_id' ? 'selected' : ''; ?>>Sort by Client</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscriptions Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Subscription #</th>
                                    <th>Client</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>Billing Cycle</th>
                                    <th>Monthly Fee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscriptionsData['data'] as $subscription): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subscription['subscription_number']); ?></td>
                                        <td>
                                            <?php 
                                                $clientName = $subscription['first_name'] . ' ' . $subscription['last_name'];
                                                echo htmlspecialchars($clientName ?? 'No client info'); 
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($subscription['plan_name'] ?? 'No plan info'); ?></td>
                                        <td>
                                            <span class="subscription-status status-<?php echo $subscription['status']; ?>"></span>
                                            <?php echo ucfirst($subscription['status']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></td>
                                        <td><?php echo ucfirst($subscription['billing_cycle']); ?></td>
                                        <td>$<?php echo number_format($subscription['plan_price'] ?? 0.00, 2); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/forms/subscriptions/view.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/forms/subscriptions/edit.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/forms/subscriptions/delete.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($subscriptionsData['total_pages'] > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $subscriptionsData['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Subscription Modal -->
    <div class="modal fade" id="subscriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="subscriptionForm">
                        <input type="hidden" id="subscriptionId" name="id">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Plan</label>
                                <select class="form-select" id="planId" name="plan_id" required>
                                    <option value="">Select Plan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IP Address</label>
                                <input type="text" class="form-control" id="ipAddress" name="ip_address"
                                       pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate" name="end_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Billing Cycle</label>
                                <select class="form-select" id="billingCycle" name="billing_cycle" required>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSubscription()">Save Subscription</button>
                </div>
            </div>
        </div>
    </div>

        <!-- View Subscription Modal -->
    <div class="modal fade" id="viewSubscriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewSubscriptionModalTitle">Subscription Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewSubscriptionModalBody">
                    <!-- Subscription details will be loaded here -->
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
            baseUrl: <?php echo json_encode(rtrim((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/')); ?>,
            csrfToken: <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>,
            userRole: <?php echo json_encode($_SESSION['role'] ?? ''); ?>
        };
    </script>

    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
    <script src="/assets/js/subscriptions.js"></script>

    <!-- Mobile toggle button moved to main dashboard container -->

    <script>
        // Initialize modals when the document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing modals');
            if (typeof bootstrap !== 'undefined') {
                // Initialize subscription modal
                const subscriptionModalEl = document.getElementById('subscriptionModal');
                if (subscriptionModalEl) {
                    window.subscriptionModal = new bootstrap.Modal(subscriptionModalEl);
                    console.log('Subscription modal initialized');
                }
            } else {
                console.error('Bootstrap is not loaded properly');
            }
        });
    </script>
</body>
</html>