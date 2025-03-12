
<?php
/**
 * Render dashboard content
 * 
 * @param array $data Dashboard data
 * @return void
 */
function renderDashboardContent($data)
{
    ?>
    <div class="container-fluid py-4">
        <h1 class="page-title mb-4">Dashboard</h1>
        
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Clients</h5>
                                <h2 class="display-4"><?php echo htmlspecialchars($data['stats']['total_clients']); ?></h2>
                            </div>
                            <div class="icon-container bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Invoices</h5>
                                <h2 class="display-4"><?php echo htmlspecialchars($data['stats']['total_invoices']); ?></h2>
                            </div>
                            <div class="icon-container bg-success">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Pending Invoices</h5>
                                <h2 class="display-4"><?php echo htmlspecialchars($data['stats']['pending_invoices']); ?></h2>
                            </div>
                            <div class="icon-container bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Revenue (Month)</h5>
                                <h2 class="display-4">$<?php echo number_format($data['stats']['revenue_this_month'], 2); ?></h2>
                            </div>
                            <div class="icon-container bg-info">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity and Clients Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Activity Type</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data['recent_activities'])): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No recent activities found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data['recent_activities'] as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['type'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description'] ?? ''); ?></td>
                                                <td><?php echo isset($activity['created_at']) ? date('M d, Y H:i', strtotime($activity['created_at'])) : ''; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Clients</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php if (empty($data['recent_clients'])): ?>
                                <li class="list-group-item text-center">No clients found.</li>
                            <?php else: ?>
                                <?php foreach ($data['recent_clients'] as $client): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($client['name'] ?? ''); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($client['email'] ?? ''); ?></small>
                                            </div>
                                            <a href="/clients.php?id=<?php echo htmlspecialchars($client['id'] ?? ''); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Invoices Row -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Invoices</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data['recent_invoices'])): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No invoices found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data['recent_invoices'] as $invoice): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($invoice['invoice_number'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($invoice['client_name'] ?? ''); ?></td>
                                                <td>$<?php echo number_format($invoice['amount'] ?? 0, 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $invoice['status'] ?? '';
                                                    $statusClass = '';
                                                    
                                                    switch ($status) {
                                                        case 'paid':
                                                            $statusClass = 'bg-success';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'bg-warning';
                                                            break;
                                                        case 'overdue':
                                                            $statusClass = 'bg-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
                                                </td>
                                                <td><?php echo isset($invoice['due_date']) ? date('M d, Y', strtotime($invoice['due_date'])) : ''; ?></td>
                                                <td>
                                                    <a href="/invoices.php?id=<?php echo htmlspecialchars($invoice['id'] ?? ''); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
<?php
/**
 * Render dashboard content
 * 
 * @param array $data Dashboard data
 * @return void
 */
function renderDashboardContent($data)
{
    ?>
    <div class="container-fluid p-4">
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white shadow">
                    <div class="card-body p-4">
                        <h2 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h2>
                        <p class="card-text">
                            This is your ISP Management System dashboard. Get a quick overview of your business stats and recent activities.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Clients</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['statistics']['clients'] ?? 0; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Active Subscriptions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['statistics']['active_subscriptions'] ?? 0; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-wifi fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Revenue</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($data['statistics']['total_revenue'] ?? 0, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending Invoices</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['statistics']['pending_invoices'] ?? 0; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['recent_activities'])): ?>
                            <p class="text-center text-muted">No recent activities found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['recent_activities'] as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['action'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($activity['created_at'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Payments -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Upcoming Payments</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['upcoming_payments'])): ?>
                            <p class="text-center text-muted">No upcoming payments found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['upcoming_payments'] as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['client_name'] ?? ''); ?></td>
                                                <td>$<?php echo number_format($payment['amount'] ?? 0, 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['due_date'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
