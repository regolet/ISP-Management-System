<?php
$title = 'Search Payments - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Search Payments</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/staff/payments" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<!-- Advanced Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/staff/payments/search" id="searchForm">
            <div class="row g-3">
                <!-- Search Text -->
                <div class="col-md-12">
                    <div class="input-group">
                        <input type="text" class="form-control" id="query" name="query" 
                               placeholder="Search by receipt number, reference number, or description..."
                               value="<?= htmlspecialchars($filters['query'] ?? '') ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="col-md-12">
                    <a class="btn btn-link p-0" data-bs-toggle="collapse" href="#advancedSearch">
                        <i class="fa fa-filter"></i> Advanced Filters
                    </a>
                </div>

                <div class="collapse <?= !empty($filters) ? 'show' : '' ?>" id="advancedSearch">
                    <div class="row g-3">
                        <!-- Date Range -->
                        <div class="col-md-6">
                            <label class="form-label">Date Range</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?= $filters['date_from'] ?? '' ?>">
                                <span class="input-group-text">to</span>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?= $filters['date_to'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Amount Range -->
                        <div class="col-md-6">
                            <label class="form-label">Amount Range</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="amount_min" 
                                       name="amount_min" placeholder="Min" 
                                       value="<?= $filters['amount_min'] ?? '' ?>">
                                <span class="input-group-text">to</span>
                                <input type="number" step="0.01" class="form-control" id="amount_max" 
                                       name="amount_max" placeholder="Max" 
                                       value="<?= $filters['amount_max'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Payment Type -->
                        <div class="col-md-4">
                            <label for="payment_type" class="form-label">Payment Type</label>
                            <select class="form-select" id="payment_type" name="payment_type">
                                <option value="">All Types</option>
                                <?php foreach ($paymentTypes as $type): ?>
                                    <option value="<?= $type['id'] ?>" 
                                            <?= ($type['id'] == ($filters['payment_type'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <?php foreach ($paymentMethods as $method): ?>
                                    <option value="<?= $method['id'] ?>" 
                                            <?= ($method['id'] == ($filters['payment_method'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($method['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?= ('pending' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                    Pending
                                </option>
                                <option value="approved" <?= ('approved' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                    Approved
                                </option>
                                <option value="processed" <?= ('processed' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                    Processed
                                </option>
                                <option value="rejected" <?= ('rejected' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                                    Rejected
                                </option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div class="col-md-6">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="date_desc" <?= ('date_desc' == ($filters['sort'] ?? '')) ? 'selected' : '' ?>>
                                    Date (Newest First)
                                </option>
                                <option value="date_asc" <?= ('date_asc' == ($filters['sort'] ?? '')) ? 'selected' : '' ?>>
                                    Date (Oldest First)
                                </option>
                                <option value="amount_desc" <?= ('amount_desc' == ($filters['sort'] ?? '')) ? 'selected' : '' ?>>
                                    Amount (Highest First)
                                </option>
                                <option value="amount_asc" <?= ('amount_asc' == ($filters['sort'] ?? '')) ? 'selected' : '' ?>>
                                    Amount (Lowest First)
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-6 text-end">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="reset" class="btn btn-secondary" id="resetFilters">
                                <i class="fa fa-undo"></i> Reset Filters
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Search Results</h5>
        <?php if (!empty($payments)): ?>
            <span class="badge bg-primary"><?= count($payments) ?> Results Found</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="alert alert-info">
                No payments found matching your search criteria.
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
                                    <?= htmlspecialchars($payment['reference_number']) ?: 
                                        '<span class="text-muted">-</span>' ?>
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
    // Date Range Validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
    });

    dateTo.addEventListener('change', function() {
        dateFrom.max = this.value;
    });

    // Amount Range Validation
    const amountMin = document.getElementById('amount_min');
    const amountMax = document.getElementById('amount_max');

    amountMin.addEventListener('change', function() {
        if (amountMax.value && parseFloat(this.value) > parseFloat(amountMax.value)) {
            this.value = amountMax.value;
        }
    });

    amountMax.addEventListener('change', function() {
        if (amountMin.value && parseFloat(this.value) < parseFloat(amountMin.value)) {
            this.value = amountMin.value;
        }
    });

    // Reset Filters
    document.getElementById('resetFilters').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('searchForm').reset();
        document.getElementById('query').value = '';
        window.location.href = '/staff/payments/search';
    });
});
</script>
