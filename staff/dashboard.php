<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// Make sure staff is linked to an employee
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Staff Dashboard";
$_SESSION['active_menu'] = 'staff_dashboard';

// Debug employee ID
error_log("Employee ID: " . $_SESSION['employee_id']);

// Fix employee details query - remove photo field
$stmt = $conn->prepare("
    SELECT e.*, u.username, u.email, u.last_login
    FROM employees e
    JOIN users u ON e.user_id = u.id
    WHERE e.id = ? AND e.status = 'active'
");

if (!$stmt) {
    error_log("Query preparation failed: " . $conn->error);
    die("Database error");
}

$stmt->bind_param("i", $_SESSION['employee_id']);
if (!$stmt->execute()) {
    error_log("Query execution failed: " . $stmt->error);
    die("Database error");
}

$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    error_log("No employee record found for ID: " . $_SESSION['employee_id']);
    $_SESSION['error'] = "Employee record not found. Please contact administrator.";
    header("Location: ../logout.php");
    exit();
}

// Get today's attendance
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->bind_param("is", $employee['id'], $today);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();

// Get latest payroll information
$latest_payroll = null;
$payroll_query = "SELECT * FROM payroll 
                 WHERE employee_id = ? 
                 AND status IN ('approved', 'paid')
                 ORDER BY pay_period_end DESC LIMIT 1";

try {
    $stmt = $conn->prepare($payroll_query);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['employee_id']);
        $stmt->execute();
        $latest_payroll = $stmt->get_result()->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Payroll query error: " . $e->getMessage());
    // Silently handle the error - payroll section will show "No payroll records found"
}

// Fix expenses query to use user_id instead of employee_id
$stmt = $conn->prepare("
    SELECT e.*, c.name as category_name 
    FROM expenses e
    LEFT JOIN expense_categories c ON e.category_id = c.id
    WHERE e.user_id = ? 
    ORDER BY e.created_at DESC LIMIT 5
");

if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']); // Use session user_id instead of employee id
    $stmt->execute();
    $recent_expenses = $stmt->get_result();
} else {
    error_log("Expenses query preparation failed: " . $conn->error);
    $recent_expenses = null;
}

// Add this to verify the expenses table structure
$expenses_check = $conn->query("SHOW CREATE TABLE expenses");
if (!$expenses_check) {
    // Create expenses table with correct structure
    $conn->query("
        CREATE TABLE IF NOT EXISTS expenses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            date DATE NOT NULL,
            category VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id)
        ) ENGINE=InnoDB
    ");
}

// Add this to check if attendance table exists
$result = $conn->query("DESCRIBE attendance");
if (!$result) {
    // Create attendance table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS attendance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            date DATE NOT NULL,
            time_in TIME,
            time_out TIME,
            status ENUM('present', 'late', 'absent') DEFAULT 'present',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id),
            UNIQUE KEY unique_attendance (employee_id, date)
        )
    ");
}

// Add this before the attendance cards section
// Get attendance summary for current month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days
    FROM attendance 
    WHERE employee_id = ? 
    AND date BETWEEN ? AND ?
");

if ($stmt) {
    $stmt->bind_param("iss", $_SESSION['employee_id'], $month_start, $month_end);
    $stmt->execute();
    $attendance_summary = $stmt->get_result()->fetch_assoc();
} else {
    $attendance_summary = [
        'present_days' => 0,
        'late_days' => 0,
        'absent_days' => 0
    ];
}

