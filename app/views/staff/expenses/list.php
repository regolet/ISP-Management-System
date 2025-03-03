<?php
$title = 'Expense Management - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Expense Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/staff/expenses/add" class="btn btn-primary">
                <i class="fa fa-plus"></i> Add Expense
            </a>
            <a href="/staff/expenses/report" class="btn btn-info">
                <i class="fa fa-chart-bar"></i> View Report
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Expenses</h5>
                <h3 class="card-text">
                    <?= formatCurrency($summary['total_amount'] ?? 0) ?>
                </h3>
                <p class="mb-0">This Month</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Approved</h5>
                <h3 class="card-text">
                    <?= formatCurrency($summary['approved_amount'] ?? 0) ?>
                </h3>
                <p class="mb-0"><?= $summary['approved_count'] ?? 0 ?> Claims</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h3 class="card-text">
                    <?= formatCurrency($summary['pending_amount'] ?? 0) ?>
                </h3>
                <p class="mb-0"><?= $summary['pending_count'] ?? 0 ?> Claims</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">Rejected</h5>
                <h3 class="card-text">
                    <?= formatCurrency($summary['rejected_amount'] ?? 0) ?>
                </h3>
                <p class="mb-0"><?= $summary['rejected_count'] ?? 0 ?> Claims</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/staff/expenses" class="row g-3">
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= ($category['id'] == ($filters['category'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= ('pending' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                        Pending
                    </option>
                    <option value="approved" <?= ('approved' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                        Approved
                    </option>
                    <option value="rejected" <?= ('rejected' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                        Rejected
                    </option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= $filters['date_from'] ?? '' ?>">
                    <span class="input-group-text">to</span>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= $filters['date_to'] ?? '' ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Expenses Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($expenses)): ?>
            <div class="alert alert-info">
                No expense records found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($expense['date'])) ?></td>
                                <td><?= htmlspecialchars($expense['category_name']) ?></td>
                                <td><?= htmlspecialchars($expense['description']) ?></td>
                                <td><?= formatCurrency($expense['amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($expense['status']) ?>">
                                        <?= ucfirst($expense['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info view-expense" 
                                                data-id="<?= $expense['id'] ?>" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <?php if ($expense['status'] === 'pending'): ?>
                                            <a href="/staff/expenses/edit/<?= $expense['id'] ?>" 
                                               class="btn btn-primary" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger delete-expense" 
                                                    data-id="<?= $expense['id'] ?>" title="Delete">
                                                <i class="fa fa-trash"></i>
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

<!-- Expense Details Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="expenseDetails">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'approved' => 'success',
        'pending' => 'warning',
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

    // View Expense Details
    const expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    document.querySelectorAll('.view-expense').forEach(button => {
        button.addEventListener('click', function() {
            const expenseId = this.dataset.id;
            
            fetch(`/staff/expenses/${expenseId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('expenseDetails').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="35%">Date</th>
                                        <td>${new Date(data.date).toLocaleDateString()}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>${data.category_name}</td>
                                    </tr>
                                    <tr>
                                        <th>Amount</th>
                                        <td>${formatCurrency(data.amount)}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-${getStatusBadgeClass(data.status)}">
                                                ${data.status}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="35%">Reference #</th>
                                        <td>${data.reference_number || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Approved By</th>
                                        <td>${data.approved_by_name || 'Pending'}</td>
                                    </tr>
                                    <tr>
                                        <th>Approved At</th>
                                        <td>${data.approved_at ? 
                                            new Date(data.approved_at).toLocaleString() : 
                                            'Pending'}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-12">
                                <h6>Description</h6>
                                <p>${data.description}</p>
                            </div>
                            ${data.remarks ? `
                                <div class="col-12">
                                    <h6>Remarks</h6>
                                    <p>${data.remarks}</p>
                                </div>
                            ` : ''}
                            ${data.attachment_path ? `
                                <div class="col-12">
                                    <h6>Attachment</h6>
                                    <a href="${data.attachment_path}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa fa-download"></i> Download Attachment
                                    </a>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    expenseModal.show();
                });
        });
    });

    // Delete Expense Handler
    document.querySelectorAll('.delete-expense').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this expense?')) {
                const expenseId = this.dataset.id;
                
                fetch(`/staff/expenses/${expenseId}`, {
                    method: 'DELETE',
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
                        alert(data.error || 'Failed to delete expense');
                    }
                });
            }
        });
    });
});

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>
