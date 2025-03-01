<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = get_db_connection();
        
        // Get form data
        $napboxName = $_POST['napboxName'];
        $connectionType = $_POST['connectionType'];
        $connectionId = $_POST['connectionId'];
        $portCount = $_POST['port_count'];

        // Validate required fields
        if (empty($napboxName) || empty($connectionType) || empty($connectionId) || empty($portCount)) {
            throw new Exception('All fields are required');
        }

        // Validate connection type
        if (!in_array($connectionType, ['OLT', 'LCP'])) {
            throw new Exception('Invalid connection type');
        }

        // Validate port count
        if (!is_numeric($portCount) || $portCount < 1) {
            throw new Exception('Port count must be a positive number');
        }

        // Add NAP box
        $stmt = $conn->prepare("
            INSERT INTO olt_napboxs (
                name, 
                mother_nap_type, 
                mother_nap, 
                port_count
            ) VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $napboxName,
            $connectionType,
            $connectionId,
            $portCount
        ]);

        // Set success message
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'NAP box added successfully'
        ];
        
    } catch (Exception $e) {
        // Set error message
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Error adding NAP box: ' . $e->getMessage()
        ];
    }
}

// Redirect back to the NAP box management page
header('Location: ftth_napbox.php');
exit;
?>
