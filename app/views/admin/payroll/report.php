<?php
$title = 'Payroll Report - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payroll Report</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/payroll" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Payroll
            </a>
            <button type="button" class="btn btn-success" id="exportReport">
                <i class="fa fa-download"></i> Export Report
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/payroll/report" class="row g-3">
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select class="form-select" id="month" name="month">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select class="form-select" id="year" name="year">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $key => $name): ?>
                        <option value="<?= $key ?>" 
                                <?= ($filters['department'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Basic Pay</h6>
                <h3 class="mb-0"><?= formatCurrency($summary['total_basic']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Deductions</h6>
                <h3 class="mb-0"><?= formatCurrency($summary['total_deductions']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Net Pay</h6>
                <h3 class="mb-0"><?= formatCurrency($summary['total_net']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Employees</h6>
                <h3 class="mb-0"><?= $summary['total_employees'] ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Department Summary -->
<?php if (!empty($department_summary)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Department Summary</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th class="text-end">Employees</th>
                            <th class="text-end">Basic Pay</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($department_summary as $dept): ?>
                            <tr>
                                <td><?= htmlspecialchars($dept['name']) ?></td>
                                <td class="text-end"><?= $dept['employees'] ?></td>
                                <td class="text-end"><?= formatCurrency($dept['basic_pay']) ?></td>
                                <td class="text-end"><?= formatCurrency($dept['deductions']) ?></td>
                                <td class="text-end"><?= formatCurrency($dept['net_pay']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Payroll Details -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Payroll Details</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Working Days</th>
                        <th>Basic Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payroll_items)): ?>
                        <?php foreach ($payroll_items as $item): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($item['department']) ?> - 
                                        <?= formatCurrency($item['daily_rate']) ?>/day
                                    </small>
                                </td>
                                <td>
                                    <div><?= number_format($item['working_days'], 1) ?> days</div>
                                    <?php if ($item['overtime_hours']): ?>
                                        <small class="text-muted">
                                            OT: <?= number_format($item['overtime_hours'], 1) ?> hrs
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= formatCurrency($item['basic_salary']) ?></div>
                                    <?php if ($item['overtime_pay']): ?>
                                        <small class="text-muted">
                                            OT: <?= formatCurrency($item['overtime_pay']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= formatCurrency($item['total_deductions']) ?></div>
                                    <button type="button" class="btn btn-link btn-sm p-0" 
                                            onclick="viewDeductions(<?= htmlspecialchars(json_encode($item['deductions'])) ?>)">
                                        View Details
                                    </button>
                                </td>
                                <td><?= formatCurrency($item['net_salary']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="printPayslip(<?= $item['id'] ?>)">
                                        <i class="fa fa-print"></i> Payslip
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No payroll records found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Deductions Modal -->
<div class="modal fade" id="deductionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deduction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tbody id="deductionsTable"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deductionsModal = new bootstrap.Modal(document.getElementById('deductionsModal'));

    // View deductions details
    window.viewDeductions = function(deductions) {
        const table = document.getElementById('deductionsTable');
        table.innerHTML = '';
        
        Object.entries(deductions).forEach(([type, amount]) => {
            const row = table.insertRow();
            row.insertCell(0).textContent = type.replace('_', ' ').toUpperCase();
            row.insertCell(1).textContent = formatCurrency(amount);
            row.cells[1].style.textAlign = 'right';
        });
        
        deductionsModal.show();
    };

    // Print payslip
    window.printPayslip = function(id) {
        window.open(`/admin/payroll/payslip/${id}`, '_blank');
    };

    // Export report
    document.getElementById('exportReport').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = '/admin/payroll/report?' + params.toString();
    });

    // Filter change handlers
    ['month', 'year', 'department'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });

    // Helper functions
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
});
</script>
