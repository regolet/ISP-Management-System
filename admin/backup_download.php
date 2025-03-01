<?php
require_once '../config.php';
check_auth();

try {
    if (!isset($_GET['file'])) {
        throw new Exception("No backup file specified");
    }

    $filename = basename($_GET['file']); // Sanitize filename
    $filepath = "backups/{$filename}";

    // Verify file exists and is readable
    if (!file_exists($filepath)) {
        throw new Exception("Backup file not found");
    }

    if (!is_readable($filepath)) {
        throw new Exception("Backup file is not readable");
    }

    // Verify file is actually a backup file from our system
    $stmt = $conn->prepare("SELECT id FROM backup_logs WHERE filename = ? AND status = 'success'");
    $stmt->bind_param("s", $filename);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Invalid backup file");
    }

    // Log activity
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, type, description, ip_address) 
        VALUES (?, 'download', ?, ?)
    ");
    $description = "Downloaded backup file: {$filename}";
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
    $stmt->execute();

    // Send appropriate headers
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Output file contents
    if ($handle = fopen($filepath, 'rb')) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        throw new Exception("Could not open file for reading");
    }
    exit();

} catch (Exception $e) {
    error_log("Backup download error: " . $e->getMessage());
    $_SESSION['error'] = "Error downloading backup: " . $e->getMessage();
    header("Location: backup.php");
    exit();
}
