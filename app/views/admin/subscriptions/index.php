<?php
$title = 'Subscription Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Subscription Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/subscriptions/create" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add Subscription
        </a>
    </div>
</div>

<!-- Search Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/subscriptions" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search subscriptions..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                        Active
                    </option>
                    <option value="suspended" <?= ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>
                        Suspended
                    </option>
                    <option value="terminated" <?= ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>
                        Terminated
                    </option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="plan_id">
                    <option value="">All Plans</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?= $plan['id'] ?>" 
                                <?= ($filters['plan_id'] ?? '') == $plan['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($plan['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
            
            <div class="col-md-2">
                <button type="button" class="btn btn-success w-100" id="exportSubscriptions">
                    <i class="fa fa-download"></i> Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Subscriptions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Period</th>
                        <th>Equipment</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($subscriptions['subscriptions'])): ?>
                        <?php foreach ($subscriptions['subscriptions'] as $sub): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($sub['customer_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($sub['customer_code']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($sub['plan_name']) ?></div>
                                    <small class="text-muted">
                                        <?= formatBandwidth($sub['bandwidth']) ?> - 
                                        <?= formatCurrency($sub['plan_amount']) ?>/mo
                                    </small>
                                </td>
                                <td>
                                    <div><?= date('M d, Y', strtotime($sub['start_date'])) ?></div>
                                    <small class="text-muted">
                                        to <?= date('M d, Y', strtotime($sub['end_date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div>Router: <?= htmlspecialchars($sub['router_model']) ?></div>
                                    <?php if ($sub['ont_model']): ?>
                                        <small class="text-muted">
                                            ONT: <?= htmlspecialchars($sub['ont_model']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($sub['status']) {
                                            'active' => 'success',
                                            'suspended' => 'warning',
                                            'terminated' => 'danger',
                                            'pending' => 'info',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($sub['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="/admin/subscriptions/<?= $sub['id'] ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="/admin/subscriptions/<?= $sub['id'] ?>/edit" 
                                           class="btn btn-sm btn-primary" title="Edit Subscription">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <?php if ($sub['status'] === 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="suspendSubscription(<?= $sub['id'] ?>)" 
                                                    title="Suspend">
                                                <i class="fa fa-pause"></i>
                                            </button>
                                        <?php elseif ($sub['status'] === 'suspended'): ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="activateSubscription(<?= $sub['id'] ?>)" 
                                                    title="Activate">
                                                <i class="fa fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($sub['status'] !== 'terminated'): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="terminateSubscription(<?= $sub['id'] ?>)" 
                                                    title="Terminate">
                                                <i class="fa fa-stop"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No subscriptions found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($subscriptions['pages'] > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $subscriptions['pages']; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<script>
// Export subscriptions
document.getElementById('exportSubscriptions').addEventListener('click', function() {
    const form = document.querySelector('form');
    const params = new URLSearchParams(new FormData(form));
    window.location.href = '/admin/subscriptions/export?' + params.toString();
});

// Subscription status actions
function suspendSubscription(id) {
    updateSubscriptionStatus(id, 'suspended', 'suspend');
}

function activateSubscription(id) {
    updateSubscriptionStatus(id, 'active', 'activate');
}

function terminateSubscription(id) {
    updateSubscriptionStatus(id, 'terminated', 'terminate');
}

function updateSubscriptionStatus(id, status, action) {
    if (confirm(`Are you sure you want to ${action} this subscription?`)) {
        fetch(`/admin/subscriptions/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || `Failed to ${action} subscription`);
            }
        });
    }
}

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});
</script>
