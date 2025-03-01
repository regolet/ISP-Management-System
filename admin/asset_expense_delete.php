<?php
require_once 'config.php';
check_login();

try {
    $expense_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $asset_id = filter_input(INPUT_GET, 'asset_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$expense_id || !$asset_id) {
        throw new Exception("Invalid expense or asset ID");
    }

    $conn->begin_transaction();

    // Get expense details for logging
    $get_expense = $conn->prepare("
        SELECT e.*, a.name as asset_name 
        FROM asset_expenses e
        LEFT JOIN assets a ON e.asset_id = a.id
        WHERE e.id = ?
    ");
    $get_expense->bind_param("i", $expense_id);
    $get_expense->execute();
    $expense = $get_expense->get_result()->fetch_assoc();

    if (!$expense) {
        throw new Exception("Expense not found");
    }

    // Delete the expense
    $delete_stmt = $conn->prepare("DELETE FROM asset_expenses WHERE id = ?");
    $delete_stmt->bind_param("i", $expense_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Failed to delete expense");
    }

    // Log with all required parameters
    $action = "Deleted expense";
    $details = sprintf(
        "Deleted expense of ₱%s for asset '%s' dated %s",
        number_format($expense['amount'], 2),
        $expense['asset_name'],
        date('M d, Y', strtotime($expense['expense_date']))
    );
    log_activity($action, 'asset_expenses', $details);

    $conn->commit();
    $_SESSION['success'] = "Expense deleted successfully";

} catch (Exception $e) {
    $conn->rollback();
    error_log("Expense Delete Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header("Location: asset_expenses.php?id=" . $asset_id);
exit();