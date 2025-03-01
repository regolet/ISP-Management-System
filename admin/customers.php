<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'customers';
$page_title = 'Customers';

// Get database connection
$conn = get_db_connection();

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query with search
$query = "SELECT 
    c.*, 
    p.name as plan_name, 
    p.amount as plan_amount,
    u.username
FROM customers c 
LEFT JOIN plans p ON c.plan_id = p.id
LEFT JOIN users u ON c.user_id = u.id
WHERE 1=1";

$params = [];
if (!empty($search)) {
    $query .= " AND (
        c.customer_code LIKE ? OR 
        c.name LIKE ? OR 
        c.address LIKE ? OR
        p.name LIKE ?
    )";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

$query .= " ORDER BY c.name ASC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Customers</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                onclick="window.location.href='customer_form.php'">
            <i class="bx bx-plus"></i>
            <span>Add Customer</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search customers..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Customer Code</th>
                            <th>Name</th>
                            <th>Plan</th>
                            <th>Due Date</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($customers) > 0): ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($customer['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($customer['address']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($customer['plan_name']): ?>
                                            <div><?php echo htmlspecialchars($customer['plan_name']); ?></div>
                                            <small class="text-muted">₱<?php echo number_format($customer['plan_amount'], 2); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $customer['due_date'] ? date('M d, Y', strtotime($customer['due_date'])) : '-'; ?></td>
                                    <td>₱<?php echo number_format($customer['outstanding_balance'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($customer['status']) {
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'suspended' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($customer['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="customer_form.php?id=<?php echo $customer['id']; ?>&view=1" 
                                               class="btn btn-sm btn-info" title="View Customer">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="customer_form.php?id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit Customer">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <?php if ($customer['status'] !== 'active'): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteCustomer(<?php echo $customer['id']; ?>)"
                                                        title="Delete Customer">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 mb-2"></i>
                                    <p class="mb-0">No customers found</p>
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
function deleteCustomer(id) {
    if (confirm('Are you sure you want to delete this customer?')) {
        window.location.href = `customer_delete.php?id=${id}`;
    }
}
</script>

<?php include 'footer.php'; ?>
