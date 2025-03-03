<?php
$title = 'Billing Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Billing Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/billing/create" class="btn btn-primary">
            <i class="fa fa-plus"></i> Create Invoice
        </a>
    </div>
</div>

<!-- Search Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/billing" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search invoices..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="unpaid" <?= ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>
                        Unpaid
                    </option>
                    <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>
                        Paid
                    </option>
                    <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>
                        Partial
                    </option>
                    <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>
                        Overdue
                    </option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="date_range">
                    <option value="">All Dates</option>
                    <option value="today" <?= ($filters['date_range'] ?? '') === 'today' ? 'selected' : '' ?>>
                        Today
                    </option>
                    <option value="week" <?= ($filters['date_range'] ?? '') === 'week' ? 'selected' : '' ?>>
                        This Week
                    </option>
                    <option value="month" <?= ($filters['date_range'] ?? '') === 'month' ? 'selected' : '' ?>>
                        This Month
                    </option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
            
            <div class="col-md-2">
                <button type="button" class="btn btn-success w-100" id="exportBilling">
                    <i class="fa fa-download"></i> Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Billing Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bills['bills'])): ?>
                        <?php foreach ($bills['bills'] as $bill): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($bill['invoiceid']) ?></div>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($bill['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($bill['customer_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($bill['customer_code']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-medium">
                                        <?= formatCurrency($bill['amount']) ?>
                                    </div>
                                    <?php if ($bill['plan_name']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($bill['plan_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $due_date = strtotime($bill['due_date']);
                                    $today = strtotime('today');
                                    $is_overdue = $due_date < $today && $bill['status'] !== 'paid';
                                    ?>
                                    <div class="<?= $is_overdue ? 'text-danger fw-medium' : '' ?>">
                                        <?= date('M d, Y', $due_date) ?>
                                    </div>
                                    <?php if ($is_overdue): ?>
                                        <small class="text-danger">Overdue</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($bill['status']) {
                                            'paid' => 'success',
                                            'unpaid' => 'danger',
                                            'partial' => 'warning',
                                            'void' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($bill['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="/admin/billing/<?= $bill['id'] ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <?php if ($bill['status'] !== 'paid' && $bill['status'] !== 'void'): ?>
                                            <a href="/admin/billing/<?= $bill['id'] ?>/edit" 
                                               class="btn btn-sm btn-primary" title="Edit Invoice">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="/admin/payments/create?billing_id=<?= $bill['id'] ?>" 
                                               class="btn btn-sm btn-success" title="Record Payment">
                                                <i class="fa fa-money-bill"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="voidBill(<?= $bill['id'] ?>)" title="Void Invoice">
                                                <i class="fa fa-ban"></i>
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
                                <p class="text-muted mb-0">No invoices found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($bills['pages'] > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $bills['pages']; $i++): ?>
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
// Export billing data
document.getElementById('exportBilling').addEventListener('click', function() {
    const form = document.querySelector('form');
    const params = new URLSearchParams(new FormData(form));
    window.location.href = '/admin/billing/export?' + params.toString();
});

// Void bill
function voidBill(id) {
    if (confirm('Are you sure you want to void this invoice? This action cannot be undone.')) {
        fetch(`/admin/billing/${id}/void`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to void invoice');
            }
        });
    }
}

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});
</script>
