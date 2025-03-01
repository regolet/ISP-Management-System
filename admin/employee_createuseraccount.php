<?php
require_once '../config.php';
check_auth();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Get employee details first
        $stmt = $conn->prepare("
            SELECT id, employee_code, email, first_name, last_name 
            FROM employees 
            WHERE id = ? AND status = 'active'
        ");
        
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $employee = $stmt->get_result()->fetch_assoc();
        
        if (!$employee) {
            throw new Exception("Active employee not found");
        }
        
        // Check if user already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $employee['employee_code'], $employee['email']);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("User account already exists with this employee code or email");
        }
        
        // Generate secure password
        $default_password = 'Staff@' . date('Y');
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        // Create user account with explicit field names
        $insert = $conn->prepare("
            INSERT INTO users (
                username, 
                password, 
                email, 
                role, 
                status
            ) VALUES (?, ?, ?, 'staff', 'active')
        ");
        
        $insert->bind_param("sss", 
            $employee['employee_code'],
            $hashed_password,
            $employee['email']
        );
        
        if (!$insert->execute()) {
            throw new Exception("Failed to create user account: " . $insert->error);
        }
        
        // Log the success
        log_activity(
            $_SESSION['user_id'], 
            'create_user', 
            sprintf(
                'Created staff account for %s %s (Employee Code: %s)',
                $employee['first_name'],
                $employee['last_name'],
                $employee['employee_code']
            )
        );
        
        $_SESSION['success'] = "User account created successfully.<br>Username: $employee[employee_code]<br>Password: $default_password";
        
        // Redirect back to employee edit page
        header("Location: employee_view.php?id=" . $employee_id . "&edit=true");
        exit();
        
    } catch (Exception $e) {
        error_log("Error creating user account: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: employees.php");
    exit();
}

header("Location: employees.php");
exit();
