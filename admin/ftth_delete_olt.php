<?php
require_once '../config.php';
check_auth();

// Establish database connection
$conn = get_db_connection();

if (isset($_GET['id'])) {
    $oltId = $_GET['id'];

    // Prepare and execute the delete statement
    $stmt = $conn->prepare("DELETE FROM olt_devices WHERE id = ?");
    $stmt->bindValue(1, $oltId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Set success message
        $_SESSION['success'] = "OLT deleted successfully.";
    } else {
        // Set error message
        $_SESSION['error'] = "Error deleting OLT: " . implode(", ", $stmt->errorInfo());
    }
}

// Redirect back to FTTH.php
header("Location: FTTH.php");
exit();
?>
