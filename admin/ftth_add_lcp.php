<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Validate input
    $required = ['name', 'olt_id', 'pon_port', 'splitter_type', 'meters_lcp'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $name = trim($_POST['name']);
    $oltId = intval($_POST['olt_id']);
    $ponPort = intval($_POST['pon_port']);
    $splitterType = intval($_POST['splitter_type']);
    $metersLcp = floatval($_POST['meters_lcp']);

    // Start transaction
    $db->beginTransaction();

    // Check if OLT exists
    $stmt = $db->prepare("
        SELECT number_of_pons 
        FROM olt_devices 
        WHERE id = ?
    ");
    $stmt->execute([$oltId]);
    $olt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$olt) {
        throw new Exception("Selected OLT not found");
    }

    // Validate port number
    if ($ponPort < 1 || $ponPort > $olt['number_of_pons']) {
        throw new Exception("Invalid PON port number");
    }

    // Check if port is available
    $stmt = $db->prepare("
        SELECT status 
        FROM olt_ports 
        WHERE olt_device_id = ? AND port_no = ?
    ");
    $stmt->execute([$oltId, $ponPort]);
    $port = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$port || $port['status'] !== 'available') {
        throw new Exception("Selected PON port is not available");
    }

    // Get splitter details
    $stmt = $db->prepare("
        SELECT ports, loss 
        FROM olt_splitter_types 
        WHERE id = ?
    ");
    $stmt->execute([$splitterType]);
    $splitter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$splitter) {
        throw new Exception("Invalid splitter type");
    }

    // Check for duplicate name
    $stmt = $db->prepare("
        SELECT id 
        FROM olt_lcps 
        WHERE name = ?
    ");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        throw new Exception("An LCP with this name already exists");
    }

    // Insert LCP record
    $stmt = $db->prepare("
        INSERT INTO olt_lcps (
            name,
            mother_nap_type,
            mother_nap_id,
            pon_port,
            splitter_type,
            total_ports,
            splitter_loss,
            meters_lcp
        )
        VALUES (?, 'OLT', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $oltId,
        $ponPort,
        $splitterType,
        $splitter['ports'],
        $splitter['loss'],
        $metersLcp
    ]);
    $lcpId = $db->lastInsertId();

    // Update OLT port status
    $stmt = $db->prepare("
        UPDATE olt_ports 
        SET status = 'in_use' 
        WHERE olt_device_id = ? AND port_no = ?
    ");
    $stmt->execute([$oltId, $ponPort]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'LCP added successfully',
        'data' => [
            'id' => $lcpId,
            'name' => $name,
            'olt_id' => $oltId,
            'pon_port' => $ponPort,
            'splitter_type' => $splitterType,
            'total_ports' => $splitter['ports'],
            'splitter_loss' => $splitter['loss'],
            'meters_lcp' => $metersLcp
        ]
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
