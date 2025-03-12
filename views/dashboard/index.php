<?php
function renderDashboardContent($data) {
    $summary = $data['summary'];
    $billingOverview = $data['billingOverview'];
    $ticketOverview = $data['ticketOverview'];
    $networkStatus = $data['networkStatus'];
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();">
                    <i class="fas fa-print me-1"></i> Print Report
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard();">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <!-- Customers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card customers-card shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="card-title mb-1">Active Customers</div>
                            <div class="card-number"><?php echo $summary['customerCount']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users dashboard-card-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card bills-card shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="card-title mb-1">Pending Bills</div>
                            <div class="card-number"><?php echo $summary['pendingBillsCount']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar dashboard-card-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card tickets-card shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="card-title mb-1">Active Tickets</div>
                            <div class="card-number"><?php echo $summary['activeTicketsCount']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-headset dashboard-card-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Overview Section -->
    <div class="row mt-4">
        <!-- Billing Overview -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Billing Overview</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($billingOverview as $bill): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo getBillingStatusColor($bill['status']); ?>">
                                                <?php echo ucfirst($bill['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $bill['count']; ?></td>
                                        <td>$<?php echo number_format($bill['total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Status -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Network Status</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment Type</th>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($networkStatus as $status): ?>
                                    <tr>
                                        <td><?php echo ucfirst($status['type']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getNetworkStatusColor($status['status']); ?>">
                                                <?php echo ucfirst($status['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $status['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <!-- Admin Tools Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Admin Tools</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Database Management</h5>
                                    <p class="card-text">Check database structure, run migrations, and fix database issues.</p>
                                    <a href="/admin/check_database.php" class="btn btn-primary">
                                        <i class="fas fa-database me-2"></i>Database Tools
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">User Management</h5>
                                    <p class="card-text">Manage system users, roles, and permissions.</p>
                                    <a href="/users.php" class="btn btn-primary">
                                        <i class="fas fa-users-cog me-2"></i>User Management
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">System Settings</h5>
                                    <p class="card-text">Configure system settings, backup, and maintenance options.</p>
                                    <a href="/settings.php" class="btn btn-primary">
                                        <i class="fas fa-cog me-2"></i>System Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity Section -->
    <?php renderDashboardRecentActivity($data['recentActivities'] ?? []); ?>

<?php
}

function getBillingStatusColor($status) {
    switch ($status) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warning';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getNetworkStatusColor($status) {
    switch ($status) {
        case 'active':
            return 'success';
        case 'maintenance':
            return 'warning';
        case 'inactive':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<!-- Dashboard specific styles -->
<style>
.card {
    transition: transform .2s;
    border-left: 4px solid;
}
.card:hover {
    transform: translateY(-5px);
}
.card.customers-card { border-left-color: #4e73df; }
.card.bills-card { border-left-color: #f6c23e; }
.card.tickets-card { border-left-color: #36b9cc; }
.dashboard-card-icon {
    font-size: 2rem;
    opacity: 0.4;
}
.card-title {
    text-transform: uppercase;
    font-size: 0.7rem;
    font-weight: bold;
    color: #666;
}
.card-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}
.badge {
    font-size: 0.8rem;
    padding: 0.4em 0.8em;
}
</style>

<script>
function refreshDashboard() {
    location.reload();
}
</script>
