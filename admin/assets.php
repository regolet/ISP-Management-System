<?php
declare(strict_types=1);
require_once 'config.php';
check_login();

$page_title = 'Asset Management';
$_SESSION['active_menu'] = 'assets';

// Get current month range
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

try {
    // Get asset statistics with prepared statement
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(id) as total_assets,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assets,
            COUNT(CASE WHEN next_collection_date <= CURRENT_DATE() AND status = 'active' THEN 1 END) as due_collections
        FROM assets
    ");
    if (!$stats_stmt) {
        throw new Exception("Failed to prepare statistics query");
    }
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();

    // Get total collections this month with prepared statement
    $collections_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(ac.amount), 0) as total_collections,
            COUNT(DISTINCT ac.asset_id) as collected_assets
        FROM asset_collections ac
        INNER JOIN assets a ON ac.asset_id = a.id
        WHERE ac.collection_date BETWEEN ? AND ?
        AND a.status = 'active'
    ");
    if (!$collections_stmt) {
        throw new Exception("Failed to prepare collections query");
    }
    $collections_stmt->bind_param('ss', $start_date, $end_date);
    $collections_stmt->execute();
    $collections = $collections_stmt->get_result()->fetch_assoc();

    // Get uncollected amount with prepared statement
    $uncollected_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(a.expected_amount), 0) as total_uncollected,
            COUNT(a.id) as pending_assets
        FROM assets a
        WHERE a.status = 'active'
        AND a.next_collection_date BETWEEN ? AND ?
        AND NOT EXISTS (
            SELECT 1 FROM asset_collections ac 
            WHERE ac.asset_id = a.id 
            AND ac.collection_date BETWEEN ? AND ?
        )
    ");
    if (!$uncollected_stmt) {
        throw new Exception("Failed to prepare uncollected query");
    }
    $uncollected_stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
    $uncollected_stmt->execute();
    $uncollected = $uncollected_stmt->get_result()->fetch_assoc();

    // Build the base query for assets list with prepared statements
    $base_query = "SELECT a.*, 
        COALESCE((SELECT COUNT(*) FROM asset_collections WHERE asset_id = a.id), 0) as collection_count,
        COALESCE((SELECT SUM(amount) FROM asset_collections WHERE asset_id = a.id), 0) as total_collected
        FROM assets a WHERE 1=1";
    $where_conditions = [];
    $params = [];
    $types = "";

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $where_conditions[] = "(name LIKE ? OR address LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where_conditions[] = "status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    if (!empty($where_conditions)) {
        $base_query .= " AND " . implode(" AND ", $where_conditions);
    }

    // Get total count for pagination
    $count_stmt = $conn->prepare($base_query);
    if (!$count_stmt) {
        throw new Exception("Failed to prepare count query");
    }
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->num_rows;

    // Pagination settings
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 10;
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;

    // Add pagination to query
    $base_query .= " ORDER BY name LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $per_page;
    $types .= "ii";

    $stmt = $conn->prepare($base_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare assets query");
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $assets = $stmt->get_result();

} catch (Exception $e) {
    error_log("Assets Page Error: " . $e->getMessage());
    $error_message = "An error occurred while loading the page. Please try again later.";
}

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php include 'alerts.php'; ?>
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Asset Management</h1>
        </div>

        <!-- Floating Action Button -->
        <div class="floating-action-button-container">
            <a href="asset_form.php" class="btn btn-primary floating-action-button">
                <i class='bx bx-plus'></i>
                <span class="fab-label">Add Asset</span>
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <!-- Collections This Month -->
            <div class="col-md-4">
                <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-money fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-primary fw-bold">Collections This Month</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format((float)($collections['total_collections'] ?? 0), 2); ?></h3>
                                <small class="text-muted"><?php echo number_format((int)($collections['collected_assets'] ?? 0)); ?> assets collected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Uncollected This Month -->
            <div class="col-md-4">
                <div class="card border-0 h-100" style="background: rgba(220, 53, 69, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-danger text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-calendar-exclamation fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-danger fw-bold">Uncollected This Month</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format((float)($uncollected['total_uncollected'] ?? 0), 2); ?></h3>
                                <small class="text-muted"><?php echo number_format((int)($uncollected['pending_assets'] ?? 0)); ?> pending collections</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Active Assets -->
            <div class="col-md-4">
                <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-buildings fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-success fw-bold">Total Active Assets</h6>
                                <h3 class="card-title mb-1"><?php echo number_format((int)($stats['active_assets'] ?? 0)); ?></h3>
                                <small class="text-muted">out of <?php echo number_format((int)($stats['total_assets'] ?? 0)); ?> total assets</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by name or address"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo isset($_GET['status']) && $_GET['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo isset($_GET['status']) && $_GET['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class='bx bx-filter-alt'></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Asset Name</th>
                                <th style="min-width: 200px;">Address</th>
                                <th class="text-end" style="min-width: 150px;">Expected Amount</th>
                                <th class="text-center" style="min-width: 150px;">Next Collection</th>
                                <th class="text-end" style="min-width: 150px;">Total Collections</th>
                                <th class="text-center" style="min-width: 100px;">Status</th>
                                <th class="text-center" style="min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($assets && $assets->num_rows > 0): ?>
                            <?php while ($asset = $assets->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($asset['name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($asset['description'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($asset['address']); ?></td>
                                <td class="text-end">₱<?php echo number_format((float)$asset['expected_amount'], 2); ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-<?php echo strtotime($asset['next_collection_date']) < time() ? 'danger' : 'info'; ?>">
                                        <?php echo date('M d, Y', strtotime($asset['next_collection_date'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div>₱<?php echo number_format((float)$asset['total_collected'], 2); ?></div>
                                    <small class="text-muted"><?php echo number_format((int)$asset['collection_count']); ?> collections</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-<?php echo $asset['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($asset['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="asset_collections.php?id=<?php echo (int)$asset['id']; ?>" 
                                           class="btn btn-sm btn-success" title="View Collections">
                                            <i class='bx bx-money'></i>
                                        </a>
                                        <a href="asset_form.php?id=<?php echo (int)$asset['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit Asset">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <a href="asset_expenses.php?id=<?php echo (int)$asset['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Manage Expenses">
                                            <i class='bx bx-receipt'></i>
                                        </a>
                                        <?php if ($asset['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="deactivateAsset(<?php echo (int)$asset['id']; ?>)" title="Deactivate">
                                            <i class='bx bx-power-off'></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                onclick="activateAsset(<?php echo (int)$asset['id']; ?>)" title="Activate">
                                            <i class='bx bx-power-off'></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class='bx bx-info-circle fs-1'></i>
                                    <p class="mb-0">No assets found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&status=<?php echo urlencode($_GET['status'] ?? ''); ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&status=<?php echo urlencode($_GET['status'] ?? ''); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&status=<?php echo urlencode($_GET['status'] ?? ''); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function activateAsset(id) {
    if (confirm('Are you sure you want to activate this asset?')) {
        window.location.href = `asset_status.php?id=${id}&status=active`;
    }
}

function deactivateAsset(id) {
    if (confirm('Are you sure you want to deactivate this asset?')) {
        window.location.href = `asset_status.php?id=${id}&status=inactive`;
    }
}
</script>

<?php include 'footer.php'; ?>