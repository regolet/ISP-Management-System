<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'subscriptions';
$page_title = 'Subscriptions';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query with search
$query = "SELECT 
    s.*, 
    c.name as customer_name,
    c.customer_code,
    p.name as plan_name,
    p.amount as plan_amount
FROM subscriptions s 
LEFT JOIN customers c ON s.customer_id = c.id
LEFT JOIN plans p ON s.plan_id = p.id
WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (
        c.name LIKE '%$search%' OR 
        c.customer_code LIKE '%$search%' OR
        p.name LIKE '%$search%'
    )";
}

$query .= " ORDER BY s.start_date DESC";
$result = $conn->query($query);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Subscriptions</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                onclick="window.location.href='subscription_form.php'">
            <i class="bx bx-plus"></i>
            <span>Add Subscription</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search subscriptions..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($sub = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($sub['customer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($sub['customer_code']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($sub['plan_name']); ?></div>
                                        <small class="text-muted">₱<?php echo number_format($sub['plan_amount'], 2); ?>/month</small>
                                    </td>
                                    <td>
                                        <div><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></div>
                                        <small class="text-muted">to <?php echo date('M d, Y', strtotime($sub['end_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($sub['status']) {
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'suspended' => 'danger',
                                                'pending' => 'warning',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($sub['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="subscription_view.php?id=<?php echo $sub['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="subscription_form.php?id=<?php echo $sub['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <?php if ($sub['status'] !== 'active'): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteSubscription(<?php echo $sub['id']; ?>)">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 mb-2"></i>
                                    <p class="mb-0">No subscriptions found</p>
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
function deleteSubscription(id) {
    if (confirm('Are you sure you want to delete this subscription?')) {
        window.location.href = `subscription_delete.php?id=${id}`;
    }
}
</script>

<?php include 'footer.php'; ?>
