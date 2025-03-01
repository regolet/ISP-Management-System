<?php
require_once 'config.php';
check_login();

try {
    $conn->begin_transaction();

    if (isset($_POST['single'])) {
        // Handle single record save
        $data = $_POST['attendance'];
        
        // Convert time to datetime format
        $date = $data['date'];
        $time_in = !empty($data['time_in']) ? $date . ' ' . $data['time_in'] : null;
        $time_out = !empty($data['time_out']) ? $date . ' ' . $data['time_out'] : null;

        $stmt = $conn->prepare("INSERT INTO attendance 
            (employee_id, date, time_in, time_out, status, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            time_in = VALUES(time_in),
            time_out = VALUES(time_out),
            status = VALUES(status),
            notes = VALUES(notes)");

        $stmt->bind_param("isssssi", 
            $data['employee_id'],
            $data['date'],
            $time_in,
            $time_out,
            $data['status'],
            $data['notes'],
            $_SESSION['user_id']  // Add created_by from session
        );
        
        $success = $stmt->execute();
        
        if ($success) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception($conn->error);
        }
        exit;
    }

    // Handle bulk save
    foreach ($_POST['attendance'] as $employee_id => $data) {
        // Convert time to datetime format
        $date = $_POST['date'];
        $time_in = !empty($data['time_in']) ? $date . ' ' . $data['time_in'] : null;
        $time_out = !empty($data['time_out']) ? $date . ' ' . $data['time_out'] : null;

        $stmt = $conn->prepare("INSERT INTO attendance 
            (employee_id, date, time_in, time_out, status, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            time_in = VALUES(time_in),
            time_out = VALUES(time_out),
            status = VALUES(status),
            notes = VALUES(notes)");

        $stmt->bind_param("isssssi", 
            $employee_id,
            $_POST['date'],
            $time_in,
            $time_out,
            $data['status'],
            $data['notes'],
            $_SESSION['user_id']  // Add created_by from session
        );
        
        $stmt->execute();
    }

    $conn->commit();
    $_SESSION['success'] = "Attendance records saved successfully.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error saving attendance: " . $e->getMessage();
}

header("Location: attendance.php?date=" . $_POST['date']);
exit;
?>
