<?php
require_once '../config.php';
check_auth();

// Get database connection
$conn = get_db_connection();

try {
    $conn->beginTransaction();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $customer_code = $_POST['customer_code'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $plan_id = !empty($_POST['plan_id']) ? $_POST['plan_id'] : null;
    $installation_date = !empty($_POST['installation_date']) ? $_POST['installation_date'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $status = $_POST['status'];
    $service_area_id = !empty($_POST['service_area_id']) ? $_POST['service_area_id'] : null;
    $installation_fee = !empty($_POST['installation_fee']) ? $_POST['installation_fee'] : 0;
    $installation_notes = $_POST['installation_notes'];
    $balance = !empty($_POST['balance']) ? $_POST['balance'] : 0;
    $credit_balance = !empty($_POST['credit_balance']) ? $_POST['credit_balance'] : 0;
    $outstanding_balance = !empty($_POST['outstanding_balance']) ? $_POST['outstanding_balance'] : 0;

    // Check if customer code already exists
    $stmt = $conn->prepare("SELECT id FROM customers WHERE customer_code = ? AND id != ?");
    $stmt->execute([$customer_code, $id]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Customer code already exists.";
        header("Location: " . ($id ? "customer_form.php?id=$id" : "customer_form.php"));
        exit();
    }

    if ($id) {
        // Update existing customer
        $stmt = $conn->prepare("
            UPDATE customers 
            SET customer_code = ?, 
                name = ?, 
                address = ?, 
                contact = ?,
                contact_number = ?,
                email = ?, 
                plan_id = ?, 
                installation_date = ?,
                due_date = ?, 
                status = ?,
                service_area_id = ?,
                installation_fee = ?,
                installation_notes = ?,
                balance = ?,
                credit_balance = ?,
                outstanding_balance = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $customer_code,
            $name,
            $address,
            $contact,
            $contact_number,
            $email,
            $plan_id,
            $installation_date,
            $due_date,
            $status,
            $service_area_id,
            $installation_fee,
            $installation_notes,
            $balance,
            $credit_balance,
            $outstanding_balance,
            $id
        ]);

        $_SESSION['success'] = "Customer updated successfully.";
    } else {
        // Insert new customer
        $stmt = $conn->prepare("
            INSERT INTO customers (
                customer_code, name, address, contact, contact_number, email, 
                plan_id, installation_date, due_date, status, service_area_id,
                installation_fee, installation_notes, balance, credit_balance,
                outstanding_balance, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $customer_code,
            $name,
            $address,
            $contact,
            $contact_number,
            $email,
            $plan_id,
            $installation_date,
            $due_date,
            $status,
            $service_area_id,
            $installation_fee,
            $installation_notes,
            $balance,
            $credit_balance,
            $outstanding_balance
        ]);

        $_SESSION['success'] = "Customer added successfully.";
    }

    $conn->commit();
    header("Location: customers.php");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Error saving customer: " . $e->getMessage();
    header("Location: " . ($id ? "customer_form.php?id=$id" : "customer_form.php"));
    exit();
}
?>
