<?php
$title = 'Payment Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payment Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/payments/create" class="btn btn-primary">
            <i class="fa fa-plus"></i> Record Payment
        </a>
    </div>
</div>

<!-- Search Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/payments" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search payments..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>
                        Completed
                    </option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                        Pending
                    </option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>
                        Failed
                    </option>
                    <option value="void" <?= ($filters['status'] ?? '') === 'void' ? 'selected' : '' ?>>
                        Void
                    </option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="payment_method">
                    <option value="">All Methods</option>
                    <?php foreach ($paymentMethods as $key => $name): ?>
                        <option value="<?= $key ?>" 
                                <?= ($filters['payment_method'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
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
                <button type="button" class="btn btn-success w-100" id="exportPayments">
                    <i class="fa fa-download"></i> Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Payment Date</th>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments['payments'])): ?>
                        <?php foreach ($payments['payments'] as $payment): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium">
                                        <?= date('M d, Y', strtotime($payment['payment_date'])) ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('h:i A', strtotime($payment['payment_date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($payment['customer_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($payment['customer_code']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($payment['invoiceid']) ?></div>
                                    <small class="text-muted">
                                        <?= formatCurrency($payment['invoice_amount']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-medium">
                                        <?= formatCurrency($payment['amount']) ?>
                                    </div>
                                    <?php if ($payment['reference_no']): ?>
                                        <small class="text-muted">
                                            Ref: <?= htmlspecialchars($payment['reference_no']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst($payment['payment_method']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($payment['status']) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            'void' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="/admin/payments/<?= $payment['id'] ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <a href="/admin/payments/<?= $payment['id'] ?>/edit" 
                                               class="btn btn-sm btn-primary" title="Edit Payment">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="voidPayment(<?= $payment['id'] ?>)" 
                                                    title="Void Payment">
                                                <i class="fa fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No payments found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($payments['pages'] > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $payments['pages']; $i++): ?>
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
// Export payments
document.getElementById('exportPayments').addEventListener('click', function() {
    const form = document.querySelector('form');
    const params = new URLSearchParams(new FormData(form));
    window.location.href = '/admin/payments/export?' + params.toString();
});

// Void payment
function voidPayment(id) {
    if (confirm('Are you sure you want to void this payment? This action cannot be undone.')) {
        fetch(`/admin/payments/${id}/void`, {
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
                alert(data.error || 'Failed to void payment');
            }
        });
    }
}

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});
</script>
