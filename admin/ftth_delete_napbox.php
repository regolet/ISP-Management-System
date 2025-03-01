<?php
require_once '../config.php';
check_auth();

if (isset($_GET['id'])) {
    // Get the NAP box ID
    $napboxId = $_GET['id'];
    
    // Establish database connection
    $conn = get_db_connection();
    
    try {
        // Prepare and execute the delete statement
        $stmt = $conn->prepare("DELETE FROM olt_napboxs WHERE id = ?");
        $stmt->bindValue(1, $napboxId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "NAP Box deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting NAP Box.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting NAP Box: " . $e->getMessage();
    }
}

// Redirect back to NAP Box management page
header("Location: ftth_napbox.php");
exit();
?>
