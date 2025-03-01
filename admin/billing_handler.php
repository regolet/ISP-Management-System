<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header('Location: billing.php');
    exit;
}

try {
    // Store form data in session for persistence on error
    $_SESSION['billing_form_data'] = $_POST;
    
    // Validate inputs
    $errors = [];
    $required_fields = [
        'customer_id' => 'Customer',
        'invoiceid' => 'Invoice ID',
        'due_date' => 'Due Date',
        'billtocustomer' => 'Bill To Customer',
        'billingaddress' => 'Billing Address'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = "$label is required";
        }
    }

    // Validate items
    if (empty($_POST['items']) || !is_array($_POST['items'])) {
        $errors[] = "At least one billing item is required";
    } else {
        $hasValidItems = false;
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description']) && 
                isset($item['qty']) && floatval($item['qty']) > 0 && 
                isset($item['price']) && floatval($item['price']) >= 0) {
                $hasValidItems = true;
                break;
            }
        }
        if (!$hasValidItems) {
            $errors[] = "At least one valid billing item is required";
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode("<br>", $errors));
    }

    $conn->begin_transaction();

    $is_edit = !empty($_POST['billing_id']);
    $billing_id = $is_edit ? clean_input($_POST['billing_id']) : null;

    // Prepare billing data
    $billing_data = [
        'customer_id' => clean_input($_POST['customer_id']),
        'invoiceid' => clean_input($_POST['invoiceid']),
        'amount' => clean_input($_POST['amount']),
        'status' => clean_input($_POST['status']),
        'due_date' => clean_input($_POST['due_date']),
        'billtocustomer' => clean_input($_POST['billtocustomer']),
        'billingaddress' => clean_input($_POST['billingaddress']),
        'discount' => clean_input($_POST['discount']) ?: 0,
        'companyname' => clean_input($_POST['companyname']),
        'companyaddress' => clean_input($_POST['companyaddress'])
    ];

    if ($is_edit) {
        // Update existing bill
        $sql = "UPDATE billing SET 
                customer_id = ?, invoiceid = ?, amount = ?, status = ?, 
                due_date = ?, billtocustomer = ?, billingaddress = ?, 
                discount = ?, companyname = ?, companyaddress = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $params = array_values($billing_data);
        $params[] = $billing_id;
        $stmt->bind_param("isdssssdssi", ...$params);
        
        // Delete existing items
        $conn->query("DELETE FROM billingitems WHERE billingid = " . $billing_id);
    } else {
        // Create new bill
        $sql = "INSERT INTO billing (
                customer_id, invoiceid, amount, status, due_date,
                billtocustomer, billingaddress, discount, companyname, companyaddress
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $params = array_values($billing_data);
        $stmt->bind_param("isdssssdss", ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error saving bill: " . $conn->error);
    }

    $billing_id = $is_edit ? $billing_id : $conn->insert_id;

    // Insert billing items
    $stmt = $conn->prepare("INSERT INTO billingitems (
        billingid, itemdescription, qty, price, totalprice
    ) VALUES (?, ?, ?, ?, ?)");

    foreach ($_POST['items'] as $item) {
        if (empty($item['description'])) continue;
        
        $qty = floatval($item['qty']);
        $price = floatval($item['price']);
        $total = $qty * $price;
        
        $stmt->bind_param("isidd", 
            $billing_id,
            $item['description'],
            $qty,
            $price,
            $total
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving billing item: " . $conn->error);
        }
    }

    $conn->commit();
    unset($_SESSION['billing_form_data']); // Clear stored form data
    
    $_SESSION['success'] = ($is_edit ? "Bill updated" : "Bill created") . " successfully";
    header("Location: billing.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: billing_form.php" . ($is_edit ? "?id=$billing_id" : ""));
    exit;
}
?>
