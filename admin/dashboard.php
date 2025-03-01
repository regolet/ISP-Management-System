<?php
require_once '../init.php';

// Use the new auth check function
check_auth('admin');

$page_title = 'Dashboard';
$_SESSION['active_menu'] = 'dashboard';
include 'header.php';
include 'navbar.php';

try {
    $conn = get_db_connection();

    // Database queries for dashboard stats
    // Subscription Analytics
    $subscription_query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN billing_cycle = 'monthly' THEN 1 END) as monthly,
        COUNT(CASE WHEN billing_cycle = 'quarterly' THEN 1 END) as quarterly,
        COUNT(CASE WHEN billing_cycle = 'annually' THEN 1 END) as annually,
        COUNT(CASE WHEN auto_renew = 1 THEN 1 END) as auto_renew
        FROM subscriptions";
    $subscription_result = $conn->query($subscription_query)->fetch();

    // Billing Status Overview
    $billing_query = "SELECT 
        COUNT(*) as total_bills,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_bills,
        COUNT(CASE WHEN status = 'unpaid' THEN 1 END) as unpaid_bills,
        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_bills,
        COALESCE(SUM(late_fee), 0) as total_late_fees
        FROM billing";
    $billing_result = $conn->query($billing_query)->fetch();

    // Customer Growth
    $customer_growth_query = "SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 END) as new_this_month,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
        COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_customers
        FROM customers";
    $customer_growth_result = $conn->query($customer_growth_query)->fetch();

    $customers_query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as paid,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as unpaid,
        COUNT(CASE WHEN status = 'suspended' THEN 1 END) as overdue
        FROM customers";
    $customers_result = $conn->query($customers_query)->fetch();

    // Get plans statistics
    $plans_query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
        FROM plans";
    $plans_result = $conn->query($plans_query)->fetch();

    // Get revenue statistics
    $revenue_query = "SELECT 
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as completed_count
        FROM payments";
    $revenue_result = $conn->query($revenue_query)->fetch();

    // Get recent payments
    $recent_payments_query = "SELECT p.*, b.invoiceid, c.name as customer_name 
                             FROM payments p
                             LEFT JOIN billing b ON p.billing_id = b.id
                             LEFT JOIN customers c ON b.customer_id = c.id
                             ORDER BY p.payment_date DESC LIMIT 5";
    $recent_payments = $conn->query($recent_payments_query);

    // Get overdue bills
    $overdue_bills_query = "SELECT b.*, c.name as customer_name 
                            FROM billing b
                            LEFT JOIN customers c ON b.customer_id = c.id
                            WHERE b.due_date < CURDATE()
                            AND b.status = 'unpaid'
                            ORDER BY b.due_date ASC LIMIT 5";
    $overdue_bills = $conn->query($overdue_bills_query);

    // Get active subscriptions
    $subscriptions_query = "SELECT s.*, p.name as plan_name, c.name as customer_name
                           FROM subscriptions s
                           LEFT JOIN plans p ON s.plan_id = p.id
                           LEFT JOIN customers c ON s.customer_id = c.id
                           WHERE s.status = 'active'
                           ORDER BY s.end_date ASC LIMIT 5";
    $active_subscriptions = $conn->query($subscriptions_query);
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading dashboard data. Please try again later.";
}
?>