include '../header.php';
include 'staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid"></div>
        <!-- Welcome Section with Profile Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <!-- Replace the profile image section with just an icon -->
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class='bx bx-user-circle text-primary' style="font-size: 60px;"></i>
                        </div>
                        <div>
                            <h1 class="h3 mb-1">Welcome, <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>!</h1>
                            <p class="text-muted mb-0">
                                Employee ID: <?php echo htmlspecialchars($employee['employee_code']); ?> | 
                                Department: <?php echo htmlspecialchars($employee['department'] ?? 'Unassigned'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="row g-3 mb-4">
            <!-- Time Card -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm time-status-card">
                    <div class="card-body text-center p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                <i class='bx bx-time text-primary'></i>
                            </div>
                            <h6 class="card-title mb-0">Time Status</h6>
                        </div>
                        <?php if (!$attendance): ?>
                            <button class="btn btn-primary btn-sm w-100" onclick="timeIn()">
                                <i class='bx bx-log-in'></i> Time In
                            </button>
                        <?php elseif (!$attendance['time_out']): ?>
                            <button class="btn btn-warning btn-sm w-100" onclick="timeOut()">
                                <i class='bx bx-log-out'></i> Time Out
                            </button>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                                In: <?php echo date('h:i A', strtotime($attendance['time_in'])); ?>
                            </small>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class='bx bx-check-circle'></i> Complete
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm quick-actions-card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-info bg-opacity-10 p-2 me-2">
                                <i class='bx bx-link text-info'></i>
                            </div>
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="expenses/add.php" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center">
                                <i class='bx bx-receipt fs-4 me-2'></i>
                                <span>Add Expense</span>
                            </a>
                            <a href="payments/add.php" class="btn btn-success btn-lg d-flex align-items-center justify-content-center">
                                <i class='bx bx-money fs-4 me-2'></i>
                                <span>Record Payment</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Payroll -->
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                                <i class='bx bx-money text-success fs-3'></i>
                            </div>
                            <h5 class="card-title mb-0">Latest Payroll</h5>
                        </div>
                        <?php if ($latest_payroll): ?>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h3 class="mb-0">₱<?php echo number_format($latest_payroll['net_pay'], 2); ?></h3>
                                    <small class="text-muted d-block">Basic: ₱<?php echo number_format($latest_payroll['basic_pay'], 2); ?></small>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted d-block">Period: <?php echo date('M d', strtotime($latest_payroll['pay_period_start'])); ?> - <?php echo date('M d, Y', strtotime($latest_payroll['pay_period_end'])); ?></small>
                                    <span class="badge bg-<?php echo $latest_payroll['status'] === 'paid' ? 'success' : 'warning'; ?> mt-1">
                                        <?php echo ucfirst($latest_payroll['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No payroll records found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Expenses and Attendance Summary Section -->
        <div class="row">
            <!-- Recent Expenses -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Expenses</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_expenses && $recent_expenses->num_rows > 0): 
                                    while ($expense = $recent_expenses->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($expense['category_name'] ?? $expense['description']); ?></td>
                                        <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($expense['status']) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    default => 'warning'
                                                };
                                            ?>">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; 
                                else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No expenses found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="col-md-6">
                <!-- Attendance Stats Cards -->
                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 48px; height: 48px;">
                                        <i class='bx bx-check-circle fs-3'></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-success fw-bold">Present</h6>
                                        <h3 class="card-title mb-0"><?php echo $attendance_summary['present_days'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-0 h-100" style="background: rgba(255, 193, 7, 0.1);">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-warning text-white rounded-3" style="width: 48px; height: 48px;">
                                        <i class='bx bx-time fs-3'></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-warning fw-bold">Late</h6>
                                        <h3 class="card-title mb-0"><?php echo $attendance_summary['late_days'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-0 h-100" style="background: rgba(220, 53, 69, 0.1);">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-danger text-white rounded-3" style="width: 48px; height: 48px;">
                                        <i class='bx bx-x-circle fs-3'></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle mb-1 text-danger fw-bold">Absent</h6>
                                        <h3 class="card-title mb-0"><?php echo $attendance_summary['absent_days'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Card -->
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Monthly Calendar</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="attendance-calendar">
                            <table class="table table-bordered table-sm text-center mb-0">
                                <thead>
                                    <tr>
                                        <?php foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day): ?>
                                            <th class="py-2"><?php echo $day; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $firstDay = strtotime($month_start);
                                    $lastDay = strtotime($month_end);
                                    
                                    // Get attendance records for the month
                                    $stmt = $conn->prepare("SELECT date, status FROM attendance 
                                                            WHERE employee_id = ? 
                                                            AND date BETWEEN ? AND ?");
                                    $stmt->bind_param("iss", $employee['id'], $month_start, $month_end);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $attendance_dates = [];
                                    while ($row = $result->fetch_assoc()) {
                                        $attendance_dates[$row['date']] = $row['status'];
                                    }

                                    // Create calendar
                                    $current = $firstDay;
                                    while ($current <= $lastDay) {
                                        if (date('w', $current) === '0') echo '<tr>';
                                        
                                        $currentDate = date('Y-m-d', $current);
                                        $status = isset($attendance_dates[$currentDate]) ? $attendance_dates[$currentDate] : '';
                                        $statusClass = match($status) {
                                            'present' => 'bg-success',
                                            'late' => 'bg-warning',
                                            'absent' => 'bg-danger',
                                            default => ''
                                        };
                                        
                                        echo "<td class='$statusClass bg-opacity-25'>" . date('d', $current) . "</td>";
                                        
                                        if (date('w', $current) === '6') echo '</tr>';
                                        $current = strtotime('+1 day', $current);
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Attendance History Card -->
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Recent Attendance</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th class="pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT * FROM attendance 
                                        WHERE employee_id = ? 
                                        ORDER BY date DESC LIMIT 5
                                    ");
                                    $stmt->bind_param("i", $_SESSION['employee_id']);
                                    $stmt->execute();
                                    $recent_attendance = $stmt->get_result();
                                    while ($record = $recent_attendance->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                        <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($record['status']) {
                                                    'present' => 'success',
                                                    'late' => 'warning',
                                                    'absent' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?> bg-opacity-75">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Card -->
                <div class="card">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Recent Activities</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-list px-3">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT * FROM activity_logs 
                                WHERE user_id = ? 
                                ORDER BY created_at DESC LIMIT 5
                            ");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $activities = $stmt->get_result();
                            
                            while ($activity = $activities->fetch_assoc()):
                            ?>
                            <div class="activity-item d-flex align-items-center py-2 border-bottom">
                                <div class="flex-shrink-0">
                                    <small class="text-muted"><?php echo date('M d, g:i A', strtotime($activity['created_at'])); ?></small>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small><?php echo htmlspecialchars($activity['description']); ?></small>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ... rest of existing dashboard content ... -->
    </div>
</div>

<script>
function timeIn() {
    submitTimeAction('time_in');
}

function timeOut() {
    submitTimeAction('time_out');
}

function submitTimeAction(action) {
    const formData = new FormData();
    formData.append('action', action);

    fetch('attendance/time_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || `Failed to record ${action.replace('_', ' ')}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`Failed to record ${action.replace('_', ' ')}`);
    });
}
</script>

<!-- Updated UI Styles -->
<style>
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: calc(100vh - 60px);
    background: #f8f9fa;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
    transition: transform 0.2s;
    margin-bottom: 1.5rem;
}

.card:hover {
    transform: translateY(-5px);
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
}

.icon-shape {
    width: 48px;
    height: 48px;
    background-color: rgba(0,123,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.table td {
    vertical-align: middle;
}

.btn-group .btn {
    margin: 0 2px;
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 10px;
}

.activity-item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.attendance-calendar {
    background: white;
    border-radius: 4px;
}

.attendance-calendar .table {
    margin-bottom: 0;
}

.attendance-calendar .table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 8px 4px;
    border: 1px solid #dee2e6;
}

.attendance-calendar .table td {
    height: 32px;
    width: 32px;
    padding: 0;
    line-height: 32px;
    font-size: 0.85rem;
    font-weight: 500;
    border: 1px solid #dee2e6;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.activity-list {
    max-height: 200px;
    overflow-y: auto;
}

.activity-item {
    padding: 8px 0;
    font-size: 0.875rem;
}

.table-sm th, .table-sm td {
    padding: 0.5rem;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

.attendance-calendar .table td.bg-success {
    background-color: rgba(40, 167, 69, 0.2) !important;
}

.attendance-calendar .table td.bg-warning {
    background-color: rgba(255, 193, 7, 0.2) !important;
}

.attendance-calendar .table td.bg-danger {
    background-color: rgba(220, 53, 69, 0.2) !important;
}

.bg-success-light {
    background-color: rgba(40, 167, 69, 0.1) !important;
}

.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.bg-danger-light {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.text-success {
    color: #28a745 !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-danger {
    color: #dc3545 !important;
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .attendance-calendar .table td {
        height: 35px;
        width: 35px;
        font-size: 0.875rem;
    }
}

.time-status-card .card-body, 
.quick-actions-card .card-body {
    display: flex;
    flex-direction: column;
}

.time-status-card .btn,
.quick-actions-card .btn {
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
}

.time-status-card i,
.quick-actions-card i {
    font-size: 1.2rem;
}

.time-status-card .rounded-circle,
.quick-actions-card .rounded-circle {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .time-status-card,
    .quick-actions-card {
        margin-bottom: 0.75rem;
    }

    .time-status-card .card-body,
    .quick-actions-card .card-body {
        padding: 0.75rem;
    }

    .time-status-card .btn,
    .quick-actions-card .btn {
        padding: 0.3rem 0.5rem;
        font-size: 0.8125rem;
    }
}

/* Updated Quick Actions Button Styles */
.quick-actions-card .btn {
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
}

.quick-actions-card .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.quick-actions-card .btn i {
    font-size: 1.5rem;
}

.quick-actions-card .card-body {
    padding: 1.5rem;
}

.quick-actions-card .btn-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.quick-actions-card .btn-success {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
}

@media (max-width: 768px) {
    .quick-actions-card .btn {
        padding: 0.75rem;
        font-size: 1rem;
    }
    
    .quick-actions-card .btn i {
        font-size: 1.25rem;
    }
}
</style>

<?php include '../footer.php'; ?>
