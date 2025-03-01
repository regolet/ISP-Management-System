<?php
require_once '../config.php';
check_auth();

$page_title = 'User Management';
$_SESSION['active_menu'] = 'users';

// Build the base query
$query = "SELECT u.*, 
         (SELECT COUNT(*) FROM activity_logs WHERE user_id = u.id) as activity_count
         FROM users u WHERE 1=1";

// Add search filter if provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $query .= " AND (username LIKE '%$search%' OR email LIKE '%$search%')";
}

// Add role filter if provided
if (isset($_GET['role']) && !empty($_GET['role'])) {
    $role = $conn->real_escape_string($_GET['role']);
    $query .= " AND role = '$role'";
}

// Add status filter if provided
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $query .= " AND status = '$status'";
}

// Get user statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
    FROM users
")->fetch_assoc();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total_records = $conn->query($query)->num_rows;
$total_pages = ceil($total_records / $per_page);

$query .= " ORDER BY username LIMIT $offset, $per_page";
$users = $conn->query($query);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">User Management</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="bx bx-plus"></i>
            <span>Add User</span>
        </button>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search username or email"
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo isset($_GET['role']) && $_GET['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="staff" <?php echo isset($_GET['role']) && $_GET['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo isset($_GET['status']) && $_GET['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo isset($_GET['status']) && $_GET['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bx bx-filter-alt"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['role'] == 'admin' ? 'danger' : 
                                        ($user['role'] == 'staff' ? 'success' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewUser(<?php echo $user['id']; ?>)">
                                        <i class="bx bx-show"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-<?php echo $user['status'] == 'active' ? 'danger' : 'success'; ?>" 
                                                onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleUserStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    if (confirm(`Are you sure you want to ${action} this user?`)) {
        window.location.href = `user_status.php?id=${id}&status=${newStatus}`;
    }
}

function editUser(id) {
    window.location.href = `user_form.php?id=${id}`;
}

function viewUser(id) {
    window.location.href = `user_view.php?id=${id}`;
}
</script>

<?php include 'footer.php'; ?>
