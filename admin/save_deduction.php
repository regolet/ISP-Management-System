<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle deduction type save
    if (isset($_POST['save_type'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $calculation_type = filter_input(INPUT_POST, 'calculation_type', FILTER_SANITIZE_STRING);
        $percentage_value = filter_input(INPUT_POST, 'percentage_value', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if ($id) {
            // Update existing deduction type
            $stmt = $conn->prepare("UPDATE deduction_types SET 
                name = ?, description = ?, type = ?, 
                calculation_type = ?, percentage_value = ?
                WHERE id = ?");
            $stmt->bind_param("ssssdi", $name, $description, $type, $calculation_type, $percentage_value, $id);
        } else {
            // Insert new deduction type
            $stmt = $conn->prepare("INSERT INTO deduction_types 
                (name, description, type, calculation_type, percentage_value, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssd", $name, $description, $type, $calculation_type, $percentage_value);
        }

    } else {
        // Handle employee deduction save
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
        $deduction_type_id = filter_input(INPUT_POST, 'deduction_type_id', FILTER_SANITIZE_NUMBER_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $frequency = filter_input(INPUT_POST, 'frequency', FILTER_SANITIZE_STRING);
        $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
        $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING) ?: null;
        $remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING);

        if ($id) {
            // Update existing deduction
            $stmt = $conn->prepare("UPDATE employee_deductions SET 
                employee_id = ?, deduction_type_id = ?, amount = ?, 
                frequency = ?, start_date = ?, end_date = ?, remarks = ?
                WHERE id = ?");
            $stmt->bind_param("iidssssi", $employee_id, $deduction_type_id, $amount, 
                            $frequency, $start_date, $end_date, $remarks, $id);
        } else {
            // Insert new deduction
            $stmt = $conn->prepare("INSERT INTO employee_deductions 
                (employee_id, deduction_type_id, amount, frequency, start_date, end_date, remarks, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("iidssss", $employee_id, $deduction_type_id, $amount, 
                            $frequency, $start_date, $end_date, $remarks);
        }
    }

    if ($stmt->execute()) {
        if (!isset($_POST['id'])) {
            // New deduction - record initial history
            recordHistory($conn->insert_id, $_POST['amount'], 'deduction', 'Initial deduction setup');
        } else {
            // Update - record adjustment if amount changed
            if ($_POST['amount'] != $original_amount) {
                recordHistory($_POST['id'], $_POST['amount'], 'adjustment', 'Amount updated');
            }
        }
        $_SESSION['success'] = "Deduction " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving deduction: " . $conn->error;
    }

    header('Location: deductions.php');
    exit;
}

function recordHistory($deduction_id, $amount, $type = 'deduction', $notes = '') {
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO deduction_history 
        (deduction_id, amount, transaction_type, notes, created_by) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idssi", $deduction_id, $amount, $type, $notes, $user_id);
    return $stmt->execute();
}
?>