<div class="container-fluid">
    <?php include 'alerts.php'; ?>
    <h1 class="h2 mb-4">Dashboard Overview</h1>

    <!-- Stats Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <!-- PON Management Stats Card -->
        <div class="col">
            <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 64px; height: 64px;">
                            <i class="bx bx-network-chart fs-1"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-primary fw-bold">PON Services</h6>
                            <h3 class="card-title mb-1">0</h3>
                            <div class="small">
                                <span class="text-success">Active: 0</span> • 
                                <span class="text-danger">Inactive: 0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Customers Card -->
        <div class="col">
            <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 64px; height: 64px;">
                            <i class="bx bx-group fs-1"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-primary fw-bold">Customers</h6>
                            <h3 class="card-title mb-1"><?php echo $customers_result['total']; ?></h3>
                            <div class="small">
                                <span class="text-success"><?php echo $customers_result['paid']; ?> paid</span> • 
                                <span class="text-warning"><?php echo $customers_result['unpaid']; ?> unpaid</span> • 
                                <span class="text-danger"><?php echo $customers_result['overdue']; ?> overdue</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plans Card -->
        <div class="col">
            <div class="card border-0 h-100" style="background: rgba(13, 202, 240, 0.1);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-info text-white rounded-3" style="width: 64px; height: 64px;">
                            <i class="bx bx-package fs-1"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-info fw-bold">Service Plans</h6>
                            <h3 class="card-title mb-1"><?php echo $plans_result['total']; ?></h3>
                            <div class="small">
                                <span class="text-success"><?php echo $plans_result['active']; ?> active</span> • 
                                <span class="text-secondary"><?php echo $plans_result['inactive']; ?> inactive</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="col">
            <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 64px; height: 64px;">
                            <i class="bx bx-money fs-1"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-success fw-bold">Total Revenue</h6>
                            <h3 class="card-title mb-1">₱<?php echo number_format($revenue_result['total_revenue'], 2); ?></h3>
                            <div class="small">
                                <span class="text-success"><?php echo $revenue_result['completed_count']; ?> completed</span> • 
                                <span class="text-warning"><?php echo $revenue_result['pending_count']; ?> pending</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments Card -->
        <div class="col">
            <div class="card border-0 h-100" style="background: rgba(255, 193, 7, 0.1);">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-warning text-white rounded-3" style="width: 64px; height: 64px;">
                            <i class="bx bx-time fs-1"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-warning fw-bold">Pending Payments</h6>
                            <h3 class="card-title mb-1">₱<?php echo number_format($revenue_result['pending_amount'], 2); ?></h3>
                            <div class="small">From <?php echo $revenue_result['pending_count']; ?> transactions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Cards -->
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
        <!-- Subscription Analytics -->
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">Subscription Analytics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <h6 class="text-muted mb-1">Total Subscriptions</h6>
                            <h3 class="mb-0"><?php echo $subscription_result['total']; ?></h3>
                        </div>
                        <div class="text-end">
                            <h6 class="text-muted mb-1">Auto-Renew</h6>
                            <h3 class="mb-0"><?php echo $subscription_result['auto_renew']; ?></h3>
                        </div>
                    </div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Monthly</span>
                            <span class="text-primary"><?php echo $subscription_result['monthly']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Quarterly</span>
                            <span class="text-info"><?php echo $subscription_result['quarterly']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Annually</span>
                            <span class="text-success"><?php echo $subscription_result['annually']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing Overview -->
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">Billing Overview</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <h6 class="text-muted mb-1">Total Bills</h6>
                            <h3 class="mb-0"><?php echo $billing_result['total_bills']; ?></h3>
                        </div>
                        <div class="text-end">
                            <h6 class="text-muted mb-1">Late Fees</h6>
                            <h3 class="mb-0">₱<?php echo number_format($billing_result['total_late_fees'], 2); ?></h3>
                        </div>
                    </div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Paid</span>
                            <span class="text-success"><?php echo $billing_result['paid_bills']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Unpaid</span>
                            <span class="text-warning"><?php echo $billing_result['unpaid_bills']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Overdue</span>
                            <span class="text-danger"><?php echo $billing_result['overdue_bills']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Growth -->
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">Customer Growth</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <h6 class="text-muted mb-1">Total Customers</h6>
                            <h3 class="mb-0"><?php echo $customer_growth_result['total_customers']; ?></h3>
                        </div>
                        <div class="text-end">
                            <h6 class="text-muted mb-1">New This Month</h6>
                            <h3 class="mb-0"><?php echo $customer_growth_result['new_this_month']; ?></h3>
                        </div>
                    </div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Active</span>
                            <span class="text-success"><?php echo $customer_growth_result['active_customers']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Suspended</span>
                            <span class="text-danger"><?php echo $customer_growth_result['suspended_customers']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row g-4">
        <!-- Recent Payments -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Recent Payments</h5>
                    <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = $recent_payments->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($payment['status']) {
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Bills -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Overdue Bills</h5>
                    <a href="billing.php?status=overdue" class="btn btn-sm btn-outline-danger">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($bill = $overdue_bills->fetch()): 
                                    $days_overdue = floor((strtotime('now') - strtotime($bill['due_date'])) / (60 * 60 * 24));
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                    <td>₱<?php echo number_format($bill['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                    <td><span class="text-danger fw-medium"><?php echo $days_overdue; ?> days</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Subscriptions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Active Subscriptions</h5>
                    <a href="subscriptions.php" class="btn btn-sm btn-outline-primary">Manage Subscriptions</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sub = $active_subscriptions->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['plan_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <a href="subscription_form.php?id=<?php echo $sub['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-show"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
