<?php
require_once '../config.php';
check_auth();

$page_title = 'Employee Details';
$_SESSION['active_menu'] = 'employees';

$employee_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$employee_id) {
    $_SESSION['error'] = "Invalid employee ID";
    header("Location: employees.php");
    exit();
}

// Get employee details
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    $_SESSION['error'] = "Employee not found";
    header("Location: employees.php");
    exit();
}

// Handle form submission for edit mode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Check if basic salary has changed
        $check_salary = $conn->prepare("SELECT basic_salary FROM employees WHERE id = ?");
        $check_salary->bind_param("i", $employee_id);
        $check_salary->execute();
        $old_salary = $check_salary->get_result()->fetch_assoc();

        // Validate hire_date
        $hire_date = date('Y-m-d', strtotime($_POST['hire_date']));
        if (!$hire_date || $hire_date === '1970-01-01') {
            throw new Exception("Invalid hire date format");
        }

        $query = "
        UPDATE employees SET 
            first_name = ?,
            last_name = ?,
            position = ?,
            department = ?,
            hire_date = ?,
            email = ?,
            phone = ?,
            address = ?,
            basic_salary = ?,
            allowance = ?,
            daily_rate = ?,
            sss_no = ?,
            philhealth_no = ?,
            pagibig_no = ?,
            tin_no = ?,
            bank_name = ?,
            bank_account_no = ?
        WHERE id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssdddssssssi",
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['position'],
            $_POST['department'],
            $hire_date,
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['basic_salary'],
            $_POST['allowance'],
            $_POST['daily_rate'],
            $_POST['sss_no'],
            $_POST['philhealth_no'],
            $_POST['pagibig_no'],
            $_POST['tin_no'],
            $_POST['bank_name'],
            $_POST['bank_account_no'],
            $employee_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error updating employee information: " . $conn->error);
        }

        // If salary changed, record in history
        if ($old_salary['basic_salary'] != $_POST['basic_salary']) {
            $history_query = "
                INSERT INTO salary_history 
                (employee_id, basic_salary, daily_rate, effective_date, days_in_month, created_by) 
                VALUES (?, ?, ?, CURRENT_DATE(), ?, ?)
            ";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->bind_param("iddii",
                $employee_id,
                $_POST['basic_salary'],
                $_POST['daily_rate'],
                $_POST['days_in_month'],
                $_SESSION['user_id']
            );
            $history_stmt->execute();
        }

        // Log the activity
        log_activity($_SESSION['user_id'], 'update_employee', "Updated employee information: {$_POST['first_name']} {$_POST['last_name']}");
        
        $conn->commit();
        $_SESSION['success'] = "Employee information updated successfully.";
        header("Location: employee_view.php?id=" . $employee_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Check if user account exists for this employee
$user_check = $conn->prepare("
    SELECT u.* FROM users u 
    WHERE u.id = ?
");
$user_check->bind_param("i", $employee['user_id']);
$user_check->execute();
$existing_user = $user_check->get_result()->fetch_assoc();

// Get employee's leave balance
$leave_query = "
    SELECT * FROM leave_balances 
    WHERE employee_id = ? AND year = YEAR(CURRENT_DATE())
";
$leave_stmt = $conn->prepare($leave_query);
$leave_stmt->bind_param("i", $employee_id);
$leave_stmt->execute();
$leave_balance = $leave_stmt->get_result()->fetch_assoc();

// Get recent attendance records
$attendance_query = "
    SELECT * FROM attendance 
    WHERE employee_id = ? 
    ORDER BY date DESC LIMIT 5
";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("i", $employee_id);
$attendance_stmt->execute();
$recent_attendance = $attendance_stmt->get_result();

// Get active deductions
$deductions_query = "
    SELECT ed.*, dt.name as deduction_name 
    FROM employee_deductions ed
    JOIN deduction_types dt ON ed.deduction_type_id = dt.id
    WHERE ed.employee_id = ? AND ed.status = 'active'
";
$deductions_stmt = $conn->prepare($deductions_query);
$deductions_stmt->bind_param("i", $employee_id);
$deductions_stmt->execute();
$active_deductions = $deductions_stmt->get_result();

// Check if we're in edit mode
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>
        
        <!-- Header with Employee Summary -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class='bx bx-user-circle text-primary' style="font-size: 48px;"></i>
                        </div>
                        <div>
                            <h1 class="h3 mb-1"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
                            <p class="text-muted mb-0">
                                <?php echo htmlspecialchars($employee['position']); ?> | 
                                <?php echo htmlspecialchars($employee['department']); ?> | 
                                ID: <?php echo htmlspecialchars($employee['employee_code']); ?>
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="employees.php" class="btn btn-outline-secondary me-2">
                            <i class='bx bx-arrow-back'></i> Back
                        </a>
                        <?php if (!$edit_mode): ?>
                            <a href="?id=<?php echo $employee_id; ?>&edit=true" class="btn btn-primary">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Basic Salary</div>
                            <div class="h5 mb-0">₱<?php echo number_format($employee['basic_salary'], 2); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Daily Rate</div>
                            <div class="h5 mb-0">₱<?php echo number_format($employee['daily_rate'], 2); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Status</div>
                            <div class="h5 mb-0">
                                <span class="badge bg-<?php echo $employee['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($employee['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Hire Date</div>
                            <div class="h5 mb-0"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Information -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($edit_mode): ?>
                            <form method="POST" class="needs-validation" novalidate>
                        <?php endif; ?>
                        
                        <!-- Tabs for different sections -->
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#personal">
                                    <i class='bx bx-user'></i> Personal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#contact">
                                    <i class='bx bx-envelope'></i> Contact
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#employment">
                                    <i class='bx bx-briefcase'></i> Employment
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#government">
                                    <i class='bx bx-id-card'></i> Government IDs
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Personal Information Tab -->
                            <div class="tab-pane fade show active" id="personal">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="first_name" 
                                                   value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['first_name']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="last_name" 
                                                   value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['last_name']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information Tab -->
                            <div class="tab-pane fade" id="contact">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Email <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['email']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($employee['phone']); ?>">
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['phone'] ?: 'Not provided'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <?php if ($edit_mode): ?>
                                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($employee['address'] ?: 'Not provided')); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Information Tab -->
                            <div class="tab-pane fade" id="employment">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Position <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="position" 
                                                   value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['position']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Department <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <select class="form-select" name="department" required>
                                                <option value="">Select Department</option>
                                                <?php
                                                $departments = ['IT', 'HR', 'Finance', 'Operations', 'Sales'];
                                                foreach ($departments as $dept) {
                                                    $selected = $employee['department'] === $dept ? 'selected' : '';
                                                    echo "<option value=\"$dept\" $selected>$dept</option>";
                                                }
                                                ?>
                                            </select>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['department']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Hire Date <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <input type="date" class="form-control" name="hire_date" 
                                                   value="<?php echo date('Y-m-d', strtotime($employee['hire_date'])); ?>" required>
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Basic Salary <?php echo $edit_mode ? '<span class="text-danger">*</span>' : ''; ?></label>
                                        <?php if ($edit_mode): ?>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" step="0.01" class="form-control" name="basic_salary" 
                                                       id="basicSalary"
                                                       value="<?php echo $employee['basic_salary']; ?>" required
                                                       onchange="calculateDailyRate()">
                                            </div>
                                        <?php else: ?>
                                            <p class="form-control-plaintext">₱<?php echo number_format($employee['basic_salary'], 2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Daily Rate (Auto-calculated)</label>
                                        <?php if ($edit_mode): ?>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" step="0.01" class="form-control" name="daily_rate" 
                                                       id="dailyRate"
                                                       value="<?php echo $employee['daily_rate']; ?>" readonly>
                                                <input type="hidden" name="days_in_month" id="daysInMonth">
                                            </div>
                                            <div class="form-text">Automatically calculated based on basic salary and days in current month</div>
                                        <?php else: ?>
                                            <p class="form-control-plaintext">₱<?php echo number_format($employee['daily_rate'], 2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!$edit_mode): ?>
                                <!-- Salary History Section -->
                                <div class="mt-4">
                                    <h5>Salary History</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Effective Date</th>
                                                    <th>Basic Salary</th>
                                                    <th>Daily Rate</th>
                                                    <th>Days in Month</th>
                                                    <th>Updated By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $history_query = "
                                                    SELECT sh.*, u.username as updated_by
                                                    FROM salary_history sh
                                                    LEFT JOIN users u ON sh.created_by = u.id
                                                    WHERE sh.employee_id = ?
                                                    ORDER BY sh.effective_date DESC
                                                    LIMIT 10
                                                ";
                                                $stmt = $conn->prepare($history_query);
                                                $stmt->bind_param("i", $employee_id);
                                                $stmt->execute();
                                                $history = $stmt->get_result();
                                                while ($row = $history->fetch_assoc()):
                                                ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($row['effective_date'])); ?></td>
                                                    <td>₱<?php echo number_format($row['basic_salary'], 2); ?></td>
                                                    <td>₱<?php echo number_format($row['daily_rate'], 2); ?></td>
                                                    <td><?php echo $row['days_in_month']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['updated_by']); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Government IDs Tab -->
                            <div class="tab-pane fade" id="government">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SSS Number</label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="sss_no" 
                                                   value="<?php echo htmlspecialchars($employee['sss_no']); ?>">
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['sss_no'] ?: 'Not provided'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">PhilHealth Number</label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="philhealth_no" 
                                                   value="<?php echo htmlspecialchars($employee['philhealth_no']); ?>">
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['philhealth_no'] ?: 'Not provided'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pag-IBIG Number</label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="pagibig_no" 
                                                   value="<?php echo htmlspecialchars($employee['pagibig_no']); ?>">
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['pagibig_no'] ?: 'Not provided'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TIN Number</label>
                                        <?php if ($edit_mode): ?>
                                            <input type="text" class="form-control" name="tin_no" 
                                                   value="<?php echo htmlspecialchars($employee['tin_no']); ?>">
                                        <?php else: ?>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($employee['tin_no'] ?: 'Not provided'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($edit_mode): ?>
                            <div class="mt-4 text-end">
                                <a href="?id=<?php echo $employee_id; ?>" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Side Information -->
            <div class="col-lg-4">
                <!-- User Account Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">User Account</h5>
                        <?php if (!$existing_user): ?>
                            <form action="employee_createuseraccount.php" method="POST" style="display: inline;">
                                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class='bx bx-user-plus'></i> Create Account
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($existing_user): ?>
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Username</div>
                                <div class="h6"><?php echo htmlspecialchars($existing_user['username']); ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Status</div>
                                <div class="h6">
                                    <span class="badge bg-<?php echo $existing_user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($existing_user['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="small text-muted mb-1">Last Login</div>
                                <div class="h6">
                                    <?php echo $existing_user['last_login'] 
                                        ? date('M d, Y g:i A', strtotime($existing_user['last_login'])) 
                                        : 'Never'; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class='bx bx-user-x' style="font-size: 48px;"></i>
                                <p class="mb-0">No user account associated</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Leave Balance Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Leave Balance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-0">
                            <div class="col-4 text-center border-end">
                                <div class="p-3">
                                    <div class="h3 mb-0"><?php echo number_format($leave_balance['sick_leave'] ?? 0, 1); ?></div>
                                    <div class="small text-muted">Sick Leave</div>
                                </div>
                            </div>
                            <div class="col-4 text-center border-end">
                                <div class="p-3">
                                    <div class="h3 mb-0"><?php echo number_format($leave_balance['vacation_leave'] ?? 0, 1); ?></div>
                                    <div class="small text-muted">Vacation</div>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="p-3">
                                    <div class="h3 mb-0"><?php echo number_format($leave_balance['emergency_leave'] ?? 0, 1); ?></div>
                                    <div class="small text-muted">Emergency</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Attendance</h5>
                        <a href="attendance.php?employee_id=<?php echo $employee_id; ?>" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php while ($attendance = $recent_attendance->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-muted">
                                                <?php echo date('M d, Y', strtotime($attendance['date'])); ?>
                                            </div>
                                            <div>
                                                <?php if ($attendance['time_in']): ?>
                                                    <span class="me-2">In: <?php echo date('h:i A', strtotime($attendance['time_in'])); ?></span>
                                                <?php endif; ?>
                                                <?php if ($attendance['time_out']): ?>
                                                    <span>Out: <?php echo date('h:i A', strtotime($attendance['time_out'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-<?php 
                                            echo $attendance['status'] == 'present' ? 'success' : 
                                                ($attendance['status'] == 'late' ? 'warning' : 
                                                ($attendance['status'] == 'absent' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo ucfirst($attendance['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <?php if ($recent_attendance->num_rows === 0): ?>
                                <div class="text-center text-muted p-3">
                                    <p class="mb-0">No recent attendance records</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: calc(100vh - 60px);
    background: #f4f6f9;
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1rem;
}

/* Updated nav-tabs styles */
.nav-tabs .nav-link {
    color: #000000 !important; /* Force black color */
    opacity: 0.7; /* Slightly dimmed when not active */
    transition: all 0.2s ease;
}

.nav-tabs .nav-link.active {
    color: #000000 !important; /* Keep black when active */
    opacity: 1; /* Full opacity when active */
    font-weight: 500;
    border-bottom: 2px solid #0d6efd; /* Add blue bottom border for active tab */
}

.nav-tabs .nav-link:hover {
    opacity: 1;
    border-color: transparent;
}

.nav-tabs .nav-link i {
    margin-right: 5px;
}

.form-control-plaintext {
    margin-bottom: 0;
    line-height: 1.5;
    padding-top: calc(0.375rem + 1px);
    padding-bottom: calc(0.375rem + 1px);
}

.list-group-item {
    padding: 1rem;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.rounded-circle {
    border-radius: 50% !important;
}

.text-primary {
    color: #0d6efd !important;
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

/* Additional styles for better tab appearance */
.nav-tabs {
    border-bottom: 1px solid #dee2e6;
}

.nav-tabs .nav-item {
    margin-bottom: -1px;
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.5rem 1rem;
}

.nav-tabs .nav-link:hover,
.nav-tabs .nav-link:focus {
    border-color: transparent;
}
</style>

<script>
// Function to calculate daily rate
function calculateDailyRate() {
    const basicSalary = document.getElementById('basicSalary');
    const dailyRate = document.getElementById('dailyRate');
    const daysInMonthInput = document.getElementById('daysInMonth');
    
    if (basicSalary && dailyRate) {
        const salary = parseFloat(basicSalary.value) || 0;
        const today = new Date();
        const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
        
        // Store days in month for the database
        if (daysInMonthInput) {
            daysInMonthInput.value = daysInMonth;
        }
        
        // Calculate daily rate
        const rate = salary / daysInMonth;
        
        // Update daily rate field with 2 decimal places
        dailyRate.value = rate.toFixed(2);
    }
}

// Calculate initial daily rate when page loads
document.addEventListener('DOMContentLoaded', function() {
    calculateDailyRate();
});
</script>

<?php include 'footer.php'; ?>
