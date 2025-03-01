<?php
require_once '../../config.php';
check_login();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

$id = filter_var($input['id'], FILTER_SANITIZE_NUMBER_INT);

// Start transaction
$conn->begin_transaction();

try {
    // First delete associated deductions
    $stmt = $conn->prepare("DELETE FROM employee_deductions WHERE deduction_type_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Then delete the deduction type
    $stmt = $conn->prepare("DELETE FROM deduction_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Deduction type deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting deduction type: ' . $e->getMessage()]);
}
