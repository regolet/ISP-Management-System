<?php
$title = 'Manage Customers - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-user-circle'></i> Manage Customers</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/customers/create" class="btn btn-outline-primary">
                <i class='bx bx-user-plus'></i> Add Customer
            </a>
            <div class="dropdown">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class='bx bx-export'></i> Export As
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportData('csv')"><i class='bx bx-file'></i> CSV</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('excel')"><i class='bx bx-spreadsheet'></i> Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('pdf')"><i class='bx bxs-file-pdf'></i> PDF</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Customers</h6>
                        <h4 class="mb-0"><?= number_format($stats['total_customers'] ?? 0) ?></h4>
                        <small class="text-success">
                            <i class='bx bx-user'></i> <?= number_format($stats['active_customers'] ?? 0) ?> Active
                        </small>
                    </div>
                    <div class="text-primary">
                        <i class='bx bx-group fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Active Plans</h6>
                        <h4 class="mb-0"><?= number_format($stats['active_plans'] ?? 0) ?></h4>
                        <small class="text-info">
                            <i class='bx bx-broadcast'></i> Total: <?= number_format($stats['total_plans'] ?? 0) ?>
                        </small>
                    </div>
                    <div class="text-success">
                        <i class='bx bx-wifi fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Pending Bills</h6>
                        <h4 class="mb-0"><?= number_format($stats['pending_bills'] ?? 0) ?></h4>
                        <small class="text-warning">
                            <i class='bx bx-money'></i> <?= formatCurrency($stats['pending_amount'] ?? 0) ?>
                        </small>
                    </div>
                    <div class="text-danger">
                        <i class='bx bx-receipt fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">New This Month</h6>
                        <h4 class="mb-0"><?= number_format($stats['new_customers'] ?? 0) ?></h4>
                        <small class="text-success">
                            <i class='bx bx-trending-up'></i> Growth: <?= number_format($stats['growth_rate'] ?? 0) ?>%
                        </small>
                    </div>
                    <div class="text-info">
                        <i class='bx bx-line-chart fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class='bx bx-filter'></i> Filters</h5>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
            <i class='bx bx-reset'></i> Reset
        </button>
    </div>
    <div class="card-body">
        <form method="GET" action="/admin/customers" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="search" class="form-label text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Name, Email, Account #..." 
                               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="status" class="form-label text-muted">Status</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-check-circle'></i></span>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= ('active' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="suspended" <?= ('suspended' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                Suspended
                            </option>
                            <option value="terminated" <?= ('terminated' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                Terminated
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="plan" class="form-label text-muted">Plan</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-wifi'></i></span>
                        <select class="form-select" id="plan" name="plan">
                            <option value="">All Plans</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" 
                                        <?= ($plan['id'] == ($filters['plan'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date_range" class="form-label text-muted">Join Date</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-calendar'></i></span>
                        <select class="form-select" id="date_range" name="date_range">
                            <option value="">All Time</option>
                            <option value="today" <?= ('today' == ($filters['date_range'] ?? '')) ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= ('week' == ($filters['date_range'] ?? '')) ? 'selected' : '' ?>>This Week</option>
                            <option value="month" <?= ('month' == ($filters['date_range'] ?? '')) ? 'selected' : '' ?>>This Month</option>
                            <option value="year" <?= ('year' == ($filters['date_range'] ?? '')) ? 'selected' : '' ?>>This Year</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="form-label text-muted">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class='bx bx-filter-alt'></i> Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($customers)): ?>
            <div class="alert alert-info">
                No customers found matching your criteria.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th style="width: 120px;">Account #</th>
                            <th style="width: 200px;">Name</th>
                            <th style="width: 200px;">Email</th>
                            <th style="width: 180px;">Plan</th>
                            <th style="width: 120px;">Join Date</th>
                            <th style="width: 100px;" class="text-center">Status</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="text-center" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input customer-select" 
                                           value="<?= $customer['id'] ?>">
                                </td>
                                <td><?= htmlspecialchars($customer['account_number'] ?? 'N/A') ?></td>
                                <td>
                                    <?= htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?>
                                </td>
                                <td><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></td>
                                <td>
                                    <?= htmlspecialchars($customer['plan_name'] ?? 'No Plan') ?>
                                    <?php if (!empty($customer['bandwidth'])): ?>
                                    <small class="text-muted d-block">
                                        <?= formatBandwidth($customer['bandwidth']) ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($customer['created_at']) ? formatDate($customer['created_at']) : 'N/A' ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= getStatusBadgeClass($customer['status'] ?? 'unknown') ?> px-2">
                                        <?= ucfirst($customer['status'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td class="text-end" style="width: 140px;">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Customer actions">
                                        <a href="/admin/customers/view/<?= $customer['id'] ?>" 
                                           class="btn btn-outline-info" data-bs-toggle="tooltip" title="View Details">
                                            <i class='bx bx-show'></i>
                                        </a>
                                        <a href="/admin/customers/edit/<?= $customer['id'] ?>" 
                                           class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit Customer">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning suspend-customer" 
                                                data-id="<?= $customer['id'] ?>" data-bs-toggle="tooltip" title="Suspend Customer">
                                            <i class='bx bx-pause-circle'></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-customer" 
                                                data-id="<?= $customer['id'] ?>" data-bs-toggle="tooltip" title="Delete Customer">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                            data-bs-toggle="dropdown" id="bulkActionsBtn" disabled>
                        <i class='bx bx-list-check'></i> Bulk Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item bulk-action" data-action="suspend" href="#">
                                <i class='bx bx-pause-circle text-warning'></i> Suspend Selected
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item bulk-action" data-action="activate" href="#">
                                <i class='bx bx-play-circle text-success'></i> Activate Selected
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item bulk-action" data-action="delete" href="#">
                                <i class='bx bx-trash text-danger'></i> Delete Selected
                            </a>
                        </li>
                    </ul>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $queryString ?>">
                                    <i class='bx bx-chevron-left'></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $queryString ?>">
                                    <i class='bx bx-chevron-right'></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function formatBandwidth($speed) {
    if ($speed >= 1000) {
        return ($speed / 1000) . ' Gbps';
    }
    return $speed . ' Mbps';
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'active' => 'success',
        'suspended' => 'warning',
        'terminated' => 'danger',
        'pending' => 'info',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select All Checkbox
    const selectAll = document.getElementById('selectAll');
    const customerCheckboxes = document.querySelectorAll('.customer-select');
    const bulkActionsBtn = document.getElementById('bulkActionsBtn');

    selectAll?.addEventListener('change', function() {
        customerCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionsState();
    });

    customerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActionsState();
            updateSelectAllState();
        });
    });

    function updateBulkActionsState() {
        const selectedCount = document.querySelectorAll('.customer-select:checked').length;
        bulkActionsBtn.disabled = selectedCount === 0;
    }

    function updateSelectAllState() {
        const totalChecked = document.querySelectorAll('.customer-select:checked').length;
        selectAll.checked = totalChecked === customerCheckboxes.length;
        selectAll.indeterminate = totalChecked > 0 && totalChecked < customerCheckboxes.length;
    }

    // Bulk Actions
    document.querySelectorAll('.bulk-action').forEach(action => {
        action.addEventListener('click', function(e) {
            e.preventDefault();
            const actionType = this.dataset.action;
            const selectedIds = Array.from(document.querySelectorAll('.customer-select:checked'))
                .map(checkbox => checkbox.value);

            if (selectedIds.length === 0) return;

            const confirmMessage = {
                'suspend': 'Are you sure you want to suspend the selected customers?',
                'activate': 'Are you sure you want to activate the selected customers?',
                'delete': 'Are you sure you want to delete the selected customers? This action cannot be undone.'
            }[actionType];

            if (confirm(confirmMessage)) {
                fetch('/admin/customers/bulk-action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    },
                    body: JSON.stringify({
                        action: actionType,
                        ids: selectedIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to perform bulk action');
                    }
                });
            }
        });
    });

    // Individual Customer Actions
    document.querySelectorAll('.suspend-customer').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to suspend this customer?')) {
                const customerId = this.dataset.id;
                
                fetch(`/admin/customers/${customerId}/suspend`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to suspend customer');
                    }
                });
            }
        });
    });

    document.querySelectorAll('.delete-customer').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
                const customerId = this.dataset.id;
                
                fetch(`/admin/customers/${customerId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to delete customer');
                    }
                });
            }
        });
    });

    // Reset Filters
    function resetFilters() {
        document.getElementById('filterForm').reset();
        document.getElementById('filterForm').submit();
    }

    // Export Data
    function exportData(format) {
        const form = document.getElementById('filterForm');
        const params = new URLSearchParams(new FormData(form));
        params.append('format', format);
        window.location.href = `/admin/customers/export?${params.toString()}`;
    }

    // Initialize Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
