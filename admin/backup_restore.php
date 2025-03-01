<?php
require_once '../config.php';
check_auth();

try {
    if (!isset($_POST['filename'])) {
        throw new Exception("No backup file specified");
    }

    $filename = basename($_POST['filename']); // Sanitize filename
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

    // Get database credentials
    $db_host = 'localhost';  // Using direct value since host_info might not be reliable
    $db_name = 'isp';        // Using database name from config
    $db_user = 'root';       // Using username from config
    $db_pass = '';          // Using password from config

    // Restore command
    $command = sprintf(
        'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_name),
        escapeshellarg($filepath)
    );

    // Execute restore command
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        // Log the error and throw exception
        $error = implode("\n", $output);
        error_log("Database restore failed: " . $error);
        throw new Exception("Database restore failed. Please check error logs.");
    }

    // Log activity
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, type, description, ip_address) 
        VALUES (?, 'restore', ?, ?)
    ");
    $description = "Restored database from backup: {$filename}";
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
    $stmt->execute();

    $_SESSION['success'] = "Database restored successfully from backup";

} catch (Exception $e) {
    error_log("Database restore error: " . $e->getMessage());
    $_SESSION['error'] = "Error restoring database: " . $e->getMessage();
}

header("Location: backup.php");
exit();
