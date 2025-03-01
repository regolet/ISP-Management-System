<?php
require_once '../config.php';
check_auth();

$page_title = 'Leave Management';
$_SESSION['active_menu'] = 'leaves';
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Leave Management</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
            <i class="bx bx-plus"></i>
            <span>Apply for Leave</span>
        </button>
    </div>

    <!-- Leave Balances -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Leave Balances</h4>
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
                        <?php 
                        // Get leave balances
                        $balances_query = "SELECT lb.id, lb.sick_leave, lb.vacation_leave, lb.emergency_leave,
                                           e.id as employee_id, e.employee_code, e.first_name, e.last_name, e.department
                                           FROM leave_balances lb
                                           RIGHT JOIN employees e ON lb.employee_id = e.id
                                           WHERE e.status = 'active'
                                           ORDER BY e.last_name, e.first_name";
                        $balances = $conn->query($balances_query);
                        
                        while ($balance = $balances->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($balance['last_name'] . ', ' . $balance['first_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($balance['employee_code']); ?> - <?php echo htmlspecialchars($balance['department']); ?></small>
                            </td>
                            <td><?php echo number_format($balance['sick_leave'] ?? 0, 1); ?></td>
                            <td><?php echo number_format($balance['vacation_leave'] ?? 0, 1); ?></td>
                            <td><?php echo number_format($balance['emergency_leave'] ?? 0, 1); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="editBalance(<?php 
                                            echo htmlspecialchars(json_encode([
                                                'id' => $balance['id'] ?? '',
                                                'employee_id' => $balance['employee_id'],
                                                'sick_leave' => $balance['sick_leave'] ?? 0,
                                                'vacation_leave' => $balance['vacation_leave'] ?? 0,
                                                'emergency_leave' => $balance['emergency_leave'] ?? 0
                                            ])); 
                                        ?>)">
                                    <i class='bx bx-edit'></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Leave Applications -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Leave Applications</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Get leave applications
                        $leaves_query = "SELECT l.*, e.employee_code, e.first_name, e.last_name, e.department,
                                         u.username as approved_by_name
                                         FROM leaves l
                                         JOIN employees e ON l.employee_id = e.id
                                         LEFT JOIN users u ON l.approved_by = u.id
                                         ORDER BY l.created_at DESC";
                        $leaves = $conn->query($leaves_query);
                        
                        while ($leave = $leaves->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($leave['last_name'] . ', ' . $leave['first_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($leave['employee_code']); ?> - <?php echo htmlspecialchars($leave['department']); ?></small>
                            </td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $leave['leave_type'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                            <td><?php echo $leave['days']; ?></td>
                            <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $leave['status'] == 'approved' ? 'success' : 
                                        ($leave['status'] == 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($leave['status']); ?>
                                </span>
                                <?php if ($leave['approved_by_name'] && $leave['status'] != 'pending'): ?>
                                    <div class="small text-muted">
                                        by <?php echo htmlspecialchars($leave['approved_by_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($leave['status'] == 'pending' && $_SESSION['role'] == 'admin'): ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm" 
                                            onclick="updateLeaveStatus(<?php echo $leave['id']; ?>, 'approved')">
                                        <i class='bx bx-check'></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="updateLeaveStatus(<?php echo $leave['id']; ?>, 'rejected')">
                                        <i class='bx bx-x'></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="leaves_apply.php" id="leaveForm">
                <div class="modal-header">
                    <h5 class="modal-title">Apply for Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php 
                            $emp_query = "SELECT id, employee_code, first_name, last_name FROM employees WHERE status = 'active'";
                            $employees = $conn->query($emp_query);
                            while ($emp = $employees->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="">Select Leave Type</option>
                            <option value="sick_leave">Sick Leave</option>
                            <option value="vacation_leave">Vacation Leave</option>
                            <option value="emergency_leave">Emergency Leave</option>
                        </select>
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
                        <textarea class="form-control" name="reason" rows="2" required></textarea>
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
function updateLeaveStatus(leaveId, status) {
    if (confirm('Are you sure you want to ' + status + ' this leave application?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'leaves_apply.php';
        
        const leaveIdInput = document.createElement('input');
        leaveIdInput.type = 'hidden';
        leaveIdInput.name = 'leave_id';
        leaveIdInput.value = leaveId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        form.appendChild(leaveIdInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'footer.php'; ?>
