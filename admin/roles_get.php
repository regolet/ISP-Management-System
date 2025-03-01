<?php
require_once '../config.php';
check_auth('admin');

header('Content-Type: application/json');

try {
    $role_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    if (!$role_id) {
        throw new Exception("Invalid role ID");
    }

    // Get role details with permissions
    $stmt = $conn->prepare("
        SELECT r.*, 
               GROUP_CONCAT(rp.permission_id) as permissions
        FROM roles r
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        WHERE r.id = ?
        GROUP BY r.id
    ");
    
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();

    if (!$role) {
        throw new Exception("Role not found");
    }

    // Convert permissions string to array
    $role['permissions'] = $role['permissions'] ? explode(',', $role['permissions']) : [];

    // Get all available permissions grouped by category
    $permissions_query = "SELECT * FROM permissions ORDER BY category, name";
    $permissions_result = $conn->query($permissions_query);
    
    $permissions_by_category = [];
    while ($permission = $permissions_result->fetch_assoc()) {
        $permissions_by_category[$permission['category']][] = $permission;
    }

    // Add permissions list to response
    $role['available_permissions'] = $permissions_by_category;

    echo json_encode($role);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}