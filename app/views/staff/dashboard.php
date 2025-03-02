<?php
$title = 'Staff Dashboard - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>Welcome, <?= htmlspecialchars($staff['first_name']) ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <?php if (empty($todayAttendance)): ?>
                <button type="button" class="btn btn-success" id="clockIn">
                    <i class="fa fa-sign-in-alt"></i> Clock In
                </button>
            <?php elseif (empty($todayAttendance[0]['time_out'])): ?>
                <button type="button" class="btn btn-warning" id="clockOut">
                    <i class="fa fa-sign-out-alt"></i> Clock Out
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled>
                    <i class="fa fa-check"></i> Shift Complete
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Status Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Today's Status</h5>
                <h3 class="card-text">
                    <?php
                    if (empty($todayAttendance)) {
                        echo 'Not Clocked In';
                    } elseif (empty($todayAttendance[0]['time_out'])) {
                        echo 'On Duty';
                    } else {
                        echo 'Shift Complete';
                    }
                    ?>
                </h3>
                <?php if (!empty($todayAttendance)): ?>
                    <p class="mb-0">
                        In: <?= date('h:i A', strtotime($todayAttendance[0]['time_in'])) ?>
                        <?php if (!empty($todayAttendance[0]['time_out'])): ?>
                            <br>Out: <?= date('h:i A', strtotime($todayAttendance[0]['time_out'])) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Leave Balance</h5>
                <h3 class="card-text">
                    <?php
                    $totalBalance = array_sum(array_column($leaveBalance, 'remaining_days'));
                    echo $totalBalance;
                    ?>
                </h3>
                <p class="mb-0">Days Available</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending Tasks</h5>
                <h3 class="card-text"><?= count($pendingTasks) ?></h3>
                <p class="mb-0">Tasks to Complete</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Recent Expenses</h5>
                <h3 class="card-text">
                    <?= formatCurrency(array_sum(array_column($recentExpenses, 'amount'))) ?>
                </h3>
                <p class="mb-0">Last 30 Days</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tasks List -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pending Tasks</h5>
                <span class="badge bg-primary"><?= count($pendingTasks) ?> Tasks</span>
            </div>
            <div class="card-body">
                <?php if (empty($pendingTasks)): ?>
                    <div class="alert alert-info">
                        No pending tasks.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($pendingTasks as $task): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($task['title']) ?></h6>
                                    <small class="text-<?= getDueDateClass($task['due_date']) ?>">
                                        Due: <?= date('M d, Y', strtotime($task['due_date'])) ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($task['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Project: <?= htmlspecialchars($task['project_name']) ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-success complete-task" 
                                                data-id="<?= $task['id'] ?>">
                                            <i class="fa fa-check"></i> Complete
                                        </button>
                                        <button type="button" class="btn btn-info view-task" 
                                                data-id="<?= $task['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Expenses</h5>
                <a href="/staff/expenses" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i> Add Expense
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentExpenses)): ?>
                    <div class="alert alert-info">
                        No recent expenses.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentExpenses as $expense): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($expense['date'])) ?></td>
                                        <td><?= htmlspecialchars($expense['category_name']) ?></td>
                                        <td><?= formatCurrency($expense['amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getExpenseStatusClass($expense['status']) ?>">
                                                <?= ucfirst($expense['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-md-4">
        <!-- Leave Balance -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Leave Balance</h5>
                <a href="/staff/leave" class="btn btn-sm btn-primary">
                    <i class="fa fa-calendar"></i> Apply Leave
                </a>
            </div>
            <div class="card-body">
                <?php foreach ($leaveBalance as $leave): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($leave['leave_type']) ?></h6>
                            <small class="text-muted">
                                Used: <?= $leave['used_days'] ?> of <?= $leave['total_days'] ?> days
                            </small>
                        </div>
                        <h5>
                            <span class="badge bg-<?= getLeaveBalanceClass($leave['remaining_days']) ?>">
                                <?= $leave['remaining_days'] ?> Days
                            </span>
                        </h5>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Monthly Attendance</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="taskDetails"></div>
            </div>
        </div>
    </div>
</div>

<?php
function getDueDateClass($date) {
    $dueDate = strtotime($date);
    $today = strtotime('today');
    $diff = $dueDate - $today;
    
    if ($diff < 0) return 'danger';
    if ($diff <= 86400 * 2) return 'warning';
    return 'success';
}

function getExpenseStatusClass($status) {
    return match ($status) {
        'approved' => 'success',
        'pending' => 'warning',
        'rejected' => 'danger',
        default => 'secondary'
    };
}

function getLeaveBalanceClass($days) {
    if ($days <= 0) return 'danger';
    if ($days <= 5) return 'warning';
    return 'success';
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Chart
    const attendanceData = <?= json_encode([
        'present' => 20,  // Replace with actual data
        'absent' => 2,
        'late' => 3,
        'leave' => 1
    ]) ?>;

    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Leave'],
            datasets: [{
                data: Object.values(attendanceData),
                backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#17a2b8']
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

    // Clock In/Out Handler
    ['clockIn', 'clockOut'].forEach(id => {
        const button = document.getElementById(id);
        if (button) {
            button.addEventListener('click', function() {
                this.disabled = true;
                
                fetch('/staff/attendance/clock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to record attendance');
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                    this.disabled = false;
                });
            });
        }
    });

    // Task Completion Handler
    document.querySelectorAll('.complete-task').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Mark this task as complete?')) {
                const taskId = this.dataset.id;
                
                fetch(`/staff/tasks/${taskId}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to complete task');
                    }
                });
            }
        });
    });

    // Task Details Handler
    const taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
    document.querySelectorAll('.view-task').forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.id;
            
            fetch(`/staff/tasks/${taskId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('taskDetails').innerHTML = `
                        <h6>${data.title}</h6>
                        <p>${data.description}</p>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Project:</strong> ${data.project_name}
                            </div>
                            <div class="col-md-6">
                                <strong>Due Date:</strong> ${new Date(data.due_date).toLocaleDateString()}
                            </div>
                        </div>
                    `;
                    taskModal.show();
                });
        });
    });
});
</script>
