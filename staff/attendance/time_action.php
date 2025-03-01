<?php
require_once '../../config.php';
require_once '../staff_auth.php';

header('Content-Type: application/json');

try {
    // Get employee ID
    $stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();

    if (!$employee) {
        throw new Exception('Employee record not found');
    }

    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    
    // Get work schedule settings
    $settings = $conn->query("SELECT * FROM attendance_settings WHERE id = 1")->fetch_assoc();
    $work_start = strtotime($today . ' ' . $settings['work_start_time']);
    $late_threshold = $work_start + ($settings['late_threshold_minutes'] * 60);

    if ($_POST['action'] === 'time_in') {
        // Check if already timed in
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->bind_param("is", $employee['id'], $today);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Already timed in for today');
        }

        // Set status based on time
        $status = (time() > $late_threshold) ? 'late' : 'present';

        // Record time in
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, time_in, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $employee['id'], $today, $now, $status);
        $stmt->execute();

        // Log activity
        log_activity($_SESSION['user_id'], 'time_in', "Time in recorded at " . date('h:i A'));

        echo json_encode(['success' => true, 'message' => 'Time in recorded successfully']);
    } 
    elseif ($_POST['action'] === 'time_out') {
        // Update existing attendance record
        $stmt = $conn->prepare("UPDATE attendance SET time_out = ? WHERE employee_id = ? AND date = ? AND time_out IS NULL");
        $stmt->bind_param("sis", $now, $employee['id'], $today);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            log_activity($_SESSION['user_id'], 'time_out', "Time out recorded at " . date('h:i A'));
            echo json_encode(['success' => true, 'message' => 'Time out recorded successfully']);
        } else {
            throw new Exception('No active attendance record found');
        }
    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
