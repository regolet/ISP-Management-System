<?php
$title = 'Leave Balances - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Leave Balances</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/leaves" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Leaves
            </a>
            <button type="button" class="btn btn-success" id="exportBalances">
                <i class="fa fa-download"></i> Export
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/leaves/balances" class="row g-3">
            <div class="col-md-4">
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

            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                        Active
                    </option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                        Inactive
                    </option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Leave Balances Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Sick Leave</th>
                        <th>Vacation Leave</th>
                        <th>Emergency Leave</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($balances)): ?>
                        <?php foreach ($balances as $balance): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($balance['first_name'] . ' ' . $balance['last_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($balance['department']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2"><?= number_format($balance['sick_leave'], 1) ?></div>
                                        <div class="progress flex-grow-1" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= min(100, ($balance['sick_leave'] / 15) * 100) ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2"><?= number_format($balance['vacation_leave'], 1) ?></div>
                                        <div class="progress flex-grow-1" style="height: 5px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= min(100, ($balance['vacation_leave'] / 15) * 100) ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2"><?= number_format($balance['emergency_leave'], 1) ?></div>
                                        <div class="progress flex-grow-1" style="height: 5px;">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?= min(100, ($balance['emergency_leave'] / 7) * 100) ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="editBalance(<?= htmlspecialchars(json_encode([
                                                'id' => $balance['id'],
                                                'employee_id' => $balance['employee_id'],
                                                'name' => $balance['first_name'] . ' ' . $balance['last_name'],
                                                'sick_leave' => $balance['sick_leave'],
                                                'vacation_leave' => $balance['vacation_leave'],
                                                'emergency_leave' => $balance['emergency_leave']
                                            ])) ?>)">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No leave balances found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Balance Modal -->
<div class="modal fade" id="editBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/leaves/update-balance" id="balanceForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id">
                <input type="hidden" name="employee_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Leave Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" id="employee_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sick Leave</label>
                        <input type="number" class="form-control" name="sick_leave" 
                               step="0.5" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Vacation Leave</label>
                        <input type="number" class="form-control" name="vacation_leave" 
                               step="0.5" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Emergency Leave</label>
                        <input type="number" class="form-control" name="emergency_leave" 
                               step="0.5" min="0" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit balance modal
    window.editBalance = function(balance) {
        const form = document.getElementById('balanceForm');
        form.querySelector('input[name="id"]').value = balance.id;
        form.querySelector('input[name="employee_id"]').value = balance.employee_id;
        document.getElementById('employee_name').value = balance.name;
        form.querySelector('input[name="sick_leave"]').value = balance.sick_leave;
        form.querySelector('input[name="vacation_leave"]').value = balance.vacation_leave;
        form.querySelector('input[name="emergency_leave"]').value = balance.emergency_leave;
        
        new bootstrap.Modal(document.getElementById('editBalanceModal')).show();
    };

    // Export balances
    document.getElementById('exportBalances').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = '/admin/leaves/balances?' + params.toString();
    });

    // Filter change handlers
    ['department', 'status'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
});
</script>
