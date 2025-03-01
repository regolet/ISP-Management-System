<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = clean_input($data['id']);

    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting expense: ' . $e->getMessage()
    ]);
}
?>
