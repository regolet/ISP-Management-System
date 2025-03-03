<?php
$title = 'Payment History - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payment History</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="downloadReport">
                <i class="fa fa-download"></i> Download Report
            </button>
            <a href="/staff/payments/add" class="btn btn-success">
                <i class="fa fa-plus"></i> Request Payment
            </a>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/staff/payments" class="row g-3">
            <div class="col-md-3">
                <label for="payment_type" class="form-label">Payment Type</label>
                <select class="form-select" id="payment_type" name="payment_type">
                    <option value="">All Types</option>
                    <?php foreach ($paymentTypes as $type): ?>
                        <option value="<?= $type['id'] ?>" <?= ($type['id'] == ($filters['payment_type'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= ('pending' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ('approved' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>Approved</option>
                    <option value="processed" <?= ('processed' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>Processed</option>
                    <option value="rejected" <?= ('rejected' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= $filters['date_from'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= $filters['date_to'] ?? '' ?>">
            </div>
            <div class="col-md-9">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by receipt number, reference number..."
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Payments</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column($payments, 'amount'))) ?>
                </h3>
                <p class="mb-0">All Time</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Processed</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column(
                        array_filter($payments, fn($p) => $p['status'] === 'processed'),
                        'amount'
                    ))) ?>
                </h3>
                <p class="mb-0">Completed Payments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column(
                        array_filter($payments, fn($p) => $p['status'] === 'pending'),
                        'amount'
                    ))) ?>
                </h3>
                <p class="mb-0">Awaiting Processing</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">This Month</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column(
                        array_filter($payments, fn($p) => 
                            date('Y-m', strtotime($p['payment_date'])) === date('Y-m')
                        ),
                        'amount'
                    ))) ?>
                </h3>
                <p class="mb-0">Current Month Total</p>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="alert alert-info">
                No payment records found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt #</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Reference #</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <?php if ($payment['receipt_number']): ?>
                                        <a href="/staff/payments/view/<?= $payment['id'] ?>">
                                            <?= htmlspecialchars($payment['receipt_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($payment['payment_type_name']) ?></td>
                                <td><?= htmlspecialchars($payment['payment_method_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($payment['reference_number']) ?: '<span class="text-muted">-</span>' ?>
                                </td>
                                <td><?= formatCurrency($payment['amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($payment['status']) ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/staff/payments/view/<?= $payment['id'] ?>" 
                                           class="btn btn-info" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <?php if ($payment['receipt_number']): ?>
                                            <a href="/staff/payments/generate-receipt/<?= $payment['id'] ?>" 
                                               class="btn btn-primary" title="Download Receipt">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-danger cancel-payment" 
                                                    data-id="<?= $payment['id'] ?>" title="Cancel Request">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'processed' => 'success',
        'pending' => 'warning',
        'approved' => 'info',
        'rejected' => 'danger',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Download Report Handler
    document.getElementById('downloadReport').addEventListener('click', function() {
        const form = document.querySelector('form');
        const params = new URLSearchParams(new FormData(form));
        window.location.href = '/staff/payments/download-report?' + params.toString();
    });

    // Cancel Payment Handler
    document.querySelectorAll('.cancel-payment').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this payment request?')) {
                const paymentId = this.dataset.id;
                
                fetch(`/staff/payments/${paymentId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to cancel payment request');
                    }
                });
            }
        });
    });

    // Date Range Validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
    });

    dateTo.addEventListener('change', function() {
        dateFrom.max = this.value;
    });
});
</script>
