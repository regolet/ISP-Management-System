<?php
function get_staff_quick_stats($employee_id, $conn) {
    // Get today's stats
    $today = date('Y-m-d');
    
    // Get attendance status
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_assoc();

    // Get today's payments
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM payments 
                           WHERE created_by = ? AND DATE(payment_date) = ?");
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_assoc();

    // Get pending tasks
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks 
                           WHERE assigned_to = ? AND status = 'pending'");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_assoc();

    return [
        'attendance' => $attendance,
        'payments' => $payments,
        'tasks' => $tasks['count'],
        'shift_status' => !$attendance ? 'not_started' : 
                         (!$attendance['time_out'] ? 'ongoing' : 'completed')
    ];
}
