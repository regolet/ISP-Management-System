<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['employee_id'])) {
        throw new Exception("Not authorized");
    }

    $action = $_POST['action'] ?? '';
    $today = date('Y-m-d');

    switch ($action) {
        case 'time_in':
            // Check if already timed in
            $check = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
            $check->bind_param("is", $_SESSION['employee_id'], $today);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Already timed in for today");
            }
            
            // Record time in
            $stmt = $conn->prepare("
                INSERT INTO attendance (employee_id, date, time_in, status)
                VALUES (?, ?, NOW(), 'present')
            ");
            
            $stmt->bind_param("is", $_SESSION['employee_id'], $today);
            break;

        case 'time_out':
            // Update time out
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET time_out = NOW() 
                WHERE employee_id = ? AND date = ? AND time_out IS NULL
            ");
            
            $stmt->bind_param("is", $_SESSION['employee_id'], $today);
            break;

        default:
            throw new Exception("Invalid action");
    }
    
    if ($stmt->execute()) {
        // Log the activity
        log_activity($_SESSION['user_id'], $action, ucfirst(str_replace('_', ' ', $action)) . " recorded successfully");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to record " . str_replace('_', ' ', $action));
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
