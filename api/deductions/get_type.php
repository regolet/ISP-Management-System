<?php
require_once '../../config.php';
check_login();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$query = "SELECT * FROM deduction_types WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($type = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $type]);
} else {
    echo json_encode(['success' => false, 'message' => 'Deduction type not found']);
}
