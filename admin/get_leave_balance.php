<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

try {
    $employee_id = filter_input(INPUT_GET, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
    $leave_type = filter_var($_GET['leave_type'], FILTER_SANITIZE_STRING);

    if (!$employee_id || !$leave_type) {
        throw new Exception("Missing required parameters");
    }

    // Validate leave type
    if (!in_array($leave_type, ['sick_leave', 'vacation_leave', 'emergency_leave'])) {
        throw new Exception("Invalid leave type");
    }

    // Get current year's leave balance
    $query = "SELECT $leave_type FROM leave_balances 
              WHERE employee_id = ? AND year = YEAR(CURRENT_DATE())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // If no balance record exists, create one with default values
    if (!$result) {
        $defaults = [
            'sick_leave' => 15.0,
            'vacation_leave' => 15.0,
            'emergency_leave' => 5.0
        ];

        $insert_query = "INSERT INTO leave_balances (employee_id, year, sick_leave, vacation_leave, emergency_leave)
                        VALUES (?, YEAR(CURRENT_DATE()), ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iddd", 
            $employee_id,
            $defaults['sick_leave'],
            $defaults['vacation_leave'],
            $defaults['emergency_leave']
        );
        $stmt->execute();

        $balance = $defaults[$leave_type];
    } else {
        $balance = $result[$leave_type];
    }

    echo json_encode([
        'success' => true,
        'balance' => (float)$balance
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}