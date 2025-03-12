<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/BillingController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    $_SESSION['error'] = "You don't have permission to delete payments";
    header("Location: /payments.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Billing Controller
$billingController = new \App\Controllers\BillingController($db);

// Check if payment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Payment ID is required";
    header("Location: /payments.php");
    exit();
}

$paymentId = (int)$_GET['id'];

// Get payment details before deletion for confirmation
$payment = $billingController->getPayment($paymentId);

if (!$payment) {
    $_SESSION['error'] = "Payment not found";
    header("Location: /payments.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete the payment
    $result = $billingController->deletePayment($paymentId);
    
    if ($result['success']) {
        $_SESSION['success'] = "Payment deleted successfully";
    } else {
        $_SESSION['error'] = $result['message'] ?? "Failed to delete payment";
    }
    
    header("Location: /payments.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Payment - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Main Content -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Delete Payment</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Are you sure you want to delete this payment? This action cannot be undone.
                        </div>
                        
                        <div class="payment-details mb-4">
                            <h6>Payment Details</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Payment Number:</th>
                                    <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Invoice Number:</th>
                                    <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Client:</th>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Amount:</th>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Payment Date:</th>
                                    <td><?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Payment Method:</th>
                                    <td><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><?php echo ucfirst($payment['status']); ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Deleting this payment will update the associated invoice status.
                        </div>
                        
                        <form method="post">
                            <div class="d-flex justify-content-between">
                                <a href="/payments.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" name="confirm_delete" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i>Delete Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>