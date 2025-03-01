<?php
require_once '../config.php';
check_auth();

// Establish database connection
$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $oltId = $_POST['oltId'];
    $oltName = $_POST['oltName'];
    $ponType = $_POST['ponType'];
    $numberOfPons = $_POST['numberOfPons'];
    $txPower = $_POST['txPower'];

    try {
        // Validate input
        if (empty($oltId) || empty($oltName) || empty($ponType) || empty($numberOfPons) || !isset($txPower)) {
            throw new Exception("All fields are required");
        }

        if (!is_numeric($oltId) || $oltId < 1) {
            throw new Exception("Invalid OLT ID");
        }

        if (!in_array($ponType, ['EPON', 'GPON'])) {
            throw new Exception("Invalid PON type");
        }

        if (!is_numeric($numberOfPons) || $numberOfPons < 1) {
            throw new Exception("Number of PONs must be a positive number");
        }

        if (!is_numeric($txPower)) {
            throw new Exception("Tx Power must be a number");
        }

        // Check if OLT exists and get current values
        $checkStmt = $conn->prepare("SELECT * FROM olt_devices WHERE id = ?");
        $checkStmt->execute([$oltId]);
        $existingOlt = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingOlt) {
            throw new Exception("OLT not found");
        }

        // Check if reducing number of PONs would affect existing NAP boxes
        if ($numberOfPons < $existingOlt['number_of_pons']) {
            $checkPortsStmt = $conn->prepare("SELECT MAX(pon_port) as max_port FROM olt_napboxs WHERE olt_id = ?");
            $checkPortsStmt->execute([$oltId]);
            $maxPort = $checkPortsStmt->fetch(PDO::FETCH_ASSOC)['max_port'];

            if ($maxPort > $numberOfPons) {
                throw new Exception("Cannot reduce number of PONs: NAP boxes are using ports up to PON {$maxPort}");
            }
        }

        // Prepare and execute the update statement
        $stmt = $conn->prepare("UPDATE olt_devices SET name = ?, pon_type = ?, number_of_pons = ?, tx_power = ? WHERE id = ?");
        $stmt->bindValue(1, $oltName);
        $stmt->bindValue(2, $ponType);
        $stmt->bindValue(3, (int)$numberOfPons, PDO::PARAM_INT);
        $stmt->bindValue(4, (float)$txPower); // No PDO::PARAM_INT for decimal values
        $stmt->bindValue(5, (int)$oltId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            error_log("OLT {$oltId} updated successfully");
            $_SESSION['success'] = "OLT updated successfully.";
        } else {
            throw new Exception("Database error: " . implode(", ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        error_log("Error updating OLT {$oltId}: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Error updating OLT: " . $e->getMessage();
    }

    header("Location: FTTH.php");
    exit();
}
?>
