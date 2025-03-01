<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_DEFAULT);
    $payment_method_id = filter_input(INPUT_POST, 'payment_method_id', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_DEFAULT);
    $reference_no = filter_input(INPUT_POST, 'reference_no', FILTER_DEFAULT) ?? NULL;
    $notes = filter_input(INPUT_POST, 'notes', FILTER_DEFAULT);

    if (!$payment_id || !$amount || !$payment_date || !$payment_method_id || !$status) {
        $_SESSION['error'] = "Invalid input data";
        header("Location: payment_edit.php?id=$payment_id");
        exit();
    }

    $query = "UPDATE payments SET
              amount = ?,
              payment_date = ?,
              payment_method_id = ?,
              status = ?,
              reference_no = ?,
              notes = ?
              WHERE id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("dssisss", $amount, $payment_date, $payment_method_id, $status, $reference_no, $notes, $payment_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Payment updated successfully";
        header("Location: payments.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating payment: " . $stmt->error;
        header("Location: payment_edit.php?id=$payment_id");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: payments.php");
    exit();
}
?>
