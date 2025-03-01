<?php
require_once 'config.php';
check_login();

// Get ID from either POST JSON or GET parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = filter_var($input['id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
} else {
    $id = filter_var($_GET['id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
}

if (!$id) {
    $_SESSION['error'] = "ID is required";
    header('Location: payroll.php');
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. First delete deduction_transactions
    $stmt = $conn->prepare("DELETE dt FROM deduction_transactions dt
                           INNER JOIN payroll_items pi ON dt.payroll_item_id = pi.id
                           WHERE pi.payroll_period_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 2. Then delete payroll_items
    $stmt = $conn->prepare("DELETE FROM payroll_items WHERE payroll_period_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 3. Finally delete payroll_period
    $stmt = $conn->prepare("DELETE FROM payroll_periods WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $conn->commit();
    $_SESSION['success'] = "Payroll period deleted successfully";
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error deleting payroll period: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting payroll period: " . $e->getMessage();
}

// Respond based on request type
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    echo json_encode([
        'success' => !isset($_SESSION['error']),
        'message' => $_SESSION['success'] ?? $_SESSION['error'] ?? ''
    ]);
} else {
    header('Location: payroll.php');
}
exit;
?>
