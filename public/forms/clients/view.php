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
    header("Location: /clients.php?error=missing_id");
    exit();
}

$clientId = (int)$_GET['id'];
$client = $clientController->getClientById($clientId);

// Check if client exists
if (!$client) {
    header("Location: /clients.php?error=client_not_found");
    exit();
}

// Get client subscriptions
try {
    $subscriptions = $subscriptionController->getSubscriptions([
        'client_id' => $clientId,
        'per_page' => 100
    ]);
} catch (\Exception $e) {
    // If there's an error (like missing column), just show empty subscriptions
    error_log("Error getting subscriptions: " . $e->getMessage());
    $subscriptions = ['subscriptions' => [], 'total' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Client - ISP Management System</title>

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
    <?php renderSidebar('clients'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Client Details</h1>
                <div>
                    <a href="/forms/clients/edit.php?id=<?php echo $client['id']; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit Client
                    </a>
                    <a href="/clients.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Clients
                    </a>
                </div>
            </div>

            <!-- Client Details -->
            <div class="row">
                <!-- Client Information -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Client Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Client Number</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($client['client_number'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Status</h6>
                                    <p class="mb-0">
                                        <?php
                                        $statusClass = 'secondary';
                                        if ($client['status'] === 'active') $statusClass = 'success';
                                        if ($client['status'] === 'inactive') $statusClass = 'warning';
                                        if ($client['status'] === 'suspended') $statusClass = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($client['status'])); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Full Name</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Connection Date</h6>
                                    <p class="mb-0"><?php echo !empty($client['connection_date']) ? date('F d, Y', strtotime($client['connection_date'])) : 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Email</h6>
                                    <p class="mb-0">
                                        <?php if (!empty($client['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Phone</h6>
                                    <p class="mb-0">
                                        <?php if (!empty($client['phone'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>"><?php echo htmlspecialchars($client['phone']); ?></a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-12 mb-3">
                                    <h6 class="text-muted mb-1">Address</h6>
                                    <p class="mb-0">
                                        <?php
                                        $addressParts = [];
                                        if (!empty($client['address'])) $addressParts[] = $client['address'];
                                        if (!empty($client['city'])) $addressParts[] = $client['city'];
                                        if (!empty($client['state'])) $addressParts[] = $client['state'];
                                        if (!empty($client['postal_code'])) $addressParts[] = $client['postal_code'];
                                        
                                        echo !empty($addressParts) ? htmlspecialchars(implode(', ', $addressParts)) : 'N/A';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Created At</h6>
                                    <p class="mb-0"><?php echo !empty($client['created_at']) ? date('F d, Y H:i', strtotime($client['created_at'])) : 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Last Updated</h6>
                                    <p class="mb-0"><?php echo !empty($client['updated_at']) ? date('F d, Y H:i', strtotime($client['updated_at'])) : 'N/A'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Actions -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="/forms/clients/edit.php?id=<?php echo $client['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Client
                                </a>
                                <?php if ($auth->hasRole('admin')): ?>
                                    <button type="button" class="btn btn-danger" onclick="deleteClient(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-trash me-2"></i>Delete Client
                                    </button>
                                <?php endif; ?>
                                <a href="/subscriptions.php?client_id=<?php echo $client['id']; ?>" class="btn btn-info">
                                    <i class="fas fa-project-diagram me-2"></i>View Subscriptions
                                </a>
                                <a href="/forms/subscriptions/add.php?client_id=<?php echo $client['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Add Subscription
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Subscriptions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Subscriptions</h5>
                    <a href="/forms/subscriptions/add.php?client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Subscription
                    </a>
                </div>
                <div class="card-body">
                    <?php 
                    // Check if we have subscriptions
                    $hasSubscriptions = !empty($subscriptions['subscriptions']) || !empty($subscriptions['data']);
                    $subscriptionsList = !empty($subscriptions['subscriptions']) ? $subscriptions['subscriptions'] : ($subscriptions['data'] ?? []);
                    
                    if (!$hasSubscriptions): 
                    ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No subscriptions found for this client. <a href="/forms/subscriptions/add.php?client_id=<?php echo $client['id']; ?>" class="alert-link">Add a subscription</a>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subscription #</th>
                                        <th>Plan</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>Billing Cycle</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptionsList as $subscription): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subscription['subscription_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($subscription['plan_name'] ?? 'N/A'); ?></td>
                                            <td>
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
                                            </td>
                                            <td><?php echo !empty($subscription['start_date']) ? date('M d, Y', strtotime($subscription['start_date'])) : 'N/A'; ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($subscription['billing_cycle'] ?? 'N/A')); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/forms/subscriptions/view.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/forms/subscriptions/edit.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
    <script src="/assets/js/clients.js"></script>
    
    <script>
        // Delete client
        function deleteClient(id) {
            if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
                fetch(`/api/clients.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Client deleted successfully!');
                        window.location.href = '/clients.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the client.');
                });
            }
        }
    </script>
</body>
</html>