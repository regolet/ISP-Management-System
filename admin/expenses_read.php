<?php
require_once '../../config.php';
check_login();

header('Content-Type: application/json');

try {
    $id = clean_input($_GET['id']);

    $stmt = $conn->prepare("
        SELECT * FROM expenses
        WHERE id = ? AND (user_id = ? OR EXISTS (
            SELECT 1 FROM users WHERE id = ? AND role = 'admin'
        ))
    ");

    $stmt->bind_param("iii", $id, $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($expense = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $expense
        ]);
    } else {
        throw new Exception('Expense not found');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching expense: ' . $e->getMessage()
    ]);
}
?>
