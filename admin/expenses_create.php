<?php
require_once '../../config.php';
check_login();

header('Content-Type: application/json');

try {
    $description = clean_input($_POST['description']);
    $category_id = clean_input($_POST['category']); // Changed from category to category_id
    $amount = clean_input($_POST['amount']);
    $expense_date = clean_input($_POST['expense_date']);
    $notes = clean_input($_POST['notes']);

    $stmt = $conn->prepare("
        INSERT INTO expenses (
            user_id, category_id, description, amount,
            expense_date, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param("iiisdss",
        $_SESSION['user_id'],
        $category_id,
        $description,
        $amount,
        $expense_date,
        $notes
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Expense created successfully',
            'id' => $conn->insert_id
        ]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error creating expense: ' . $e->getMessage()
    ]);
}
?>
