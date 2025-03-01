<?php
require_once 'config.php';
check_login();

// Only admin can manage leave balances
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: leaves.php");
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->begin_transaction();

        $balance_id = filter_input(INPUT_POST, 'balance_id', FILTER_SANITIZE_STRING);
        $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
        $sick_leave = filter_input(INPUT_POST, 'sick_leave', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $vacation_leave = filter_input(INPUT_POST, 'vacation_leave', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $emergency_leave = filter_input(INPUT_POST, 'emergency_leave', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Validate inputs
        if (!$employee_id || !isset($sick_leave) || !isset($vacation_leave) || !isset($emergency_leave)) {
            throw new Exception("All fields are required");
        }

        // Get employee details for logging
        $emp_query = "SELECT first_name, last_name FROM employees WHERE id = ?";
        $stmt = $conn->prepare($emp_query);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $employee = $stmt->get_result()->fetch_assoc();

        if ($balance_id) {
            // Update existing balance
            $query = "
                UPDATE leave_balances 
                SET sick_leave = ?,
                    vacation_leave = ?,
                    emergency_leave = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND employee_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("dddii", 
                $sick_leave, 
                $vacation_leave, 
                $emergency_leave, 
                $balance_id,
                $employee_id
            );
        } else {
            // Insert new balance
            $query = "
                INSERT INTO leave_balances (
                    employee_id, year, sick_leave, vacation_leave, emergency_leave
                ) VALUES (
                    ?, YEAR(CURRENT_DATE()), ?, ?, ?
                )
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iddd", 
                $employee_id,
                $sick_leave, 
                $vacation_leave, 
                $emergency_leave
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Error updating leave balance: " . $conn->error);
        }

        // Log the activity
        log_activity(
            $_SESSION['user_id'],
            'update_leave_balance',
            "Updated leave balance for {$employee['first_name']} {$employee['last_name']}"
        );

        $conn->commit();
        $_SESSION['success'] = "Leave balance updated successfully";
    }
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: leaves.php");
exit;