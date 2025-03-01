<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'billing';
$page_title = 'Billing';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query with search
$query = "SELECT 
    b.*, 
    c.name as customer_name,
    c.customer_code,
    p.name as plan_name,
    p.amount as plan_amount
FROM billing b 
LEFT JOIN customers c ON b.customer_id = c.id
LEFT JOIN plans p ON c.plan_id = p.id
WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (
        b.invoiceid LIKE '%$search%' OR 
        c.name LIKE '%$search%' OR
        c.customer_code LIKE '%$search%'
    )";
}

$query .= " ORDER BY b.due_date ASC";
$result = $conn->query($query);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Billing</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                onclick="window.location.href='billing_form.php'">
            <i class="bx bx-plus"></i>
            <span>Create Invoice</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search invoices..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Billing Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($bill = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($bill['invoiceid']); ?></div>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($bill['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($bill['customer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($bill['customer_code']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-medium">₱<?php echo number_format($bill['amount'], 2); ?></div>
                                        <?php if ($bill['plan_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($bill['plan_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $due_date = strtotime($bill['due_date']);
                                        $today = strtotime('today');
                                        $is_overdue = $due_date < $today && $bill['status'] !== 'paid';
                                        ?>
                                        <div class="<?php echo $is_overdue ? 'text-danger fw-medium' : ''; ?>">
                                            <?php echo date('M d, Y', $due_date); ?>
                                        </div>
                                        <?php if ($is_overdue): ?>
                                            <small class="text-danger">Overdue</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($bill['status']) {
                                                'paid' => 'success',
                                                'unpaid' => 'danger',
                                                'partial' => 'warning',
                                                'void' => 'secondary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($bill['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="billing_view.php?id=<?php echo $bill['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <?php if ($bill['status'] !== 'paid' && $bill['status'] !== 'void'): ?>
                                                <a href="billing_form.php?id=<?php echo $bill['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <a href="payment_add.php?billing_id=<?php echo $bill['id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="bx bx-money"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 mb-2"></i>
                                    <p class="mb-0">No invoices found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
