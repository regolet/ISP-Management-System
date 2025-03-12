<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is admin
if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Read the SQL file - now points to the file that removes username/password
$sqlFile = dirname(__DIR__) . '/database/alter_olt_table_remove.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Error reading SQL file");
}

// Split SQL into statements
$sqlStatements = array_filter(
    array_map(
        'trim',
        explode(';', $sql)
    ),
    'strlen'
);

$success = true;
$errors = [];

// Execute each statement
foreach ($sqlStatements as $statement) {
    try {
        $db->exec($statement);
    } catch (PDOException $e) {
        $success = false;
        $errors[] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update OLT Schema - ISP Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>OLT Schema Update</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading">Success!</h4>
                        <p>The OLT table schema has been updated successfully.</p>
                        <p>Username and password fields have been removed from OLT devices table.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">Error!</h4>
                        <p>There was an error updating the OLT schema:</p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <a href="olt.php" class="btn btn-primary">Back to OLT Management</a>
            </div>
        </div>
    </div>
</body>
</html>
