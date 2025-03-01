<?php
require_once 'config.php';
check_login();

// Only allow admin to run this script
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

try {
    $conn->begin_transaction();

    // Create permissions table
    $conn->query("CREATE TABLE IF NOT EXISTS permissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        category VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create role_permissions table
    $conn->query("CREATE TABLE IF NOT EXISTS role_permissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_role_permission (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    )");

    // Define default permissions
    $default_permissions = [
        // Dashboard permissions
        ['view_dashboard', 'Can view dashboard', 'menu'],
        
        // User management permissions
        ['view_users', 'Can view user list', 'menu'],
        ['add_user', 'Can add new users', 'action'],
        ['edit_user', 'Can edit users', 'action'],
        ['delete_user', 'Can delete users', 'action'],
        
        // Role management permissions
        ['view_roles', 'Can view roles list', 'menu'],
        ['add_role', 'Can add new roles', 'action'],
        ['edit_role', 'Can edit roles', 'action'],
        ['delete_role', 'Can delete roles', 'action'],
        
        // Leave management permissions
        ['view_leaves', 'Can view leave list', 'menu'],
        ['apply_leave', 'Can apply for leave', 'action'],
        ['approve_leave', 'Can approve/reject leave requests', 'action'],
        ['manage_leave_types', 'Can manage leave types', 'action'],
        
        // Attendance permissions
        ['view_attendance', 'Can view attendance', 'menu'],
        ['mark_attendance', 'Can mark attendance', 'action'],
        ['edit_attendance', 'Can edit attendance records', 'action'],
        ['generate_attendance_report', 'Can generate attendance reports', 'action'],
        
        // Report permissions
        ['view_reports', 'Can view reports section', 'menu'],
        ['generate_reports', 'Can generate various reports', 'action'],
        
        // Settings permissions
        ['view_settings', 'Can view settings', 'menu'],
        ['manage_settings', 'Can modify system settings', 'action']
    ];

    // Insert default permissions
    $stmt = $conn->prepare("INSERT IGNORE INTO permissions (name, description, category) VALUES (?, ?, ?)");

    foreach ($default_permissions as $permission) {
        $stmt->bind_param("sss", $permission[0], $permission[1], $permission[2]);
        $stmt->execute();
    }

    // Assign all permissions to admin role by default
    $admin_role = $conn->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch_assoc();
    
    if ($admin_role) {
        $permissions = $conn->query("SELECT id FROM permissions");
        $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        
        while ($permission = $permissions->fetch_assoc()) {
            $stmt->bind_param("ii", $admin_role['id'], $permission['id']);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo "Permissions setup completed successfully!";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}