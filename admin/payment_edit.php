<?php
require_once '../config.php';
check_auth();

$page_title = 'Edit Payment';
$_SESSION['active_menu'] = 'payments';

// Get payment ID
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    $_SESSION['error'] = "Invalid payment ID";
    header("Location: payments.php");
    exit();
}

// Get payment details with related information
$query = "SELECT p.*,
          b.invoiceid, b.amount as billing_amount, b.status as billing_status,
          c.name as customer_name, c.id as customer_id
          FROM payments p
          LEFT JOIN billing b ON p.billing_id = b.id
          LEFT JOIN customers c ON b.customer_id = c.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    $_SESSION['error'] = "Payment not found";
    header("Location: payments.php");
    exit();
}

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Edit Payment</h1>
            <div class="btn-toolbar gap-2">
                <a href="payment_details.php?id=<?php echo $payment_id; ?>" class="btn btn-outline-info">
                    <i class='bx bx-show'></i> View Details
                </a>
                <a href="payments.php" class="btn btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Back to List
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="payment_save.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="id" value="<?php echo $payment_id; ?>">
                            <input type="hidden" name="customer_id" value="<?php echo $payment['customer_id']; ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" name="amount"
                                               value="<?php echo $payment['amount']; ?>" step="0.01" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Payment Date</label>
                                    <input type="datetime-local" class="form-control" name="payment_date"
                                           value="<?php echo date('Y-m-d\TH:i', strtotime($payment['payment_date'])); ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method_id" required>
                                        <option value="">Select payment method</option>
                                        <?php
                                        // Get payment methods from database
                                        $methods = $conn->query("SELECT id, name FROM payment_methods WHERE status = 'active'");
                                        while ($method = $methods->fetch_assoc()) {
                                            $selected = ($method['id'] == $payment['payment_method_id']) ? 'selected' : '';
                                            echo '<option value="' . $method['id'] . '" ' . $selected . '>' .
                                                 htmlspecialchars($method['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="completed" <?php echo $payment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="pending" <?php echo $payment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo $payment['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" name="reference_number"
                                           value="<?php echo htmlspecialchars($payment['reference_number'] ?? ''); ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class='bx bx-save'></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
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
                        <p class="mb-0"><strong>Name:</strong> <?php echo htmlspecialchars($payment['customer_name']); ?></p>
                    </div>
                </div>

                <!-- Invoice Information -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Invoice Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Invoice ID:</strong> <?php echo htmlspecialchars($payment['invoiceid']); ?></p>
                        <p><strong>Amount:</strong> ₱<?php echo number_format($payment['billing_amount'], 2); ?></p>
                        <p class="mb-0"><strong>Status:</strong>
                            <span class="badge bg-<?php echo $payment['billing_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($payment['billing_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
