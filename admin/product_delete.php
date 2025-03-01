<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit();
}

$id = clean_input($_GET['id']);

// Check if product exists
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = "Product not found";
    header("Location: inventory.php");
    exit();
}

// Check if product has any transactions
$stmt = $conn->prepare("SELECT id FROM inventory_transactions WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // If product has transactions, just mark it as inactive
    $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product marked as inactive";
    } else {
        $_SESSION['error'] = "Error marking product as inactive: " . $conn->error;
    }
} else {
    // If no transactions, delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting product: " . $conn->error;
    }
}

header("Location: inventory.php");
exit();
?>
