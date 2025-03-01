<?php
require_once '../config.php';
check_auth();

include 'header.php';
include 'navbar.php';

// Get payment ID from URL
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    $_SESSION['error'] = "Invalid payment ID";
    header("Location: payments.php");
    exit();
}

// Get payment details with related information
$query = "SELECT p.*, 
          b.invoiceid, b.amount as billing_amount, b.status as billing_status, 
          c.name as customer_name, c.id as customer_id,
          pm.name as payment_method_name
          FROM payments p
          LEFT JOIN billing b ON p.billing_id = b.id
          LEFT JOIN customers c ON b.customer_id = c.id
          LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Payment not found";
    header("Location: payments.php");
    exit();
}

$payment = $result->fetch_assoc();
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Payment Details</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="payments.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-arrow-back"></i> Back to Payments
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Payment Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Payment ID</h6>
                                <p class="mb-0">#<?php echo $payment['id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Status</h6>
                                <span class="badge bg-<?php 
                                    echo match($payment['status']) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Amount</h6>
                                <p class="mb-0">₱<?php echo number_format($payment['amount'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Payment Date</h6>
                                <p class="mb-0"><?php echo date('M d, Y g:i A', strtotime($payment['payment_date'])); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Payment Method</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($payment['payment_method_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Reference Number</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="text-muted">Notes</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($payment['notes'] ?? 'No notes available')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Invoice Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Invoice ID</h6>
                                <p class="mb-0">
                                    <a href="billing_view.php?id=<?php echo $payment['billing_id']; ?>">
                                        <?php echo htmlspecialchars($payment['invoiceid'] ?? 'N/A'); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Invoice Amount</h6>
                                <p class="mb-0">₱<?php echo number_format($payment['billing_amount'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Invoice Status</h6>
                                <span class="badge bg-<?php 
                                    echo match($payment['billing_status']) {
                                        'paid' => 'success',
                                        'unpaid' => 'warning',
                                        'overdue' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($payment['billing_status'] ?? 'Unknown'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Customer Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($payment['customer_name']); ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted">Created At</h6>
                            <p class="mb-0"><?php echo date('M d, Y g:i A', strtotime($payment['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add any additional JavaScript functionality here
</script>
