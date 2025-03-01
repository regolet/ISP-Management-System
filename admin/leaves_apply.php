<?php
require_once 'config.php';
check_login();

try {
    // Process leave application
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->begin_transaction();

        // Handle leave status update
        if (isset($_POST['leave_id']) && isset($_POST['status'])) {
            $leave_id = filter_input(INPUT_POST, 'leave_id', FILTER_SANITIZE_NUMBER_INT);
            $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

            if (!in_array($status, ['approved', 'rejected'])) {
                throw new Exception("Invalid status");
            }

            // Get leave details before updating
            $leave_query = "
                SELECT l.*, e.first_name, e.last_name, lt.name as leave_type_name
                FROM leaves l
                JOIN employees e ON l.employee_id = e.id
                JOIN leave_types lt ON l.leave_type_id = lt.id
                WHERE l.id = ?
            ";
            $stmt = $conn->prepare($leave_query);
            $stmt->bind_param("i", $leave_id);
            $stmt->execute();
            $leave = $stmt->get_result()->fetch_assoc();

            if (!$leave) {
                throw new Exception("Leave application not found");
            }

            if ($leave['status'] !== 'pending') {
                throw new Exception("This leave application has already been processed");
            }

            // Update leave status
            $update_query = "
                UPDATE leaves 
                SET status = ?, approved_by = ?, approved_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sii", $status, $_SESSION['user_id'], $leave_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating leave status: " . $conn->error);
            }

            // If approved, deduct from leave balance
            if ($status === 'approved') {
                $leave_type_column = strtolower(str_replace(' ', '_', $leave['leave_type_name']));
                
                $update_balance = "
                    UPDATE leave_balances 
                    SET $leave_type_column = $leave_type_column - ?
                    WHERE employee_id = ? AND year = YEAR(CURRENT_DATE())
                ";
                $stmt = $conn->prepare($update_balance);
                $stmt->bind_param("di", $leave['days'], $leave['employee_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating leave balance: " . $conn->error);
                }
            }

            // Log the activity
            log_activity(
                $_SESSION['user_id'],
                'update_leave_status',
                ucfirst($status) . " leave application for {$leave['first_name']} {$leave['last_name']}"
            );

            $conn->commit();
            $_SESSION['success'] = "Leave application " . ucfirst($status) . " successfully";
            header("Location: leaves.php");
            exit;
        }
        // Handle new leave application
        else {
            $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
            $leave_type = filter_var($_POST['leave_type'], FILTER_SANITIZE_STRING);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $reason = trim($_POST['reason']);

            // Validate inputs
            if (!$employee_id || !$leave_type || !$start_date || !$end_date || !$reason) {
                throw new Exception("All fields are required");
            }

            // Validate dates
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $today = new DateTime();

            if ($start < $today) {
                throw new Exception("Start date cannot be in the past");
            }

            if ($end < $start) {
                throw new Exception("End date must be after start date");
            }

            // Calculate business days (excluding weekends)
            $days = 0;
            $current = clone $start;
            while ($current <= $end) {
                if ($current->format('N') < 6) { // 1 (Mon) through 5 (Fri)
                    $days++;
                }
                $current->modify('+1 day');
            }

            if ($days <= 0) {
                throw new Exception("Selected dates contain no working days");
            }

            // Check minimum notice period (3 days for planned leaves)
            $notice_days = $today->diff($start)->days;
            if ($leave_type === 'vacation_leave' && $notice_days < 3) {
                throw new Exception("Vacation leave requires at least 3 days notice");
            }

            // Check leave balance
            $balance_query = "SELECT $leave_type FROM leave_balances WHERE employee_id = ? AND year = YEAR(CURRENT_DATE())";
            $stmt = $conn->prepare($balance_query);
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $balance = $stmt->get_result()->fetch_assoc();

            $current_balance = $balance[$leave_type] ?? 0;

            if ($current_balance < $days) {
                throw new Exception("Insufficient leave balance. Available: $current_balance days, Requested: $days days");
            }

            // Check for overlapping leaves
            $overlap_query = "
                SELECT COUNT(*) as count 
                FROM leaves 
                WHERE employee_id = ? 
                AND status != 'rejected'
                AND (
                    (start_date BETWEEN ? AND ?) OR
                    (end_date BETWEEN ? AND ?) OR
                    (start_date <= ? AND end_date >= ?)
                )
            ";
            $stmt = $conn->prepare($overlap_query);
            $stmt->bind_param("issssss", 
                $employee_id, 
                $start_date, $end_date,
                $start_date, $end_date,
                $start_date, $end_date
            );
            $stmt->execute();
            $overlap = $stmt->get_result()->fetch_assoc();

            if ($overlap['count'] > 0) {
                throw new Exception("There is already a leave application for these dates");
            }

            // Check department staffing level
            $dept_query = "
                SELECT e.department, COUNT(*) as on_leave
                FROM leaves l
                JOIN employees e ON l.employee_id = e.id
                WHERE e.department = (SELECT department FROM employees WHERE id = ?)
                AND l.status = 'approved'
                AND (
                    (l.start_date BETWEEN ? AND ?) OR
                    (l.end_date BETWEEN ? AND ?) OR
                    (l.start_date <= ? AND l.end_date >= ?)
                )
                GROUP BY e.department
            ";
            $stmt = $conn->prepare($dept_query);
            $stmt->bind_param("issssss",
                $employee_id,
                $start_date, $end_date,
                $start_date, $end_date,
                $start_date, $end_date
            );
            $stmt->execute();
            $dept_result = $stmt->get_result()->fetch_assoc();

            if ($dept_result && $dept_result['on_leave'] >= 2) {
                throw new Exception("Maximum number of employees already on leave for this period in your department");
            }

            // Get leave type ID
            $type_query = "SELECT id FROM leave_types WHERE LOWER(REPLACE(name, ' ', '_')) = ?";
            $stmt = $conn->prepare($type_query);
            $stmt->bind_param("s", $leave_type);
            $stmt->execute();
            $leave_type_result = $stmt->get_result()->fetch_assoc();

            if (!$leave_type_result) {
                throw new Exception("Invalid leave type");
            }

            // Insert leave application
            $insert_query = "
                INSERT INTO leaves (
                    employee_id, leave_type_id, start_date, end_date,
                    days, reason, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
            ";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iissdsi",
                $employee_id,
                $leave_type_result['id'],
                $start_date,
                $end_date,
                $days,
                $reason,
                $_SESSION['user_id']
            );

            if (!$stmt->execute()) {
                throw new Exception("Error submitting leave application: " . $conn->error);
            }

            // Get employee details for logging
            $emp_query = "SELECT first_name, last_name FROM employees WHERE id = ?";
            $stmt = $conn->prepare($emp_query);
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();

            // Log the activity
            log_activity(
                $_SESSION['user_id'],
                'apply_leave',
                "Applied for $leave_type ($days days) for {$employee['first_name']} {$employee['last_name']}"
            );

            $conn->commit();
            $_SESSION['success'] = "Leave application submitted successfully";
            header("Location: leaves.php");
            exit;
        }
    }
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: leaves.php");
    exit;
}