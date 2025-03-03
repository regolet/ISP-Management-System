<?php
$title = 'Expense Report - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Expense Report</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="downloadReport">
                <i class="fa fa-download"></i> Download Report
            </button>
            <a href="/staff/expenses" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Expenses
            </a>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/staff/expenses/report" id="reportForm" class="row g-3">
            <div class="col-md-4">
                <label for="period" class="form-label">Report Period</label>
                <select class="form-select" id="period" name="period">
                    <option value="month" <?= ($period === 'month') ? 'selected' : '' ?>>This Month</option>
                    <option value="quarter" <?= ($period === 'quarter') ? 'selected' : '' ?>>This Quarter</option>
                    <option value="year" <?= ($period === 'year') ? 'selected' : '' ?>>This Year</option>
                    <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-md-6 <?= ($period !== 'custom') ? 'd-none' : '' ?>" id="dateRangeInputs">
                <label class="form-label">Custom Date Range</label>
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
                    <i class="fa fa-sync"></i> Update Report
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
                <h5 class="card-title">Total Expenses</h5>
                <h3 class="card-text">
                    <?= formatCurrency($summary['total_amount'] ?? 0) ?>
                </h3>
                <p class="mb-0"><?= $summary['total_expenses'] ?? 0 ?> Claims</p>
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

<div class="row">
    <!-- Category Summary -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Expenses by Category</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Claims</th>
                                <th>Amount</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorySummary as $category): ?>
                                <tr>
                                    <td><?= htmlspecialchars($category['category_name']) ?></td>
                                    <td><?= $category['expense_count'] ?></td>
                                    <td><?= formatCurrency($category['total_amount']) ?></td>
                                    <td><?= number_format(($category['total_amount'] / $summary['total_amount']) * 100, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Monthly Expense Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Report Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detailed Report</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Reference #</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($expense['date'])) ?></td>
                            <td><?= htmlspecialchars($expense['category_name']) ?></td>
                            <td><?= htmlspecialchars($expense['description']) ?></td>
                            <td><?= htmlspecialchars($expense['reference_number'] ?: '-') ?></td>
                            <td><?= formatCurrency($expense['amount']) ?></td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeClass($expense['status']) ?>">
                                    <?= ucfirst($expense['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Period Selection Handler
    const period = document.getElementById('period');
    const dateRangeInputs = document.getElementById('dateRangeInputs');
    
    period.addEventListener('change', function() {
        dateRangeInputs.classList.toggle('d-none', this.value !== 'custom');
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

    // Category Chart
    const categoryData = <?= json_encode($categorySummary) ?>;
    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: categoryData.map(c => c.category_name),
            datasets: [{
                data: categoryData.map(c => c.total_amount),
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545',
                    '#17a2b8', '#6c757d', '#6f42c1', '#fd7e14'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Trend Chart
    const trendData = <?= json_encode($monthlyTrend) ?>;
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendData.map(d => d.month),
            datasets: [{
                label: 'Total Expenses',
                data: trendData.map(d => d.amount),
                borderColor: '#007bff',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Download Report Handler
    document.getElementById('downloadReport').addEventListener('click', function() {
        const form = document.getElementById('reportForm');
        const params = new URLSearchParams(new FormData(form));
        window.location.href = '/staff/expenses/report/download?' + params.toString();
    });
});
</script>
