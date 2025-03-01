<?php
require_once '../config.php';
check_auth();

$page_title = 'Attendance Management';
$_SESSION['active_menu'] = 'attendance';

// Get selected date (default to today)
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get all active employees with their attendance for selected date
$query = "SELECT 
    e.id,
    e.employee_code,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    a.time_in,
    a.time_out,
    a.status,
    a.notes
FROM employees e
LEFT JOIN attendance a ON e.id = a.employee_id 
    AND DATE(a.date) = ?
WHERE e.status = 'active'
ORDER BY e.last_name, e.first_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$employees = $stmt->get_result();

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Attendance Record</h1>
        <input type="date" class="form-control" id="attendanceDate" 
               value="<?php echo $selected_date; ?>" 
               max="<?php echo date('Y-m-d'); ?>">
    </div>

    <div class="card">
        <div class="card-body">
            <form id="attendanceForm" method="POST" action="save_attendance.php">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($emp = $employees->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($emp['employee_code']); ?></small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($emp['position']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($emp['department']); ?></small>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm time-input" 
                                           name="attendance[<?php echo $emp['id']; ?>][time_in]" 
                                           value="<?php echo $emp['time_in'] ? date('H:i', strtotime($emp['time_in'])) : ''; ?>">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm time-input" 
                                           name="attendance[<?php echo $emp['id']; ?>][time_out]" 
                                           value="<?php echo $emp['time_out'] ? date('H:i', strtotime($emp['time_out'])) : ''; ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" 
                                            name="attendance[<?php echo $emp['id']; ?>][status]">
                                        <option value="present" <?php echo ($emp['status'] ?? '') == 'present' ? 'selected' : ''; ?>>Present</option>
                                        <option value="absent" <?php echo ($emp['status'] ?? '') == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                        <option value="late" <?php echo ($emp['status'] ?? '') == 'late' ? 'selected' : ''; ?>>Late</option>
                                        <option value="half_day" <?php echo ($emp['status'] ?? '') == 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="attendance[<?php echo $emp['id']; ?>][notes]" 
                                           value="<?php echo htmlspecialchars($emp['notes'] ?? ''); ?>"
                                           placeholder="Optional notes">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success save-row" 
                                            data-employee-id="<?php echo $emp['id']; ?>">
                                        <i class="bx bx-save"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger clear-row" 
                                            data-employee-id="<?php echo $emp['id']; ?>">
                                        <i class="bx bx-x"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save"></i> Save All Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Action Button -->
    <a href="attendance_add.php" class="btn btn-primary position-fixed" 
       style="bottom: 2rem; right: 2rem; border-radius: 50px; padding: 0.5rem 1rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
        <i class='bx bx-plus fs-5'></i>
        <span class="d-none d-md-inline">Add Attendance</span>
    </a>

    <script>
    // Initialize Bootstrap modals
    document.addEventListener('DOMContentLoaded', function() {
        var addAttendanceModal = new bootstrap.Modal(document.getElementById('addAttendanceModal'));
    });
    </script>

    <?php include 'footer.php'; ?>
</div>
