<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Asset Management</h1>
        </div>

        <!-- Floating Action Button -->
        <div class="floating-action-button-container">
            <a href="/admin/assets/create" class="btn btn-primary floating-action-button">
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
                               value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo isset($status) && $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo isset($status) && $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                        <?php if (!empty($assets)): ?>
                            <?php foreach ($assets as $asset): ?>
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
                                        <a href="/admin/assets/<?php echo (int)$asset['id']; ?>/collections" 
                                           class="btn btn-sm btn-success" title="View Collections">
                                            <i class='bx bx-money'></i>
                                        </a>
                                        <a href="/admin/assets/<?php echo (int)$asset['id']; ?>/edit" 
                                           class="btn btn-sm btn-primary" title="Edit Asset">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <a href="/admin/assets/<?php echo (int)$asset['id']; ?>/expenses" 
                                           class="btn btn-sm btn-info" title="Manage Expenses">
                                            <i class='bx bx-receipt'></i>
                                        </a>
                                        <?php if ($asset['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="updateStatus(<?php echo (int)$asset['id']; ?>, 'inactive')" title="Deactivate">
                                            <i class='bx bx-power-off'></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                onclick="updateStatus(<?php echo (int)$asset['id']; ?>, 'active')" title="Activate">
                                            <i class='bx bx-power-off'></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $current_page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    if (confirm(`Are you sure you want to ${status === 'active' ? 'activate' : 'deactivate'} this asset?`)) {
        window.location.href = `/admin/assets/${id}/status?status=${status}`;
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
