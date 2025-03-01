<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Validate required fields
    $required_fields = ['id', 'name', 'mother_nap_type', 'pon_port', 'splitter_type'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Validate mother_nap_id based on type
    if ($_POST['mother_nap_type'] === 'OLT') {
        if (!isset($_POST['olt_id']) || empty($_POST['olt_id'])) {
            throw new Exception('OLT selection is required');
        }
        $mother_nap_id = $_POST['olt_id'];
    } else if ($_POST['mother_nap_type'] === 'LCP') {
        if (!isset($_POST['parent_lcp_id']) || empty($_POST['parent_lcp_id'])) {
            throw new Exception('Parent LCP selection is required');
        }
        // Prevent circular reference
        if ($_POST['parent_lcp_id'] == $_POST['id']) {
            throw new Exception('An LCP cannot be its own parent');
        }
        $mother_nap_id = $_POST['parent_lcp_id'];
    } else {
        throw new Exception('Invalid mother NAP type');
    }

    $db = get_db_connection();

    // Check if LCP exists
    $stmt = $db->prepare("SELECT id FROM olt_lcp WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    if (!$stmt->fetch()) {
        throw new Exception('LCP not found');
    }

    // Check if name is unique (excluding current LCP)
    $stmt = $db->prepare("SELECT id FROM olt_lcp WHERE name = ? AND id != ?");
    $stmt->execute([$_POST['name'], $_POST['id']]);
    if ($stmt->fetch()) {
        throw new Exception('duplicate_name');
    }

    // Check if port is already in use (excluding current LCP)
    $stmt = $db->prepare("
        SELECT id 
        FROM olt_lcp 
        WHERE mother_nap_type = ? 
        AND mother_nap_id = ? 
        AND pon_port = ?
        AND id != ?
    ");
    $stmt->execute([
        $_POST['mother_nap_type'],
        $mother_nap_id,
        $_POST['pon_port'],
        $_POST['id']
    ]);
    if ($stmt->fetch()) {
        throw new Exception('port_in_use');
    }

    // Validate splitter type exists
    $splitter_type = $_POST['splitter_type'];
    if (strpos($splitter_type, 'PLC-') === 0) {
        $splitter_id = substr($splitter_type, 4);
        $stmt = $db->prepare("SELECT id FROM olt_loss_plc WHERE id = ?");
    } else if (strpos($splitter_type, 'FBT-') === 0) {
        $splitter_id = substr($splitter_type, 4);
        $stmt = $db->prepare("SELECT id FROM olt_loss_fbt WHERE id = ?");
    } else {
        throw new Exception('Invalid splitter type');
    }
    $stmt->execute([$splitter_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid splitter type');
    }

    // Check for circular references in LCP hierarchy
    if ($_POST['mother_nap_type'] === 'LCP') {
        $parent_id = $mother_nap_id;
        $visited = [$_POST['id']];
        
        while ($parent_id) {
            if (in_array($parent_id, $visited)) {
                throw new Exception('Circular reference detected in LCP hierarchy');
            }
            $visited[] = $parent_id;
            
            $stmt = $db->prepare("
                SELECT mother_nap_id 
                FROM olt_lcp 
                WHERE id = ? AND mother_nap_type = 'LCP'
            ");
            $stmt->execute([$parent_id]);
            $result = $stmt->fetch();
            $parent_id = $result ? $result['mother_nap_id'] : null;
        }
    }

    // Update LCP
    $stmt = $db->prepare("
        UPDATE olt_lcp SET
            name = ?,
            mother_nap_type = ?,
            mother_nap_id = ?,
            pon_port = ?,
            splitter_type = ?,
            fiber_length = ?,
            connector_count = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['mother_nap_type'],
        $mother_nap_id,
        $_POST['pon_port'],
        $_POST['splitter_type'],
        $_POST['fiber_length'] ?? 0,
        $_POST['connector_count'] ?? 0,
        $_POST['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'LCP updated successfully'
    ]);

} catch (Exception $e) {
    error_log('Error in ftth_edit_lcp.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
