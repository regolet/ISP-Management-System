<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $db = get_db_connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Validate input
    $type = $_POST['type'] ?? '';
    
    if ($type === 'plc') {
        $ports = $_POST['ports'] ?? '';
        $loss = $_POST['loss'] ?? '';
        
        if (!is_numeric($ports) || !is_numeric($loss)) {
            throw new Exception('Invalid PLC values');
        }

        // Validate common PLC splitter ratios
        $validPorts = [2, 4, 8, 16, 32, 64, 128];
        if (!in_array((int)$ports, $validPorts)) {
            throw new Exception('Invalid port count. Common values are: ' . implode(', ', $validPorts));
        }

        // Validate loss values based on port count
        $expectedLoss = [
            2 => [2.8, 3.2],    // 1:2 split: ~3.0 dB ±0.2
            4 => [6.8, 7.2],    // 1:4 split: ~7.0 dB ±0.2
            8 => [10.3, 10.7],  // 1:8 split: ~10.5 dB ±0.2
            16 => [13.8, 14.2], // 1:16 split: ~14.0 dB ±0.2
            32 => [17.3, 17.7], // 1:32 split: ~17.5 dB ±0.2
            64 => [20.8, 21.2], // 1:64 split: ~21.0 dB ±0.2
            128 => [24.8, 25.2] // 1:128 split: ~25.0 dB ±0.2
        ];

        if (isset($expectedLoss[$ports])) {
            $range = $expectedLoss[$ports];
            if ($loss < $range[0] || $loss > $range[1]) {
                throw new Exception(
                    "Warning: The loss value {$loss} dB for {$ports} ports is outside the typical range " .
                    "({$range[0]} - {$range[1]} dB). Are you sure this is correct?"
                );
            }
        }

        // Check if ports value already exists
        $checkStmt = $db->prepare("SELECT id FROM olt_loss_plc WHERE ports = ?");
        $checkStmt->execute([$ports]);
        if ($checkStmt->fetch()) {
            throw new Exception('A PLC loss value for this port count already exists');
        }

        $stmt = $db->prepare("INSERT INTO olt_loss_plc (ports, loss) VALUES (?, ?)");
        $success = $stmt->execute([$ports, $loss]);
        
        if (!$success) {
            throw new Exception('Failed to add PLC loss value');
        }
    } 
    else if ($type === 'fbt') {
        $value = $_POST['value'] ?? '';
        $loss = $_POST['loss'] ?? '';
        
        if (!is_numeric($value) || !is_numeric($loss)) {
            throw new Exception('Invalid FBT values');
        }

        // Validate FBT percentage range
        if ($value < 0 || $value > 100) {
            throw new Exception('FBT value must be between 0 and 100 percent');
        }

        // Validate common FBT ratios and their typical loss values
        $expectedLoss = [
            10 => [10.3, 10.7], // 10% split: ~10.5 dB ±0.2
            20 => [7.3, 7.7],   // 20% split: ~7.5 dB ±0.2
            30 => [5.3, 5.7],   // 30% split: ~5.5 dB ±0.2
            40 => [4.3, 4.7],   // 40% split: ~4.5 dB ±0.2
            50 => [3.3, 3.7]    // 50% split: ~3.5 dB ±0.2
        ];

        // Find the closest standard ratio
        $closestRatio = null;
        $minDiff = 100;
        foreach (array_keys($expectedLoss) as $standardRatio) {
            $diff = abs($value - $standardRatio);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closestRatio = $standardRatio;
            }
        }

        // If within 2% of a standard ratio, validate the loss value
        if ($minDiff <= 2 && isset($expectedLoss[$closestRatio])) {
            $range = $expectedLoss[$closestRatio];
            if ($loss < $range[0] || $loss > $range[1]) {
                throw new Exception(
                    "Warning: The loss value {$loss} dB for {$value}% split is outside the typical range " .
                    "({$range[0]} - {$range[1]} dB) for a {$closestRatio}% split. Are you sure this is correct?"
                );
            }
        }

        // Check if value already exists
        $checkStmt = $db->prepare("SELECT id FROM olt_loss_fbt WHERE value = ?");
        $checkStmt->execute([$value]);
        if ($checkStmt->fetch()) {
            throw new Exception('An FBT loss value for this percentage already exists');
        }

        $stmt = $db->prepare("INSERT INTO olt_loss_fbt (value, loss) VALUES (?, ?)");
        $success = $stmt->execute([$value, $loss]);
        
        if (!$success) {
            throw new Exception('Failed to add FBT loss value');
        }
    } 
    else {
        throw new Exception('Invalid type specified');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Loss value added successfully'
    ]);

} catch (PDOException $e) {
    error_log('Database error in ftth_add_loss_value.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('General error in ftth_add_loss_value.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
