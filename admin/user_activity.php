<?php
require_once 'config.php';
check_login();

$page_title = 'Activity Logs';
$_SESSION['active_menu'] = 'activity_logs';

// Get activity statistics
$stats_query = "SELECT 
    COUNT(*) as total_activities,
    COUNT(DISTINCT user_id) as total_users,
    COUNT(DISTINCT DATE(created_at)) as active_days,
    MAX(created_at) as last_activity
    FROM activity_logs";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get all activities with user details
$query = "SELECT a.*, u.username, u.role
          FROM activity_logs a 
          LEFT JOIN users u ON a.user_id = u.id 
          ORDER BY a.created_at DESC 
          LIMIT 1000";
$activities = $conn->query($query);

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Activity Logs</h1>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Activities</h6>
                                <h2 class="mb-0"><?php echo number_format($stats['total_activities']); ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class='bx bx-line-chart'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Active Users</h6>
                                <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class='bx bx-user-check'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Active Days</h6>
                                <h2 class="mb-0"><?php echo number_format($stats['active_days']); ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class='bx bx-calendar-check'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Last Activity</h6>
                                <h5 class="mb-0"><?php 
                                    echo $stats['last_activity'] 
                                        ? date('M d, Y g:i A', strtotime($stats['last_activity'])) 
                                        : 'No activity recorded'; 
                                ?></h5>
                            </div>
                            <div class="fs-1">
                                <i class='bx bx-time-five'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search activities..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="user">
                            <option value="">All Users</option>
                            <?php 
                            $users = $conn->query("SELECT DISTINCT username FROM users ORDER BY username");
                            while ($user = $users->fetch_assoc()):
                            ?>
                            <option value="<?php echo $user['username']; ?>" 
                                    <?php echo isset($_GET['user']) && $_GET['user'] === $user['username'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="activity_type">
                            <option value="">All Activities</option>
                            <?php 
                            $types = $conn->query("SELECT DISTINCT type FROM activity_logs WHERE type IS NOT NULL ORDER BY type");
                            while ($type = $types->fetch_assoc()):
                            ?>
                            <option value="<?php echo $type['type']; ?>"
                                    <?php echo isset($_GET['activity_type']) && $_GET['activity_type'] === $type['type'] ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type['type'])); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date" 
                               value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-search'></i> Search
                        </button>
                        <?php if (!empty($_GET)): ?>
                        <a href="user_activity.php" class="btn btn-secondary">
                            <i class='bx bx-reset'></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Activities</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class='bx bx-export'></i> Export
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="activitiesTable">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Activity Type</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Modify query based on filters
                            $where = [];
                            $params = [];
                            $param_types = "";

                            if (!empty($_GET['search'])) {
                                $search = "%{$_GET['search']}%";
                                $where[] = "(a.description LIKE ? OR u.username LIKE ? OR a.type LIKE ?)";
                                $params = array_merge($params, [$search, $search, $search]);
                                $param_types .= "sss";
                            }

                            if (!empty($_GET['user'])) {
                                $where[] = "u.username = ?";
                                $params[] = $_GET['user'];
                                $param_types .= "s";
                            }

                            if (!empty($_GET['activity_type'])) {
                                $where[] = "a.type = ?";
                                $params[] = $_GET['activity_type'];
                                $param_types .= "s";
                            }

                            if (!empty($_GET['date'])) {
                                $where[] = "DATE(a.created_at) = ?";
                                $params[] = $_GET['date'];
                                $param_types .= "s";
                            }

                            $query = "SELECT a.*, u.username, u.role
                                    FROM activity_logs a 
                                    LEFT JOIN users u ON a.user_id = u.id";

                            if (!empty($where)) {
                                $query .= " WHERE " . implode(" AND ", $where);
                            }
                            
                            $query .= " ORDER BY a.created_at DESC LIMIT 1000";

                            $stmt = $conn->prepare($query);
                            if (!empty($params)) {
                                $stmt->bind_param($param_types, ...$params);
                            }
                            $stmt->execute();
                            $activities = $stmt->get_result();

                            while ($activity = $activities->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $activity['type'] ?? 'Unknown')); ?></td>
                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    // Get the table HTML
    let table = document.getElementById("activitiesTable");
    
    // Convert to worksheet
    let ws = XLSX.utils.table_to_sheet(table);
    
    // Create workbook and add the worksheet
    let wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Activities");
    
    // Save the file
    XLSX.writeFile(wb, "activity_logs_" + new Date().toISOString().slice(0,10) + ".xlsx");
}

// Add datatable functionality
$(document).ready(function() {
    // Check if DataTables is available
    if (typeof $.fn.DataTable !== 'undefined') {
        try {
            var table = $('#activitiesTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']], // Sort by date desc
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                columnDefs: [
                    { targets: 0, type: 'date' } // Time column as date
                ],
                initComplete: function() {
                    console.log('DataTable initialization complete');
                }
            });
        } catch (error) {
            console.error('Error initializing DataTable:', error);
        }
    } else {
        console.error('DataTables plugin not loaded');
    }
});
</script>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
.table td {
    vertical-align: middle;
}
.form-control:focus, .form-select:focus {
    box-shadow: none;
    border-color: #80bdff;
}
.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}
.card .card-body i {
    opacity: 0.4;
}
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}
</style>

<?php include 'footer.php'; ?>
