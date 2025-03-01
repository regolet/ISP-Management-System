<?php
require_once '../config.php';
check_auth();

try {
    if (!isset($_POST['filename'])) {
        throw new Exception("No backup file specified");
    }

    $filename = basename($_POST['filename']); // Sanitize filename
    $filepath = "backups/{$filename}";

    // Verify file exists
    if (!file_exists($filepath)) {
        throw new Exception("Backup file not found");
    }

    // Verify file is actually a backup file from our system
    $stmt = $conn->prepare("SELECT id FROM backup_logs WHERE filename = ?");
    $stmt->bind_param("s", $filename);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Invalid backup file");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete file
        if (!unlink($filepath)) {
            throw new Exception("Failed to delete backup file");
        }

        // Delete from backup_logs
        $stmt = $conn->prepare("DELETE FROM backup_logs WHERE filename = ?");
        $stmt->bind_param("s", $filename);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update backup logs");
        }

        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, type, description, ip_address) 
            VALUES (?, 'delete', ?, ?)
        ");
        $description = "Deleted backup file: {$filename}";
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
        if (!$stmt->execute()) {
            throw new Exception("Failed to log activity");
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Backup deleted successfully";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Backup delete error: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting backup: " . $e->getMessage();
}

header("Location: backup.php");
exit();
