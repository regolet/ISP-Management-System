<?php
require_once '../config.php';
check_auth();

$page_title = 'Audit Logs';
$_SESSION['active_menu'] = 'audit_logs';

// Build base query
$where_conditions = ["1=1"];
$params = [];
$param_types = "";

// Apply filters
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $_GET['user_id'];
    $param_types .= "i";
}

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_conditions[] = "al.type = ?";
    $params[] = $_GET['type'];
    $param_types .= "s";
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $param_types .= "s";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $param_types .= "s";
}

// Get filter options
$types_query = "SELECT DISTINCT type FROM activity_logs ORDER BY type";
$activity_types = $conn->query($types_query);

$users_query = "SELECT id, username FROM users ORDER BY username";
$users = $conn->query($users_query);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total records
$count_query = "SELECT COUNT(*) as total FROM activity_logs al WHERE " . implode(" AND ", $where_conditions);
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get logs
$query = "
    SELECT al.*, u.username 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE " . implode(" AND ", $where_conditions) . "
    ORDER BY al.created_at DESC 
    LIMIT ?, ?
";

$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Audit Logs</h1>
        <?php if (!empty($_GET)): ?>
            <a href="audit_logs.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bx bx-reset"></i>
                <span>Clear Filters</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select class="form-select" name="user_id">
                        <option value="">All Users</option>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo isset($_GET['user_id']) && $_GET['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Activity Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Activities</option>
                        <?php while ($type = $activity_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['type']; ?>"
                                    <?php echo isset($_GET['type']) && $_GET['type'] == $type['type'] ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type['type'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bx bx-filter-alt"></i>
                        <span>Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Timestamp</th>
                            <th style="width: 150px;">User</th>
                            <th style="width: 150px;">Activity Type</th>
                            <th>Description</th>
                            <th style="width: 150px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs->num_rows > 0): ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <a href="?user_id=<?php echo $log['user_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($log['username']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?type=<?php echo urlencode($log['type']); ?>" class="text-decoration-none">
                                            <span class="badge bg-<?php 
                                                echo $log['type'] == 'login' ? 'success' : 
                                                    ($log['type'] == 'logout' ? 'secondary' : 
                                                    ($log['type'] == 'create' ? 'primary' : 
                                                    ($log['type'] == 'update' ? 'info' : 
                                                    ($log['type'] == 'delete' ? 'danger' : 'warning')))); 
                                            ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $log['type'])); ?>
                                            </span>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bx bx-info-circle fs-4 text-muted"></i>
                                    <p class="text-muted mb-0">No activity logs found</p>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
code {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.875em;
    color: #6c757d;
}
.badge {
    font-weight: 500;
}
</style>

<?php include 'footer.php'; ?>
