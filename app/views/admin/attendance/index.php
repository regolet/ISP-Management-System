<?php
$title = 'Attendance Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Attendance Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/attendance/report" class="btn btn-info">
                <i class="fa fa-chart-bar"></i> View Report
            </a>
            <button type="button" class="btn btn-success" id="exportAttendance">
                <i class="fa fa-download"></i> Export
            </button>
        </div>
    </div>
</div>

<!-- Date Selection -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="attendance_date" class="form-label">Date</label>
                <input type="date" class="form-control" id="attendance_date" name="date" 
                       value="<?= $selected_date ?>" max="<?= date('Y-m-d') ?>">
            </div>
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
                    <option value="present" <?= ($filters['status'] ?? '') === 'present' ? 'selected' : '' ?>>
                        Present
                    </option>
                    <option value="absent" <?= ($filters['status'] ?? '') === 'absent' ? 'selected' : '' ?>>
                        Absent
                    </option>
                    <option value="late" <?= ($filters['status'] ?? '') === 'late' ? 'selected' : '' ?>>
                        Late
                    </option>
                    <option value="half_day" <?= ($filters['status'] ?? '') === 'half_day' ? 'selected' : '' ?>>
                        Half Day
                    </option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="card">
    <div class="card-body">
        <form id="attendanceForm" method="POST" action="/admin/attendance/save">
            <?= csrf_field() ?>
            <input type="hidden" name="date" value="<?= $selected_date ?>">
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th>Hours</th>
                            <th>Notes</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($record['employee_code']) ?> - 
                                            <?= htmlspecialchars($record['department']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control form-control-sm time-input" 
                                               name="attendance[<?= $record['employee_id'] ?>][time_in]" 
                                               value="<?= $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : '' ?>">
                                    </td>
                                    <td>
                                        <input type="time" class="form-control form-control-sm time-input" 
                                               name="attendance[<?= $record['employee_id'] ?>][time_out]" 
                                               value="<?= $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : '' ?>">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" 
                                                name="attendance[<?= $record['employee_id'] ?>][status]">
                                            <option value="present" <?= ($record['status'] ?? '') === 'present' ? 'selected' : '' ?>>
                                                Present
                                            </option>
                                            <option value="absent" <?= ($record['status'] ?? '') === 'absent' ? 'selected' : '' ?>>
                                                Absent
                                            </option>
                                            <option value="late" <?= ($record['status'] ?? '') === 'late' ? 'selected' : '' ?>>
                                                Late
                                            </option>
                                            <option value="half_day" <?= ($record['status'] ?? '') === 'half_day' ? 'selected' : '' ?>>
                                                Half Day
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($record['time_in'] && $record['time_out']): ?>
                                            <?php
                                            $hours = round((strtotime($record['time_out']) - strtotime($record['time_in'])) / 3600, 2);
                                            echo $hours;
                                            ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="attendance[<?= $record['employee_id'] ?>][notes]" 
                                               value="<?= htmlspecialchars($record['notes'] ?? '') ?>"
                                               placeholder="Optional notes">
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success save-row" 
                                                    data-employee-id="<?= $record['employee_id'] ?>" 
                                                    title="Save">
                                                <i class="fa fa-save"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger clear-row" 
                                                    data-employee-id="<?= $record['employee_id'] ?>" 
                                                    title="Clear">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fa fa-info-circle text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No records found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save All Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date change handler
    document.getElementById('attendance_date').addEventListener('change', function() {
        window.location.href = '/admin/attendance?date=' + this.value;
    });

    // Department filter
    document.getElementById('department').addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('department', this.value);
        window.location.href = '/admin/attendance?' + params.toString();
    });

    // Status filter
    document.getElementById('status').addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('status', this.value);
        window.location.href = '/admin/attendance?' + params.toString();
    });

    // Save individual row
    document.querySelectorAll('.save-row').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.dataset.employeeId;
            const formData = new FormData();
            formData.append('employee_id', employeeId);
            formData.append('date', document.querySelector('input[name="date"]').value);
            
            const row = this.closest('tr');
            formData.append('time_in', row.querySelector(`[name="attendance[${employeeId}][time_in]"]`).value);
            formData.append('time_out', row.querySelector(`[name="attendance[${employeeId}][time_out]"]`).value);
            formData.append('status', row.querySelector(`[name="attendance[${employeeId}][status]"]`).value);
            formData.append('notes', row.querySelector(`[name="attendance[${employeeId}][notes]"]`).value);

            fetch('/admin/attendance/save-row', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success indicator
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-success');
                    setTimeout(() => {
                        button.classList.remove('btn-outline-success');
                        button.classList.add('btn-success');
                    }, 1000);
                } else {
                    alert(data.error || 'Failed to save attendance');
                }
            });
        });
    });

    // Clear row
    document.querySelectorAll('.clear-row').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear this attendance record?')) {
                const employeeId = this.dataset.employeeId;
                const row = this.closest('tr');
                row.querySelector(`[name="attendance[${employeeId}][time_in]"]`).value = '';
                row.querySelector(`[name="attendance[${employeeId}][time_out]"]`).value = '';
                row.querySelector(`[name="attendance[${employeeId}][status]"]`).value = 'absent';
                row.querySelector(`[name="attendance[${employeeId}][notes]"]`).value = '';
            }
        });
    });

    // Export attendance
    document.getElementById('exportAttendance').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        window.location.href = '/admin/attendance/export?' + params.toString();
    });
});
</script>
