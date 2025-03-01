<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_POST['attendance'])) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

try {
    $data = $_POST['attendance'];
    
    // Validate time format
    if (!empty($data['time_in']) && !preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $data['time_in'])) {
        throw new Exception('Invalid time in format');
    }
    if (!empty($data['time_out']) && !preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $data['time_out'])) {
        throw new Exception('Invalid time out format');
    }

    // Validate times are in order
    if (!empty($data['time_in']) && !empty($data['time_out'])) {
        if (strtotime($data['time_in']) >= strtotime($data['time_out'])) {
            throw new Exception('Time out must be later than time in');
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
