<?php
require_once 'config.php';
check_login();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $conn->begin_transaction();

    // Validate required fields
    $required_fields = ['asset_id', 'expense_date', 'category', 'description', 'amount', 'payment_method'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize and validate input
    $asset_id = filter_input(INPUT_POST, 'asset_id', FILTER_SANITIZE_NUMBER_INT);
    $expense_date = filter_input(INPUT_POST, 'expense_date', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    $expense_id = filter_input(INPUT_POST, 'expense_id', FILTER_SANITIZE_NUMBER_INT);

    // Additional validation
    if (!$asset_id || !strtotime($expense_date) || $amount <= 0) {
        throw new Exception('Invalid input values');
    }

    // Validate reference number for non-cash payments
    if ($payment_method !== 'cash' && empty($reference_number)) {
        throw new Exception('Reference number is required for non-cash payments');
    }

    // Check if asset exists
    $check_asset = $conn->prepare("SELECT id, name FROM assets WHERE id = ?");
    $check_asset->bind_param("i", $asset_id);
    $check_asset->execute();
    $asset = $check_asset->get_result()->fetch_assoc();

    if (!$asset) {
        throw new Exception('Asset not found');
    }

    if ($expense_id) {
        // Update existing expense
        $stmt = $conn->prepare("
            UPDATE asset_expenses 
            SET expense_date = ?, category = ?, description = ?, amount = ?, 
                payment_method = ?, reference_number = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND asset_id = ?
        ");
        $stmt->bind_param(
            "sssdssii",
            $expense_date, $category, $description, $amount,
            $payment_method, $reference_number, $expense_id, $asset_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update expense');
        }

        $action = "Updated expense";
        $details = sprintf(
            "Updated expense of ₱%s for asset '%s' dated %s",
            number_format($amount, 2),
            $asset['name'],
            date('M d, Y', strtotime($expense_date))
        );
    } else {
        // Insert new expense
        $stmt = $conn->prepare("
            INSERT INTO asset_expenses 
            (asset_id, expense_date, category, description, amount, payment_method, reference_number, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssdssi",
            $asset_id, $expense_date, $category, $description,
            $amount, $payment_method, $reference_number, $_SESSION['user_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save expense');
        }

        $action = "Added expense";
        $details = sprintf(
            "Added new expense of ₱%s for asset '%s' dated %s",
            number_format($amount, 2),
            $asset['name'],
            date('M d, Y', strtotime($expense_date))
        );
    }

    // Log activity
    log_activity($action, 'asset_expenses', $details);

    $conn->commit();
    $_SESSION['success'] = 'Expense saved successfully';

} catch (Exception $e) {
    $conn->rollback();
    error_log("Expense Save Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header("Location: asset_expenses.php?id=" . $asset_id);
exit();