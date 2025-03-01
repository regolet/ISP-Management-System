<?php
require_once __DIR__ . '/../config.php';
check_auth('customer');

// Get subscription data
$subscription_query = "SELECT s.*, p.* 
                      FROM subscriptions s 
                      JOIN plans p ON s.plan_id = p.id 
                      WHERE s.customer_id = ? 
                      ORDER BY s.created_at DESC LIMIT 1";
$stmt = $conn->prepare($subscription_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

// Get subscription history
$history_query = "SELECT s.*, p.name as plan_name, p.amount 
                 FROM subscriptions s 
                 JOIN plans p ON s.plan_id = p.id 
                 WHERE s.customer_id = ? 
                 ORDER BY s.created_at DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$history = $stmt->get_result();

// Set page title
$page_title = 'Subscription Details';
include __DIR__ . '/../header.php';
include __DIR__ . '/navbar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Subscription Details</h1>

        <!-- Current Plan Details -->
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Plan</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($subscription): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <h4 class="small font-weight-bold">Plan Name</h4>
                                        <p class="mb-4"><?php echo htmlspecialchars($subscription['name'] ?? ''); ?></p>

                                        <h4 class="small font-weight-bold">Monthly Fee</h4>
                                        <p class="mb-4">₱<?php echo number_format($subscription['amount'] ?? 0, 2); ?></p>

                                        <h4 class="small font-weight-bold">Start Date</h4>
                                        <p class="mb-4"><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></p>

                                        <h4 class="small font-weight-bold">End Date</h4>
                                        <p class="mb-4"><?php echo $subscription['end_date'] ? date('M d, Y', strtotime($subscription['end_date'])) : 'Ongoing'; ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <h4 class="small font-weight-bold">Download Speed</h4>
                                        <p class="mb-4"><?php echo htmlspecialchars($subscription['download_speed'] ?? 'Not specified'); ?> Mbps</p>

                                        <h4 class="small font-weight-bold">Upload Speed</h4>
                                        <p class="mb-4"><?php echo htmlspecialchars($subscription['upload_speed'] ?? 'Not specified'); ?> Mbps</p>

                                        <h4 class="small font-weight-bold">Data Cap</h4>
                                        <p class="mb-4"><?php echo $subscription['data_cap'] ? htmlspecialchars($subscription['data_cap']) : 'Unlimited'; ?></p>

                                        <h4 class="small font-weight-bold">Status</h4>
                                        <p class="mb-4"><?php echo ucfirst($subscription['status'] ?? 'Unknown'); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No active subscription found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Plan Features or Additional Info -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Plan Features</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($subscription): ?>
                            <ul class="list-unstyled">
                                <?php if (!empty($subscription['description'])): ?>
                                    <?php foreach (explode("\n", $subscription['description']) as $feature): ?>
                                        <li class="mb-2">
                                            <i class='bx bx-check text-success me-2'></i>
                                            <?php echo htmlspecialchars($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-muted">No specific features listed</li>
                                <?php endif; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-center">No plan features to display</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription History -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Subscription History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                    <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo $row['end_date'] ? date('M d, Y', strtotime($row['end_date'])) : 'Ongoing'; ?></td>
                                    <td><?php echo ucfirst($row['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>