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

// Initialize Client Controller
$clientController = new \App\Controllers\ClientController($db);
$subscriptionController = new \App\Controllers\SubscriptionController($db);

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug log
        error_log("Form submitted with data: " . print_r($_POST, true));
        
        // Ensure database connection is valid
        error_log("Database path: " . $database->db_path);
        error_log("Database file exists: " . (file_exists($database->db_path) ? "Yes" : "No"));
        
        // Create client
        $result = $clientController->createClient($_POST);
        
        // Debug log
        error_log("Client creation result: " . print_r($result, true));
        
        if ($result['success']) {
            // Set success message in session
            $_SESSION['flash_message'] = 'Client added successfully!';
            $_SESSION['flash_message_type'] = 'success';
            
            // Create subscription
            $subscriptionData = [
                'client_id' => $result['client']['id'],
                'plan_name' => $_POST['plan_name'],
                'status' => 'active',
                'start_date' => date('Y-m-d')
            ];
            $subscriptionResult = $subscriptionController->createSubscription($subscriptionData);

            if (!$subscriptionResult['success']) {
                $message = 'Error creating subscription: ' . $subscriptionResult['message'];
                $messageType = 'danger';
            } else {
                // Redirect after successful creation
                header("Location: /clients.php");
                exit();
            }
        } else {
            $message = 'Error: ' . $result['message'];
            $messageType = 'danger';
        }
    } catch (\Exception $e) {
        error_log("Exception in client creation: " . $e->getMessage());
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Client - ISP Management System</title>

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
                <h1 class="h3 mb-0">Add New Client</h1>
                <a href="/clients.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Clients
                </a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Client Form -->
            <div class="card">
                <div class="card-body">
                    <form id="clientForm" method="POST" action="">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address">
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                            <div class="col-md-4">
                                <label for="postalCode" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postalCode" name="postal_code">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="connectionDate" class="form-label">Connection Date</label>
                                <input type="date" class="form-control" id="connectionDate" name="connection_date">
                            </div>
                            <div class="col-md-6">
                                <label for="planName" class="form-label">Plan Name</label>
                                <input type="text" class="form-control" id="planName" name="plan_name" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Client
                                </button>
                                <a href="/clients.php" class="btn btn-secondary ms-2">
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
    <script src="/assets/js/clients.js"></script>
</body>
</html>