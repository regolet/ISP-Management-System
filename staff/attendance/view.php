<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

// Make sure staff is linked to an employee
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

$page_title = "My Attendance";
$_SESSION['active_menu'] = 'attendance';

// Get employee attendance for current month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = ? 
    AND date BETWEEN ? AND ?
    ORDER BY date DESC
");

$stmt->bind_param("iss", $_SESSION['employee_id'], $month_start, $month_end);
$stmt->execute();
$attendance_records = $stmt->get_result();

include '../../header.php';
include '../staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="h3 mb-0">My Attendance</h1>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $attendance_records->fetch_assoc()): ?>
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
                                    ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../footer.php'; ?>
