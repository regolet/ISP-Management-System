<?php
require_once '../config.php';
check_auth();

try {
    // Create backup directory if it doesn't exist
    $backup_dir = 'backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    // Generate backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$timestamp}.sql";
    $filepath = $backup_dir . $filename;

    // Get database credentials
    $db_host = 'localhost';  // Using direct value since host_info might not be reliable
    $db_name = 'isp';        // Using database name from config
    $db_user = 'root';       // Using username from config
    $db_pass = '';          // Using password from config

    // Create backup command with proper escaping
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_name),
        escapeshellarg($filepath)
    );

    // Execute backup command
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        // Log the error and throw exception
        $error = implode("\n", $output);
        error_log("Backup creation failed: " . $error);
        throw new Exception("Database backup failed. Please check error logs.");
    }

    if (!file_exists($filepath)) {
        throw new Exception("Backup file was not created");
    }

    $size = filesize($filepath);
    if ($size === 0) {
        unlink($filepath);
        throw new Exception("Backup file is empty");
    }

    // Log successful backup
    $stmt = $conn->prepare("
        INSERT INTO backup_logs (filename, size, type, status, created_by) 
        VALUES (?, ?, 'full', 'success', ?)
    ");
    $stmt->bind_param("sii", $filename, $size, $_SESSION['user_id']);
    $stmt->execute();

    // Log activity
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, type, description, ip_address) 
        VALUES (?, 'backup', ?, ?)
    ");
    $description = "Created database backup: {$filename}";
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
    $stmt->execute();

    $_SESSION['success'] = "Database backup created successfully";

} catch (Exception $e) {
    // Log failed backup attempt
    if (isset($filename)) {
        $stmt = $conn->prepare("
            INSERT INTO backup_logs (filename, size, type, status, created_by) 
            VALUES (?, 0, 'full', 'failed', ?)
        ");
        $stmt->bind_param("si", $filename, $_SESSION['user_id']);
        $stmt->execute();
    }

    $_SESSION['error'] = "Error creating backup: " . $e->getMessage();
    error_log("Backup creation error: " . $e->getMessage());
}

// Redirect back to backup page
header("Location: backup.php");
exit();
