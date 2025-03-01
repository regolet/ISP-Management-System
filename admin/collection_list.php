<?php
require_once 'config.php';
check_login();

$page_title = 'Collection History';
$_SESSION['active_menu'] = 'asset_collections';

include 'header.php';
include 'navbar.php';

// Get collection statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_collections,
        SUM(amount) as total_amount,
        COUNT(DISTINCT asset_id) as unique_assets
    FROM asset_collections
")->fetch_assoc();

// Get collections with asset details
$query = "
    SELECT ac.*, 
           a.name as asset_name,
           a.address as asset_address,
           u.username as collected_by
    FROM asset_collections ac
    LEFT JOIN assets a ON ac.asset_id = a.id
    LEFT JOIN users u ON ac.created_by = u.id
    ORDER BY ac.collection_date DESC, ac.created_at DESC
";

$collections = $conn->query($query);
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Collection History</h1>
            <a href="assets.php" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Back to Assets
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-money fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-primary fw-bold">Total Collections</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-receipt fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-success fw-bold">Total Transactions</h6>
                                <h3 class="card-title mb-1"><?php echo number_format($stats['total_collections'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 h-100" style="background: rgba(13, 202, 240, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-info text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-buildings fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-info fw-bold">Unique Assets</h6>
                                <h3 class="card-title mb-1"><?php echo number_format($stats['unique_assets'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collections Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Collection Date</th>
                                <th>Asset</th>
                                <th class="text-end">Amount</th>
                                <th>Payment Method</th>
                                <th>Reference #</th>
                                <th>Collected By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($collections->num_rows > 0):
                                while ($collection = $collections->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($collection['collection_date'])); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($collection['asset_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($collection['asset_address']); ?></small>
                                    </td>
                                    <td class="text-end">₱<?php echo number_format($collection['amount'], 2); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $collection['payment_method'])); ?></td>
                                    <td><?php echo htmlspecialchars($collection['reference_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($collection['collected_by']); ?></div>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($collection['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="asset_collections.php?id=<?php echo $collection['asset_id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Asset Collections">
                                                <i class='bx bx-show'></i>
                                            </a>
                                            <a href="asset_collections.php?id=<?php echo $collection['asset_id']; ?>&collection_id=<?php echo $collection['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit Collection">
                                                <i class='bx bx-edit'></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No collections found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
