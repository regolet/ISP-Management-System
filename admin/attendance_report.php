<?php
require_once '../config.php';
check_auth();

$page_title = "Attendance Report";
$_SESSION['active_menu'] = 'attendance_report'; // Set active menu for navbar
include 'header.php';
include 'navbar.php';

// Default to current month
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date = date('Y-m-01', strtotime($month));
$end_date = date('Y-m-t', strtotime($month));

// Get attendance records for the month
$stmt = $conn->prepare("
    SELECT a.*, 
           e.employee_code, e.first_name, e.last_name, e.department,
           u.username as recorded_by
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN users u ON a.created_by = u.id
    WHERE a.date BETWEEN ? AND ?
    ORDER BY a.date DESC, e.last_name, e.first_name
");

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Get summary counts with explicit table reference for status
$summary = $conn->prepare("
    SELECT 
        COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.date END) as present_days,
        COUNT(DISTINCT CASE WHEN a.status = 'late' THEN a.date END) as late_days,
        COUNT(DISTINCT CASE WHEN a.status = 'absent' THEN a.date END) as absent_days,
        COUNT(DISTINCT CASE WHEN a.status = 'half_day' THEN a.date END) as half_days,
        a.employee_id,
        e.employee_code,
        e.first_name,
        e.last_name,
        e.department
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE a.date BETWEEN ? AND ?
    GROUP BY a.employee_id, e.employee_code, e.first_name, e.last_name, e.department
    ORDER BY e.last_name, e.first_name
");

$summary->bind_param("ss", $start_date, $end_date);
$summary->execute();
$summary_result = $summary->get_result();
?>

<body>
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php include 'alerts.php'; ?>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Attendance Report</h1>
                <div class="btn-toolbar gap-2">
                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                        <i class='bx bx-export'></i> Export to Excel
                    </button>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="month" class="form-label">Select Month</label>
                            <input type="month" class="form-control" id="month" name="month" 
                                   value="<?php echo $month; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="department" class="form-label">Department</label>
                            <?php
                            // Pre-build options array
                            $options = array('' => 'All Departments');
                            $departments = $conn->query("SELECT DISTINCT department FROM employees WHERE status = 'active' ORDER BY department");
                            while ($dept = $departments->fetch_assoc()) {
                                $options[$dept['department']] = $dept['department'];
                            }
                            
                            // Build select element
                            $select = '<select class="form-select" id="department" name="department">';
                            foreach ($options as $value => $label) {
                                $selected = (isset($_GET['department']) && $_GET['department'] == $value) ? ' selected' : '';
                                $select .= sprintf(
                                    '<option value="%s"%s>%s</option>',
                                    htmlspecialchars($value),
                                    $selected,
                                    htmlspecialchars($label)
                                );
                            }
                            $select .= '</select>';
                            
                            // Output the built select element
                            echo $select;
                            ?>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-filter-alt'></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Monthly Summary</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Present Days</th>
                                    <th>Late Days</th>
                                    <th>Absent Days</th>
                                    <th>Half Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $summary_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($row['employee_code']); ?> - 
                                            <?php echo htmlspecialchars($row['department']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo $row['present_days']; ?></td>
                                    <td><?php echo $row['late_days']; ?></td>
                                    <td><?php echo $row['absent_days']; ?></td>
                                    <td><?php echo $row['half_days']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detailed Records -->
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Detailed Records</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($record['last_name'] . ', ' . $record['first_name']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($record['employee_code']); ?> - 
                                            <?php echo htmlspecialchars($record['department']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $record['status'] == 'present' ? 'success' : 
                                                ($record['status'] == 'late' ? 'warning' : 
                                                ($record['status'] == 'absent' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                                    <td>
                                        <small>
                                            <?php echo htmlspecialchars($record['recorded_by']); ?><br>
                                            <?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
