<?php
$title = 'Create Payroll - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Create Payroll</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/payroll" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Payroll
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/payroll" id="payrollForm" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- Payroll Period -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="period_start" class="form-label">Period Start *</label>
                            <input type="date" class="form-control" id="period_start" name="period_start" 
                                   value="<?= old('period_start') ?>" required>
                            <?php if (isset($errors['period_start'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['period_start'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label for="period_end" class="form-label">Period End *</label>
                            <input type="date" class="form-control" id="period_end" name="period_end" 
                                   value="<?= old('period_end') ?>" required>
                            <?php if (isset($errors['period_end'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['period_end'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label for="pay_date" class="form-label">Pay Date *</label>
                            <input type="date" class="form-control" id="pay_date" name="pay_date" 
                                   value="<?= old('pay_date') ?>" required>
                            <?php if (isset($errors['pay_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['pay_date'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Employee Selection -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Select Employees</h5>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                                <label class="form-check-label" for="selectAll">Select All</label>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;"></th>
                                        <th>Employee</th>
                                        <th>Position</th>
                                        <th>Daily Rate</th>
                                        <th>Working Days</th>
                                        <th>Basic Pay</th>
                                        <th>Deductions</th>
                                        <th>Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input employee-select" 
                                                       name="employees[]" value="<?= $emp['id'] ?>"
                                                       data-daily-rate="<?= $emp['daily_rate'] ?>">
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($emp['employee_code']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($emp['position']) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($emp['department']) ?>
                                                </small>
                                            </td>
                                            <td><?= formatCurrency($emp['daily_rate']) ?></td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm working-days" 
                                                       name="working_days[<?= $emp['id'] ?>]" min="0" step="0.5" 
                                                       value="<?= $emp['working_days'] ?? '0' ?>" disabled>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm basic-pay" 
                                                       name="basic_pay[<?= $emp['id'] ?>]" readonly>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control deductions" 
                                                           name="deductions[<?= $emp['id'] ?>]" min="0" step="0.01" 
                                                           value="0" disabled>
                                                    <button type="button" class="btn btn-outline-secondary view-deductions" 
                                                            data-employee-id="<?= $emp['id'] ?>" disabled>
                                                        <i class="fa fa-list"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm net-pay" 
                                                       name="net_pay[<?= $emp['id'] ?>]" readonly>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Total Basic Pay</h6>
                                    <h3 id="totalBasicPay">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Total Deductions</h6>
                                    <h3 id="totalDeductions">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Total Net Pay</h6>
                                    <h3 id="totalNetPay">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                            Save as Draft
                        </button>
                        <button type="submit" name="action" value="process" class="btn btn-primary">
                            Process Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Deductions Modal -->
<div class="modal fade" id="deductionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Deductions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="deductionsForm">
                    <input type="hidden" id="deductionEmployeeId">
                    
                    <div class="mb-3">
                        <label class="form-label">SSS Contribution</label>
                        <input type="number" class="form-control deduction-item" 
                               name="sss" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">PhilHealth Contribution</label>
                        <input type="number" class="form-control deduction-item" 
                               name="philhealth" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pag-IBIG Contribution</label>
                        <input type="number" class="form-control deduction-item" 
                               name="pagibig" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tax</label>
                        <input type="number" class="form-control deduction-item" 
                               name="tax" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Other Deductions</label>
                        <input type="number" class="form-control deduction-item" 
                               name="other" step="0.01" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Deductions</label>
                        <input type="text" class="form-control" id="totalDeduction" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveDeductions">Save Deductions</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payrollForm');
    const selectAll = document.getElementById('selectAll');
    const employeeCheckboxes = document.querySelectorAll('.employee-select');
    const deductionsModal = new bootstrap.Modal(document.getElementById('deductionsModal'));

    // Select all functionality
    selectAll.addEventListener('change', function() {
        employeeCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            toggleEmployeeInputs(checkbox);
        });
        updateTotals();
    });

    // Individual checkbox functionality
    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleEmployeeInputs(this);
            updateSelectAll();
            updateTotals();
        });
    });

    // Toggle employee inputs
    function toggleEmployeeInputs(checkbox) {
        const row = checkbox.closest('tr');
        const inputs = row.querySelectorAll('input:not([type="checkbox"])');
        const buttons = row.querySelectorAll('button');
        inputs.forEach(input => input.disabled = !checkbox.checked);
        buttons.forEach(button => button.disabled = !checkbox.checked);
    }

    // Update select all checkbox
    function updateSelectAll() {
        const checkedCount = document.querySelectorAll('.employee-select:checked').length;
        selectAll.checked = checkedCount === employeeCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < employeeCheckboxes.length;
    }

    // Calculate pay
    document.querySelectorAll('.working-days').forEach(input => {
        input.addEventListener('input', function() {
            calculatePay(this.closest('tr'));
        });
    });

    function calculatePay(row) {
        const dailyRate = parseFloat(row.querySelector('.employee-select').dataset.dailyRate);
        const workingDays = parseFloat(row.querySelector('.working-days').value) || 0;
        const deductions = parseFloat(row.querySelector('.deductions').value) || 0;
        
        const basicPay = dailyRate * workingDays;
        const netPay = basicPay - deductions;

        row.querySelector('.basic-pay').value = formatCurrency(basicPay);
        row.querySelector('.net-pay').value = formatCurrency(netPay);

        updateTotals();
    }

    // Update totals
    function updateTotals() {
        let totalBasic = 0;
        let totalDeductions = 0;
        let totalNet = 0;

        document.querySelectorAll('.employee-select:checked').forEach(checkbox => {
            const row = checkbox.closest('tr');
            totalBasic += parseCurrency(row.querySelector('.basic-pay').value);
            totalDeductions += parseFloat(row.querySelector('.deductions').value) || 0;
            totalNet += parseCurrency(row.querySelector('.net-pay').value);
        });

        document.getElementById('totalBasicPay').textContent = formatCurrency(totalBasic);
        document.getElementById('totalDeductions').textContent = formatCurrency(totalDeductions);
        document.getElementById('totalNetPay').textContent = formatCurrency(totalNet);
    }

    // Deductions modal
    document.querySelectorAll('.view-deductions').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.dataset.employeeId;
            document.getElementById('deductionEmployeeId').value = employeeId;
            
            // Reset form
            document.getElementById('deductionsForm').reset();
            
            deductionsModal.show();
        });
    });

    // Calculate total deductions
    document.querySelectorAll('.deduction-item').forEach(input => {
        input.addEventListener('input', calculateTotalDeduction);
    });

    function calculateTotalDeduction() {
        let total = 0;
        document.querySelectorAll('.deduction-item').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('totalDeduction').value = formatCurrency(total);
    }

    // Save deductions
    document.getElementById('saveDeductions').addEventListener('click', function() {
        const employeeId = document.getElementById('deductionEmployeeId').value;
        const total = parseCurrency(document.getElementById('totalDeduction').value);
        
        const row = document.querySelector(`.employee-select[value="${employeeId}"]`).closest('tr');
        row.querySelector('.deductions').value = total;
        
        calculatePay(row);
        deductionsModal.hide();
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Helper functions
    function formatCurrency(amount) {
        return '₱' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function parseCurrency(str) {
        return parseFloat(str.replace(/[₱,]/g, '')) || 0;
    }
});
</script>
