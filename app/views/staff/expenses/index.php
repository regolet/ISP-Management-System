<?php
$title = 'Expense Management - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Expense Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="fa fa-plus"></i> Submit New Expense
        </button>
    </div>
</div>

<!-- Expense Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Claims</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column($expenses, 'amount'))) ?>
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
                    <?= formatCurrency(array_sum(array_column(
                        array_filter($expenses, fn($e) => $e['status'] === 'approved'),
                        'amount'
                    ))) ?>
                </h3>
                <p class="mb-0">Reimbursed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column(
                        array_filter($expenses, fn($e) => $e['status'] === 'pending'),
                        'amount'
                    ))) ?>
                </h3>
                <p class="mb-0">Awaiting Approval</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Categories</h5>
                <h3 class="card-text"><?= count($categories) ?></h3>
                <p class="mb-0">Available</p>
            </div>
        </div>
    </div>
</div>

<!-- Expense List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Expense Claims</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" id="filterExpenses">
                <i class="fa fa-filter"></i> Filter
            </button>
            <button type="button" class="btn btn-outline-primary" id="downloadReport">
                <i class="fa fa-download"></i> Download Report
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <div class="collapse mb-3" id="filterForm">
            <div class="card card-body">
                <form class="row g-3">
                    <div class="col-md-3">
                        <label for="dateRange" class="form-label">Date Range</label>
                        <select class="form-select" id="dateRange" name="date_range">
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
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
                                <?php if ($expense['payment_date']): ?>
                                    <small class="text-muted">
                                        Paid on <?= date('M d, Y', strtotime($expense['payment_date'])) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pending Payment</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info view-expense" 
                                            data-id="<?= $expense['id'] ?>" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <?php if ($expense['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-primary edit-expense" 
                                                data-id="<?= $expense['id'] ?>" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </button>
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
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit New Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="expenseForm" enctype="multipart/form-data">
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" 
                                       id="amount" name="amount" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="receipt" class="form-label">Receipt</label>
                            <input type="file" class="form-control" id="receipt" name="receipt" 
                                   accept="image/*,.pdf">
                            <div class="form-text">Max size: 5MB. Accepted formats: Images, PDF</div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitExpense">Submit Expense</button>
            </div>
        </div>
    </div>
</div>

<!-- Expense Details Modal -->
<div class="modal fade" id="expenseDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="expenseDetails"></div>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusBadgeClass($status) {
    return match ($status) {
        'approved' => 'success',
        'pending' => 'warning',
        'rejected' => 'danger',
        default => 'secondary'
    };
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter Toggle
    document.getElementById('filterExpenses').addEventListener('click', function() {
        const filterForm = document.getElementById('filterForm');
        bootstrap.Collapse.getOrCreateInstance(filterForm).toggle();
    });

    // Expense Form Submission
    document.getElementById('submitExpense').addEventListener('click', function() {
        const form = document.getElementById('expenseForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        
        fetch('/staff/expenses', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to submit expense');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the expense');
        });
    });

    // View Expense Details
    const expenseDetailsModal = new bootstrap.Modal(document.getElementById('expenseDetailsModal'));
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
                                        <th width="35%">Submitted</th>
                                        <td>${new Date(data.created_at).toLocaleString()}</td>
                                    </tr>
                                    <tr>
                                        <th>Payment</th>
                                        <td>${data.payment_date ? 
                                            new Date(data.payment_date).toLocaleDateString() : 
                                            'Pending'}</td>
                                    </tr>
                                    <tr>
                                        <th>Receipt</th>
                                        <td>
                                            ${data.receipt_file ? 
                                                `<a href="/uploads/receipts/${data.receipt_file}" target="_blank">
                                                    View Receipt
                                                </a>` : 
                                                'No receipt uploaded'}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-12">
                                <h6>Description</h6>
                                <p>${data.description}</p>
                            </div>
                            ${data.status === 'rejected' ? `
                                <div class="col-12">
                                    <div class="alert alert-danger">
                                        <h6>Rejection Reason</h6>
                                        <p>${data.rejection_reason}</p>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    expenseDetailsModal.show();
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

    // Download Report Handler
    document.getElementById('downloadReport').addEventListener('click', function() {
        const form = document.querySelector('#filterForm form');
        const params = new URLSearchParams(new FormData(form));
        window.location.href = '/staff/expenses/report/download?' + params.toString();
    });
});
</script>
