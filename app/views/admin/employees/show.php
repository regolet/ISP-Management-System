<?php
$title = 'Employee Details - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Employee Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/employees" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Employees
            </a>
            <a href="/admin/employees/<?= $employee['id'] ?>/edit" class="btn btn-primary">
                <i class="fa fa-edit"></i> Edit Employee
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Employee Details -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Employee Information</h5>
                    <span class="badge bg-<?= $employee['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($employee['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Personal Information -->
                <h6 class="mb-3">Personal Information</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1">Name:</p>
                        <strong><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                        <div class="text-muted small"><?= htmlspecialchars($employee['employee_code']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">Contact:</p>
                        <div><?= htmlspecialchars($employee['email']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($employee['phone']) ?></div>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="mb-1">Address:</p>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($employee['address'])) ?>
                    </div>
                </div>

                <!-- Employment Details -->
                <h6 class="mb-3">Employment Details</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1">Position:</p>
                        <strong><?= htmlspecialchars($employee['position']) ?></strong>
                        <div class="text-muted small"><?= htmlspecialchars($employee['department']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">Hire Date:</p>
                        <div><?= date('F d, Y', strtotime($employee['hire_date'])) ?></div>
                        <div class="text-muted small">
                            <?= formatTimeAgo($employee['hire_date']) ?>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="mb-1">Daily Rate:</p>
                    <strong><?= formatCurrency($employee['daily_rate']) ?></strong>
                </div>

                <!-- Government IDs -->
                <h6 class="mb-3">Government IDs</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1">SSS Number:</p>
                        <div><?= htmlspecialchars($employee['sss_number']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">PhilHealth Number:</p>
                        <div><?= htmlspecialchars($employee['philhealth_number']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">Pag-IBIG Number:</p>
                        <div><?= htmlspecialchars($employee['pagibig_number']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">TIN Number:</p>
                        <div><?= htmlspecialchars($employee['tin_number']) ?></div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <h6 class="mb-3">Emergency Contact</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1">Contact Name:</p>
                        <div><?= htmlspecialchars($employee['emergency_contact_name']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">Contact Phone:</p>
                        <div><?= htmlspecialchars($employee['emergency_contact_phone']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Actions</h5>
                <div class="d-grid gap-2">
                    <?php if (!$employee['user_id']): ?>
                        <button type="button" class="btn btn-success" onclick="createUserAccount()">
                            <i class="fa fa-user-plus"></i> Create User Account
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($employee['status'] === 'active'): ?>
                        <button type="button" class="btn btn-danger" onclick="deactivateEmployee()">
                            <i class="fa fa-user-times"></i> Deactivate Employee
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-success" onclick="activateEmployee()">
                            <i class="fa fa-user-check"></i> Activate Employee
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Present Days</div>
                            <h3 class="mb-0"><?= $stats['present_days'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Late Days</div>
                            <h3 class="mb-0"><?= $stats['late_days'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Leave Balance</div>
                            <h3 class="mb-0"><?= $stats['leave_balance'] ?? 0 ?></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Overtime Hours</div>
                            <h3 class="mb-0"><?= $stats['overtime_hours'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($activities)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($activity['type']) ?></h6>
                                    <small class="text-muted">
                                        <?= formatTimeAgo($activity['created_at']) ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        No recent activity
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function createUserAccount() {
    if (confirm('Are you sure you want to create a user account for this employee?')) {
        fetch('/admin/employees/<?= $employee['id'] ?>/create-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Account created successfully!\n\nUsername: ${data.username}\nPassword: ${data.password}\n\nPlease save these credentials.`);
                location.reload();
            } else {
                alert(data.error || 'Failed to create user account');
            }
        });
    }
}

function deactivateEmployee() {
    updateEmployeeStatus('inactive', 'deactivate');
}

function activateEmployee() {
    updateEmployeeStatus('active', 'activate');
}

function updateEmployeeStatus(status, action) {
    if (confirm(`Are you sure you want to ${action} this employee?`)) {
        fetch('/admin/employees/<?= $employee['id'] ?>/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || `Failed to ${action} employee`);
            }
        });
    }
}
</script>
