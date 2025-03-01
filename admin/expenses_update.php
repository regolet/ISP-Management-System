<?php
require_once '../../config.php';
check_login();

header('Content-Type: application/json');

try {
    $id = clean_input($_POST['id']);
    $description = clean_input($_POST['description']);
    $category_id = clean_input($_POST['category']); // Changed from category to category_id
    $amount = clean_input($_POST['amount']);
    $expense_date = clean_input($_POST['expense_date']);
    $notes = clean_input($_POST['notes']);

    $stmt = $conn->prepare("
        UPDATE expenses
        SET description = ?,
            category_id = ?,
            amount = ?,
            expense_date = ?,
            notes = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param("sidssis",
        $description,
        $category_id,
        $amount,
        $expense_date,
        $notes,
        $id,
        $_SESSION['user_id']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Expense updated successfully'
        ]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating expense: ' . $e->getMessage()
    ]);
}
?>
