<?php
$title = 'Deduction Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Deduction Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeductionModal">
            <i class="fa fa-plus"></i> Add Deduction Type
        </button>
    </div>
</div>

<!-- Deduction Types -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Deduction Types</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Calculation</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($types as $type): ?>
                        <tr>
                            <td>
                                <div><?= htmlspecialchars($type['name']) ?></div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($type['description']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $type['type'] === 'government' ? 'info' : 'warning' ?>">
                                    <?= ucfirst($type['type']) ?>
                                </span>
                            </td>
                            <td><?= ucfirst($type['calculation_type']) ?></td>
                            <td>
                                <?php if ($type['calculation_type'] === 'percentage'): ?>
                                    <?= number_format($type['percentage_value'], 2) ?>%
                                <?php else: ?>
                                    Fixed Amount
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $type['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $type['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-primary" 
                                            onclick="editDeductionType(<?= htmlspecialchars(json_encode($type)) ?>)">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="deleteDeductionType(<?= $type['id'] ?>)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Active Deductions -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Active Deductions</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                    data-bs-target="#addEmployeeDeductionModal">
                <i class="fa fa-plus"></i> Add Employee Deduction
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Deduction</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deductions as $deduction): ?>
                        <tr>
                            <td>
                                <div><?= htmlspecialchars($deduction['employee_name']) ?></div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($deduction['employee_code']) ?>
                                </small>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($deduction['deduction_name']) ?></div>
                                <span class="badge bg-<?= $deduction['type'] === 'government' ? 'info' : 'warning' ?>">
                                    <?= ucfirst($deduction['type']) ?>
                                </span>
                            </td>
                            <td><?= formatCurrency($deduction['amount']) ?></td>
                            <td><?= ucfirst($deduction['frequency']) ?></td>
                            <td>
                                <div>From: <?= date('M d, Y', strtotime($deduction['start_date'])) ?></div>
                                <?php if ($deduction['end_date']): ?>
                                    <small class="text-muted">
                                        To: <?= date('M d, Y', strtotime($deduction['end_date'])) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($deduction['status']) {
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?= ucfirst($deduction['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info" 
                                            onclick="viewHistory(<?= $deduction['id'] ?>)">
                                        <i class="fa fa-history"></i>
                                    </button>
                                    <?php if ($deduction['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-primary" 
                                                onclick="editDeduction(<?= htmlspecialchars(json_encode($deduction)) ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="cancelDeduction(<?= $deduction['id'] ?>)">
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
    </div>
</div>

<!-- Add/Edit Deduction Type Modal -->
<div class="modal fade" id="deductionTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deductionTypeForm" method="POST" action="/admin/deductions/types/save">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="deductionTypeId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Deduction Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select class="form-select" name="type" required>
                            <option value="government">Government</option>
                            <option value="loan">Loan</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Calculation Type *</label>
                        <select class="form-select" name="calculation_type" required>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="percentageValueField" style="display: none;">
                        <label class="form-label">Percentage Value *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="percentage_value" 
                                   step="0.01" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Employee Deduction Modal -->
<div class="modal fade" id="employeeDeductionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="employeeDeductionForm" method="POST" action="/admin/deductions/save">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="employeeDeductionId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Employee Deduction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee *</label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['id'] ?>">
                                    <?= htmlspecialchars($employee['name']) ?> 
                                    (<?= htmlspecialchars($employee['employee_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deduction Type *</label>
                        <select class="form-select" name="deduction_type_id" required>
                            <option value="">Select Deduction Type</option>
                            <?php foreach ($active_types as $type): ?>
                                <option value="<?= $type['id'] ?>">
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" name="amount" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Frequency *</label>
                        <select class="form-select" name="frequency" required>
                            <option value="onetime">One Time</option>
                            <option value="monthly">Monthly</option>
                            <option value="bimonthly">Bi-Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date *</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date">
                                <small class="text-muted">Optional for recurring deductions</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deductionTypeModal = new bootstrap.Modal(document.getElementById('deductionTypeModal'));
    const employeeDeductionModal = new bootstrap.Modal(document.getElementById('employeeDeductionModal'));
    
    // Toggle percentage value field based on calculation type
    document.querySelector('select[name="calculation_type"]').addEventListener('change', function() {
        document.getElementById('percentageValueField').style.display = 
            this.value === 'percentage' ? 'block' : 'none';
        document.querySelector('input[name="percentage_value"]').required = 
            this.value === 'percentage';
    });

    // Edit deduction type
    window.editDeductionType = function(type) {
        const form = document.getElementById('deductionTypeForm');
        form.querySelector('input[name="id"]').value = type.id;
        form.querySelector('input[name="name"]').value = type.name;
        form.querySelector('textarea[name="description"]').value = type.description;
        form.querySelector('select[name="type"]').value = type.type;
        form.querySelector('select[name="calculation_type"]').value = type.calculation_type;
        form.querySelector('input[name="percentage_value"]').value = type.percentage_value;
        
        document.getElementById('percentageValueField').style.display = 
            type.calculation_type === 'percentage' ? 'block' : 'none';
        
        document.querySelector('#deductionTypeModal .modal-title').textContent = 'Edit Deduction Type';
        deductionTypeModal.show();
    };

    // Delete deduction type
    window.deleteDeductionType = function(id) {
        if (confirm('Are you sure you want to delete this deduction type?')) {
            fetch(`/admin/deductions/types/${id}`, {
                method: 'DELETE',
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
                    alert(data.error || 'Failed to delete deduction type');
                }
            });
        }
    };

    // Edit employee deduction
    window.editDeduction = function(deduction) {
        const form = document.getElementById('employeeDeductionForm');
        form.querySelector('input[name="id"]').value = deduction.id;
        form.querySelector('select[name="employee_id"]').value = deduction.employee_id;
        form.querySelector('select[name="deduction_type_id"]').value = deduction.deduction_type_id;
        form.querySelector('input[name="amount"]').value = deduction.amount;
        form.querySelector('select[name="frequency"]').value = deduction.frequency;
        form.querySelector('input[name="start_date"]').value = deduction.start_date;
        form.querySelector('input[name="end_date"]').value = deduction.end_date || '';
        form.querySelector('textarea[name="remarks"]').value = deduction.remarks;
        
        document.querySelector('#employeeDeductionModal .modal-title').textContent = 'Edit Employee Deduction';
        employeeDeductionModal.show();
    };

    // Cancel deduction
    window.cancelDeduction = function(id) {
        if (confirm('Are you sure you want to cancel this deduction?')) {
            fetch(`/admin/deductions/${id}/cancel`, {
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
                    alert(data.error || 'Failed to cancel deduction');
                }
            });
        }
    };

    // View deduction history
    window.viewHistory = function(id) {
        window.location.href = `/admin/deductions/${id}/history`;
    };
});
</script>
