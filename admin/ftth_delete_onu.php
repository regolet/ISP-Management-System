<?php
require_once '../config.php';
check_auth();

// Get database connection
$conn = get_db_connection();

if (isset($_GET['id']) && isset($_GET['napbox_id'])) {
    $id = (int)$_GET['id'];
    $napbox_id = (int)$_GET['napbox_id'];

    // Verify ONU belongs to the specified NAP box
    $stmt = $conn->prepare("SELECT id FROM customer_onus WHERE id = ? AND napbox_id = ?");
    $stmt->execute([$id, $napbox_id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Invalid ONU or NAP box.";
        header("Location: ftth_view_onus.php?id=" . $napbox_id);
        exit();
    }

    // Delete the ONU
    $stmt = $conn->prepare("DELETE FROM customer_onus WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "ONU deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting ONU: " . implode(", ", $stmt->errorInfo());
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ftth_view_onus.php?id=" . $napbox_id);
exit();
?>
