<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

// Make sure staff is linked to an employee
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

$page_title = "Payment History";
$_SESSION['active_menu'] = 'payments';

// Get payment history with correct column names
$stmt = $conn->prepare("
    SELECT p.*, 
           b.invoiceid,
           b.billtocustomer as customer_name
    FROM payments p
    LEFT JOIN billing b ON p.billing_id = b.id
    WHERE p.created_by = ?
    ORDER BY p.payment_date DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payments = $stmt->get_result();

// Get payment totals
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_amount,
        COUNT(DISTINCT billing_id) as unique_billings
    FROM payments 
    WHERE created_by = ? AND status = 'completed'
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

include '../../header.php';
include '../staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="h3 mb-0">Payment History</h1>
            </div>
            <div class="col-md-6 text-end">
                <a href="add.php" class="btn btn-primary">
                    <i class='bx bx-plus'></i> Record New Payment
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary bg-opacity-10 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-primary">Total Collections</h6>
                        <h3 class="mb-0">₱<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-10 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-success">Total Transactions</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_payments'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info bg-opacity-10 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-info">Unique Customers</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['unique_billings'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice ID</th>  <!-- Changed from Invoice # -->
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Reference #</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments->num_rows > 0):
                                while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['invoiceid'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['customer_name'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($payment['status']) {
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                default => 'warning'
                                            };
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $payment['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No payment records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../footer.php'; ?>
