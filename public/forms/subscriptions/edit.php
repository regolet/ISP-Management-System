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
    $client = $clientController->getClientById($subscription['client_id']);
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug log
        error_log("Form submitted with data: " . print_r($_POST, true));
        
        // Add subscription ID to the data
        $_POST['id'] = $subscriptionId;
        
        $result = $subscriptionController->updateSubscription($subscriptionId, $_POST);
        
        // Debug log
        error_log("Subscription update result: " . print_r($result, true));
        
        if ($result['success']) {
            // Set success message in session
            $_SESSION['flash_message'] = 'Subscription updated successfully!';
            $_SESSION['flash_message_type'] = 'success';
            
            // Redirect after successful update
            header("Location: /forms/subscriptions/view.php?id=" . $subscriptionId);
            exit();
        } else {
            $message = 'Error: ' . $result['message'];
            $messageType = 'danger';
        }
    } catch (\Exception $e) {
        error_log("Exception in subscription update: " . $e->getMessage());
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch all plans from the database
function get_all_plans() {
    global $db;
    $query = "SELECT * FROM plans";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$plans = get_all_plans();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subscription - ISP Management System</title>

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
                <h1 class="h3 mb-0">Edit Subscription</h1>
                <div>
                    <a href="/forms/subscriptions/view.php?id=<?php echo $subscriptionId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Subscription
                    </a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Subscription Form -->
            <div class="card">
                <div class="card-body">
                    <form id="subscriptionForm" method="POST" action="">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        
                        <?php if ($client): ?>
                            <!-- If client_id is provided, show client info -->
                            <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                            <div class="alert alert-info mb-4">
                                <h5 class="alert-heading">Client Information</h5>
                                <p class="mb-0">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?><br>
                                    <?php if (!empty($client['email'])): ?>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($client['phone'])): ?>
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($client['phone']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- If client_id is not available, show a warning -->
                            <div class="alert alert-warning mb-4">
                                <h5 class="alert-heading">No Client Associated</h5>
                                <p class="mb-0">
                                    This subscription is not associated with any client. You may need to update the database to link it to a client.
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($subscription['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($subscription['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo ($subscription['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="cancelled" <?php echo ($subscription['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="planId" class="form-label">Plan</label>
                                <select class="form-select" id="planId" name="plan_id" required>
                                    <option value="">Select Plan</option>
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?php echo htmlspecialchars($plan['id']); ?>" <?php echo ($subscription['plan_id'] == $plan['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($plan['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="billingCycle" class="form-label">Billing Cycle</label>
                                <select class="form-select" id="billingCycle" name="billing_cycle">
                                    <option value="monthly" <?php echo ($subscription['billing_cycle'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="quarterly" <?php echo ($subscription['billing_cycle'] == 'quarterly') ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="yearly" <?php echo ($subscription['billing_cycle'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo date('Y-m-d', strtotime($subscription['start_date'])); ?>">
                            </div>
                            <div class="col-md-12">
                                <label for="identifier" class="form-label">Identifier (Optional)</label>
                                <input type="text" class="form-control" id="identifier" name="identifier" placeholder="Custom identifier for this subscription" value="<?php echo htmlspecialchars($subscription['identifier'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Subscription
                                </button>
                                <a href="/forms/subscriptions/view.php?id=<?php echo $subscriptionId; ?>" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Add any form validation or dynamic behavior here
        });
    </script>
</body>
</html>