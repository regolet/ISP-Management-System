<?php
$title = 'Asset Report - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Asset Report</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa fa-print"></i> Print Report
        </button>
        <a href="/admin/assets" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Assets
        </a>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="/admin/assets/report" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter Report
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
                <h5 class="card-title">Total Assets</h5>
                <h3 class="card-text"><?= count($assets) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Value</h5>
                <h3 class="card-text"><?= formatCurrency($totalValue) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Depreciation</h5>
                <h3 class="card-text"><?= formatCurrency($totalDepreciation) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Net Value</h5>
                <h3 class="card-text"><?= formatCurrency($totalValue - $totalDepreciation) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Asset Value by Type -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Asset Value by Type</h5>
            </div>
            <div class="card-body">
                <canvas id="assetTypeChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Expense Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="expenseChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Expense Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Expense Summary</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Expense Type</th>
                        <th>Total Amount</th>
                        <th>Count</th>
                        <th>Average</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenseStats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($stat['expense_type'])) ?></td>
                            <td><?= formatCurrency($stat['total_amount']) ?></td>
                            <td><?= $stat['total_expenses'] ?></td>
                            <td><?= formatCurrency($stat['average_amount']) ?></td>
                            <td>
                                <span class="badge bg-<?= getPaymentStatusBadgeClass($stat['payment_status']) ?>">
                                    <?= ucfirst(htmlspecialchars($stat['payment_status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Asset Details -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Asset Details</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Type</th>
                        <th>Purchase Value</th>
                        <th>Current Value</th>
                        <th>Depreciation</th>
                        <th>Expenses</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td>
                                <a href="/admin/assets/<?= $asset['id'] ?>">
                                    <?= htmlspecialchars($asset['name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars(ucfirst($asset['asset_type'])) ?></td>
                            <td><?= formatCurrency($asset['purchase_price']) ?></td>
                            <td><?= formatCurrency($asset['current_value']) ?></td>
                            <td><?= formatCurrency($asset['total_depreciation']) ?></td>
                            <td>
                                <?php
                                $totalExpenses = array_reduce($asset['expenses'], function($carry, $expense) {
                                    return $carry + $expense['amount'];
                                }, 0);
                                echo formatCurrency($totalExpenses);
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeClass($asset['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($asset['status'])) ?>
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
function getStatusBadgeClass($status) {
    return match ($status) {
        'available' => 'success',
        'collected' => 'warning',
        'maintenance' => 'danger',
        default => 'secondary'
    };
}

function getPaymentStatusBadgeClass($status) {
    return match ($status) {
        'paid' => 'success',
        'pending' => 'warning',
        'overdue' => 'danger',
        default => 'secondary'
    };
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<!-- Print Styles -->
<style media="print">
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .card-header {
        background: none !important;
        border-bottom: 2px solid #000 !important;
    }
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .table td, .table th {
        border: 1px solid #ddd !important;
    }
    @page {
        size: landscape;
    }
</style>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for asset type chart
    const assetTypes = <?= json_encode(array_column($assets, 'asset_type')) ?>;
    const assetValues = <?= json_encode(array_column($assets, 'current_value')) ?>;
    
    // Asset Type Chart
    new Chart(document.getElementById('assetTypeChart'), {
        type: 'pie',
        data: {
            labels: assetTypes.map(type => type.charAt(0).toUpperCase() + type.slice(1)),
            datasets: [{
                data: assetValues,
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Prepare data for expense chart
    const expenseData = <?= json_encode($expenseStats) ?>;
    const expenseTypes = expenseData.map(stat => stat.expense_type);
    const expenseAmounts = expenseData.map(stat => stat.total_amount);

    // Expense Chart
    new Chart(document.getElementById('expenseChart'), {
        type: 'bar',
        data: {
            labels: expenseTypes.map(type => type.charAt(0).toUpperCase() + type.slice(1)),
            datasets: [{
                label: 'Total Expenses',
                data: expenseAmounts,
                backgroundColor: '#17a2b8'
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
});
</script>
