<?php
$title = 'Leave Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Leave Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/leaves/balances" class="btn btn-info">
                <i class="fa fa-calculator"></i> Leave Balances
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                <i class="fa fa-plus"></i> Apply Leave
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/leaves" class="row g-3">
            <div class="col-md-3">
                <label for="employee" class="form-label">Employee</label>
                <select class="form-select" id="employee" name="employee_id">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" 
                                <?= ($filters['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
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

            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                        Pending
                    </option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>
                        Approved
                    </option>
                    <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>
                        Rejected
                    </option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="date_range" class="form-label">Date Range</label>
                <select class="form-select" id="date_range" name="date_range">
                    <option value="">All Time</option>
                    <option value="today" <?= ($filters['date_range'] ?? '') === 'today' ? 'selected' : '' ?>>
                        Today
                    </option>
                    <option value="week" <?= ($filters['date_range'] ?? '') === 'week' ? 'selected' : '' ?>>
                        This Week
                    </option>
                    <option value="month" <?= ($filters['date_range'] ?? '') === 'month' ? 'selected' : '' ?>>
                        This Month
                    </option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Leave Applications -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Period</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($leaves)): ?>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($leave['department']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?= ucwords(str_replace('_', ' ', $leave['leave_type'])) ?>
                                </td>
                                <td>
                                    <div><?= date('M d, Y', strtotime($leave['start_date'])) ?></div>
                                    <small class="text-muted">
                                        to <?= date('M d, Y', strtotime($leave['end_date'])) ?>
                                    </small>
                                </td>
                                <td><?= $leave['days'] ?></td>
                                <td><?= htmlspecialchars($leave['reason']) ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($leave['status']) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'pending' => 'warning',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($leave['status']) ?>
                                    </span>
                                    <?php if ($leave['approved_by_name']): ?>
                                        <div class="small text-muted">
                                            by <?= htmlspecialchars($leave['approved_by_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($leave['status'] === 'pending'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success" 
                                                    onclick="updateLeaveStatus(<?= $leave['id'] ?>, 'approved')" 
                                                    title="Approve">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="updateLeaveStatus(<?= $leave['id'] ?>, 'rejected')" 
                                                    title="Reject">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No leave applications found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/leaves/apply" id="leaveForm">
                <?= csrf_field() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">Apply for Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" 
                                        data-balances='<?= json_encode($emp['leave_balances']) ?>'>
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="">Select Leave Type</option>
                            <?php foreach ($leave_types as $type => $name): ?>
                                <option value="<?= $type ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="leaveBalance"></small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Leave balance display
    const employeeSelect = document.querySelector('select[name="employee_id"]');
    const leaveTypeSelect = document.querySelector('select[name="leave_type"]');
    const leaveBalance = document.getElementById('leaveBalance');

    function updateLeaveBalance() {
        const employee = employeeSelect.options[employeeSelect.selectedIndex];
        const leaveType = leaveTypeSelect.value;
        
        if (employee && leaveType && employee.dataset.balances) {
            const balances = JSON.parse(employee.dataset.balances);
            leaveBalance.textContent = `Available Balance: ${balances[leaveType] || 0} days`;
        } else {
            leaveBalance.textContent = '';
        }
    }

    employeeSelect.addEventListener('change', updateLeaveBalance);
    leaveTypeSelect.addEventListener('change', updateLeaveBalance);

    // Date validation
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');

    function validateDates() {
        if (startDate.value && endDate.value) {
            if (new Date(endDate.value) < new Date(startDate.value)) {
                endDate.setCustomValidity('End date must be after start date');
            } else {
                endDate.setCustomValidity('');
            }
        }
    }

    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);

    // Leave status update
    window.updateLeaveStatus = function(id, status) {
        if (confirm(`Are you sure you want to ${status} this leave application?`)) {
            fetch(`/admin/leaves/${id}/status`, {
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
                    alert(data.error || `Failed to ${status} leave application`);
                }
            });
        }
    };

    // Filter change handlers
    ['employee', 'department', 'status', 'date_range'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
});
</script>
