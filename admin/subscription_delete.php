<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No subscription specified";
    header('Location: subscriptions.php');
    exit;
}

try {
    $conn->begin_transaction();
    
    $id = clean_input($_GET['id']);
    
    // Delete subscription
    $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        $_SESSION['success'] = "Subscription deleted successfully";
    } else {
        throw new Exception("Subscription not found");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error deleting subscription: " . $e->getMessage();
}

header('Location: subscriptions.php');
exit;
