<?php
require_once __DIR__ . '/../config.php';
check_auth('customer');

// Utility functions for customer dashboard
function get_customer_balance($customer_id) {
    global $conn;
    
    $query = "SELECT 
        COALESCE(SUM(b.amount), 0) as total_billed,
        COALESCE(SUM(p.amount), 0) as total_paid
        FROM billing b
        LEFT JOIN payments p ON b.id = p.billing_id AND p.status = 'completed'
        WHERE b.customer_id = ?";
        
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['total_billed'] - $result['total_paid'];
}

function get_customer_status($customer_id) {
    global $conn;
    
    $query = "SELECT 
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM billing 
                WHERE customer_id = ? 
                AND status = 'overdue'
            ) THEN 'overdue'
            WHEN EXISTS (
                SELECT 1 FROM billing 
                WHERE customer_id = ? 
                AND status = 'unpaid'
            ) THEN 'unpaid'
            ELSE 'paid'
        END as status";
        
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $customer_id, $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['status'];
}

// Get customer data
$customer_query = "SELECT * FROM customers WHERE user_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Update customer balance and status
$customer['balance'] = get_customer_balance($customer['id']);
$customer['status'] = get_customer_status($customer['id']);

// Get billing data
$billing_query = "SELECT * FROM billing WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($billing_query);
$stmt->bind_param("i", $customer['id']);
$stmt->execute();
$billing = $stmt->get_result()->fetch_assoc();

// Get subscription data
$subscription_query = "SELECT s.*, p.name as plan_name, p.amount as plan_amount 
                      FROM subscriptions s 
                      JOIN plans p ON s.plan_id = p.id 
                      WHERE s.customer_id = ? 
                      ORDER BY s.created_at DESC LIMIT 1";
$stmt = $conn->prepare($subscription_query);
$stmt->bind_param("i", $customer['id']);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

// Set page title
$page_title = 'Dashboard';
include __DIR__ . '/../header.php';
include __DIR__ . '/navbar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

        <div class="row">
            <!-- Subscription Info Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Current Plan</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo htmlspecialchars($subscription['plan_name'] ?? 'No Plan'); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class='bx bx-broadcast bx-sm text-gray-300'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Balance</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ₱<?php echo number_format($customer['balance'] ?? 0, 2); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class='bx bx-money bx-sm text-gray-300'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Due Date Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Due Date
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $customer['due_date'] ? date('M d, Y', strtotime($customer['due_date'])) : 'Not Set'; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class='bx bx-calendar bx-sm text-gray-300'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Status</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo strtoupper($customer['status'] ?? 'Unknown'); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class='bx bx-check-circle bx-sm text-gray-300'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Subscription Details -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Subscription Details</h6>
                        <a href="<?php echo BASE_URL; ?>customer/subscription.php" class="btn btn-sm btn-primary shadow-sm">
                            <i class='bx bx-show'></i> View Details
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($subscription): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <th>Plan:</th>
                                        <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>₱<?php echo number_format($subscription['plan_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Start Date:</th>
                                        <td><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>End Date:</th>
                                        <td><?php echo $subscription['end_date'] ? date('M d, Y', strtotime($subscription['end_date'])) : 'Ongoing'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><?php echo ucfirst($subscription['status']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No subscription found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Billing Details -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Latest Bill</h6>
                        <a href="<?php echo BASE_URL; ?>customer/billing.php" class="btn btn-sm btn-primary shadow-sm">
                            <i class='bx bx-show'></i> View All Bills
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($billing): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <th>Invoice ID:</th>
                                        <td><?php echo htmlspecialchars($billing['invoiceid']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>₱<?php echo number_format($billing['amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Due Date:</th>
                                        <td><?php echo date('M d, Y', strtotime($billing['due_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><?php echo ucfirst($billing['status']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Balance:</th>
                                        <td>₱<?php echo number_format($billing['balance'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No billing found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>