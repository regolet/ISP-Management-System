<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'payments';
$page_title = 'Payments';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query with search
$query = "SELECT 
    p.*, 
    c.name as customer_name,
    c.customer_code,
    b.invoiceid,
    b.amount as invoice_amount
FROM payments p 
LEFT JOIN billing b ON p.billing_id = b.id
LEFT JOIN customers c ON b.customer_id = c.id
WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (
        b.invoiceid LIKE '%$search%' OR 
        c.name LIKE '%$search%' OR
        c.customer_code LIKE '%$search%'
    )";
}

$query .= " ORDER BY p.payment_date DESC";
$result = $conn->query($query);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Payments</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                onclick="window.location.href='payment_add.php'">
            <i class="bx bx-plus"></i>
            <span>Record Payment</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search payments..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Payment Date</th>
                            <th>Customer</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($payment = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['customer_code']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($payment['invoiceid']); ?></div>
                                        <small class="text-muted">₱<?php echo number_format($payment['invoice_amount'], 2); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-medium">₱<?php echo number_format($payment['amount'], 2); ?></div>
                                        <?php if ($payment['reference_no']): ?>
                                            <small class="text-muted">Ref: <?php echo htmlspecialchars($payment['reference_no']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($payment['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($payment['status']) {
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'void' => 'secondary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="payment_details.php?id=<?php echo $payment['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <?php if ($payment['status'] === 'pending'): ?>
                                                <a href="payment_edit.php?id=<?php echo $payment['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="voidPayment(<?php echo $payment['id']; ?>)">
                                                    <i class="bx bx-x"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 mb-2"></i>
                                    <p class="mb-0">No payments found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function voidPayment(id) {
    if (confirm('Are you sure you want to void this payment?')) {
        window.location.href = `payment_void.php?id=${id}`;
    }
}
</script>

<?php include 'footer.php'; ?>
