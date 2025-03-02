<?php
$title = 'Payroll History - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payroll History</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" id="downloadReport">
            <i class="fa fa-download"></i> Download Report
        </button>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/staff/payroll/history" class="row g-3">
            <div class="col-md-4">
                <label for="year" class="form-label">Year</label>
                <select class="form-select" id="year" name="year">
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                        $selected = ($y == ($year ?? $currentYear)) ? 'selected' : '';
                        echo "<option value=\"{$y}\" {$selected}>{$y}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="month" class="form-label">Month</label>
                <select class="form-select" id="month" name="month">
                    <option value="">All Months</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $selected = ($m == ($month ?? '')) ? 'selected' : '';
                        echo "<option value=\"{$m}\" {$selected}>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
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
                <h5 class="card-title">Total Earnings</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column($payrollHistory, 'net_salary'))) ?>
                </h3>
                <p class="mb-0">Year to Date</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Basic Salary</h5>
                <h3 class="card-text">
                    <?= formatCurrency($currentPayroll['basic_salary'] ?? 0) ?>
                </h3>
                <p class="mb-0">Current Rate</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Total Allowances</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column($payrollHistory, 'allowances'))) ?>
                </h3>
                <p class="mb-0">Year to Date</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Total Deductions</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column($payrollHistory, 'deductions'))) ?>
                </h3>
                <p class="mb-0">Year to Date</p>
            </div>
        </div>
    </div>
</div>

<!-- Payroll History Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($payrollHistory)): ?>
            <div class="alert alert-info">
                No payroll records found for the selected period.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Overtime</th>
                            <th>Tax</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrollHistory as $payroll): ?>
                            <tr>
                                <td>
                                    <?= date('M Y', strtotime($payroll['period_start'])) ?>
                                </td>
                                <td><?= formatCurrency($payroll['basic_salary']) ?></td>
                                <td>
                                    <?= formatCurrency($payroll['allowances']) ?>
                                    <?php if ($payroll['allowance_types']): ?>
                                        <i class="fa fa-info-circle text-info" 
                                           data-bs-toggle="tooltip" 
                                           title="<?= htmlspecialchars($payroll['allowance_types']) ?>">
                                        </i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= formatCurrency($payroll['deductions']) ?>
                                    <?php if ($payroll['deduction_reasons']): ?>
                                        <i class="fa fa-info-circle text-info" 
                                           data-bs-toggle="tooltip" 
                                           title="<?= htmlspecialchars($payroll['deduction_reasons']) ?>">
                                        </i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($payroll['overtime_hours'] > 0): ?>
                                        <?= $payroll['overtime_hours'] ?> hrs
                                        <span class="text-muted">
                                            (<?= formatCurrency($payroll['overtime_hours'] * $payroll['overtime_rate']) ?>)
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= formatCurrency($payroll['tax']) ?></td>
                                <td class="fw-bold"><?= formatCurrency($payroll['net_salary']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getPaymentStatusClass($payroll['payment_status']) ?>">
                                        <?= ucfirst($payroll['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info view-details" 
                                                data-id="<?= $payroll['id'] ?>" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <a href="/staff/payroll/download-slip/<?= $payroll['id'] ?>" 
                                           class="btn btn-primary" title="Download Payslip">
                                            <i class="fa fa-download"></i>
                                        </a>
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

<!-- Payroll Details Modal -->
<div class="modal fade" id="payrollDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payroll Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="payrollDetails">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function getPaymentStatusClass($status) {
    return match ($status) {
        'paid' => 'success',
        'pending' => 'warning',
        'processing' => 'info',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // View Details Handler
    const payrollModal = new bootstrap.Modal(document.getElementById('payrollDetailsModal'));
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const payrollId = this.dataset.id;
            
            fetch(`/staff/payroll/details/${payrollId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('payrollDetails').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Pay Period</h6>
                                <p>${new Date(data.period_start).toLocaleDateString()} - 
                                   ${new Date(data.period_end).toLocaleDateString()}</p>
                                
                                <h6>Basic Salary</h6>
                                <p>${formatCurrency(data.basic_salary)}</p>
                                
                                <h6>Allowances</h6>
                                <ul>
                                    ${Object.entries(data.allowances).map(([type, amount]) => 
                                        `<li>${type}: ${formatCurrency(amount)}</li>`
                                    ).join('')}
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Deductions</h6>
                                <ul>
                                    ${Object.entries(data.deductions).map(([reason, amount]) => 
                                        `<li>${reason}: ${formatCurrency(amount)}</li>`
                                    ).join('')}
                                </ul>
                                
                                <h6>Overtime</h6>
                                <p>${data.overtime.hours} hours @ ${formatCurrency(data.overtime.rate)}/hr = 
                                   ${formatCurrency(data.overtime.amount)}</p>
                                
                                <h6>Tax</h6>
                                <p>${formatCurrency(data.tax)}</p>
                            </div>
                            <div class="col-12 mt-3">
                                <h5 class="text-end">
                                    Net Salary: ${formatCurrency(data.net_salary)}
                                </h5>
                            </div>
                        </div>
                    `;
                    payrollModal.show();
                });
        });
    });

    // Download Report Handler
    document.getElementById('downloadReport').addEventListener('click', function() {
        const form = document.querySelector('form');
        const params = new URLSearchParams(new FormData(form));
        window.location.href = '/staff/payroll/download-report?' + params.toString();
    });
});

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>
