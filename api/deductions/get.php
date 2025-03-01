<?php
require_once '../../config.php';
check_login();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$query = "SELECT * FROM employee_deductions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($deduction = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $deduction]);
} else {
    echo json_encode(['success' => false, 'message' => 'Deduction not found']);
}
