<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// Make sure staff is linked to an employee
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "My Profile";
$_SESSION['active_menu'] = 'profile';

// Get employee details
$stmt = $conn->prepare("
    SELECT e.*, u.username, u.email as user_email, u.last_login 
    FROM employees e
    JOIN users u ON e.user_id = u.id
    WHERE e.id = ? AND e.status = 'active'
");

$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

// Redirect if employee not found
if (!$employee) {
    $_SESSION['error'] = "Employee record not found";
    header("Location: ../login.php");
    exit();
}

// Handle password update
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match");
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Password updated successfully";
            log_activity($_SESSION['user_id'], 'password_update', 'Password changed successfully');
        } else {
            throw new Exception("Failed to update password");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: profile.php");
    exit();
}

include '../header.php';
include 'staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Personal Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Employee ID:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($employee['employee_code']); ?></dd>
                                    
                                    <dt class="col-sm-4">Name:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></dd>
                                    
                                    <dt class="col-sm-4">Position:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($employee['position']); ?></dd>
                                    
                                    <dt class="col-sm-4">Department:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($employee['department']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Email:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($employee['user_email']); ?></dd>
                                    
                                    <dt class="col-sm-4">Contact:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></dd>
                                    
                                    <dt class="col-sm-4">Hire Date:</dt>
                                    <dd class="col-sm-8"><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A'; ?></dd>
                                    
                                    <dt class="col-sm-4">Last Login:</dt>
                                    <dd class="col-sm-8"><?php echo $employee['last_login'] ? date('M d, Y h:i A', strtotime($employee['last_login'])) : 'Never'; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Change Password</h4>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php include '../footer.php'; ?>
