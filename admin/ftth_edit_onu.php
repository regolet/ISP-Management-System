<?php
require_once '../config.php';
check_auth();

// Get database connection
$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $napbox_id = $_POST['napbox_id'];
    $port_number = $_POST['port_number'];
    $serial_number = $_POST['serial_number'];
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $signal_level = !empty($_POST['signal_level']) ? $_POST['signal_level'] : null;
    $status = $_POST['status'];

    // Validate port number against NAP box port count
    $stmt = $conn->prepare("SELECT port_count FROM olt_napboxs WHERE id = ?");
    $stmt->execute([$napbox_id]);
    $napbox = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($port_number > $napbox['port_count']) {
        $_SESSION['error'] = "Port number exceeds NAP box capacity.";
        header("Location: ftth_view_onus.php?id=" . $napbox_id);
        exit();
    }

    // Check if port is already in use by another ONU
    $stmt = $conn->prepare("SELECT id FROM customer_onus WHERE napbox_id = ? AND port_number = ? AND id != ?");
    $stmt->execute([$napbox_id, $port_number, $id]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Port number is already in use.";
        header("Location: ftth_view_onus.php?id=" . $napbox_id);
        exit();
    }

    // Check if serial number is already in use by another ONU
    $stmt = $conn->prepare("SELECT id FROM customer_onus WHERE serial_number = ? AND id != ?");
    $stmt->execute([$serial_number, $id]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Serial number is already in use.";
        header("Location: ftth_view_onus.php?id=" . $napbox_id);
        exit();
    }

    // Update ONU
    $stmt = $conn->prepare("UPDATE customer_onus SET port_number = ?, serial_number = ?, customer_id = ?, signal_level = ?, status = ? WHERE id = ?");
    
    if ($stmt->execute([$port_number, $serial_number, $customer_id, $signal_level, $status, $id])) {
        $_SESSION['success'] = "ONU updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating ONU: " . implode(", ", $stmt->errorInfo());
    }
}

header("Location: ftth_view_onus.php?id=" . $napbox_id);
exit();
?>
