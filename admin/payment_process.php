<?php
require_once '../config.php';
check_auth();

// Check if table needs to be altered
$check_column = $conn->query("SHOW COLUMNS FROM billing WHERE Field = 'status'");
$column_info = $check_column->fetch_assoc();
if ($column_info && strpos($column_info['Type'], 'partial') === false) {
    // Column exists but doesn't have 'partial' status
    $alter_sql = file_get_contents(__DIR__ . '/../sql/alter_billing_table.sql');
    if (!$conn->query($alter_sql)) {
        error_log("Error updating billing table structure: " . $conn->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Get form data
        $billing_id = filter_input(INPUT_POST, 'billing_id', FILTER_SANITIZE_NUMBER_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $payment_method_id = filter_input(INPUT_POST, 'payment_method_id', FILTER_SANITIZE_NUMBER_INT);
        $reference_no = filter_input(INPUT_POST, 'reference_no', FILTER_SANITIZE_STRING);
        $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

        // Validate required fields
        if (!$billing_id || !$amount || !$payment_method_id || !$payment_date) {
            throw new Exception("Required fields are missing");
        }

        // Get billing and customer information
        $get_billing = $conn->prepare("
            SELECT b.*, 
                   c.id as customer_id, 
                   c.credit_balance,
                   c.outstanding_balance
            FROM billing b 
            LEFT JOIN customers c ON b.customer_id = c.id 
            WHERE b.id = ?
        ");
        $get_billing->bind_param("i", $billing_id);
        $get_billing->execute();
        $billing_info = $get_billing->get_result()->fetch_assoc();

        if (!$billing_info) {
            throw new Exception("Billing not found");
        }

        // Insert payment
        $stmt = $conn->prepare("
            INSERT INTO payments (
                billing_id, 
                amount, 
                payment_method_id, 
                reference_no, 
                payment_date, 
                notes, 
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)
        ");
        $stmt->bind_param(
            "idisssi", 
            $billing_id, 
            $amount, 
            $payment_method_id, 
            $reference_no, 
            $payment_date, 
            $notes,
            $_SESSION['user_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating payment: " . $conn->error);
        }

        // Calculate total paid amount INCLUDING the new payment
        $get_total = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_paid 
            FROM payments 
            WHERE billing_id = ? AND status = 'completed'
        ");
        $get_total->bind_param("i", $billing_id);
        $get_total->execute();
        $total_result = $get_total->get_result()->fetch_assoc();
        $total_paid = $total_result['total_paid'];

        // Calculate remaining balance and overpayment
        $remaining_balance = max(0, $billing_info['amount'] - $total_paid);
        $overpayment = max(0, $total_paid - $billing_info['amount']);
        
        // Debug information
        error_log("Payment Process Debug:");
        error_log("Billing ID: " . $billing_id);
        error_log("Total Amount Due: " . $billing_info['amount']);
        error_log("Total Amount Paid: " . $total_paid);
        error_log("Remaining Balance: " . $remaining_balance);
        error_log("Overpayment Amount: " . $overpayment);

        // If there's an overpayment, add it to customer's credit balance
        if ($overpayment > 0) {
            $update_credit = $conn->prepare("
                UPDATE customers 
                SET credit_balance = credit_balance + ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $update_credit->bind_param("di", $overpayment, $billing_info['customer_id']);
            
            if (!$update_credit->execute()) {
                throw new Exception("Error updating customer credit balance");
            }
            error_log("Credit balance updated with overpayment: " . $overpayment);
        }

        // Determine new billing status based on payment amount
        if ($total_paid >= $billing_info['amount']) {
            $new_status = 'paid';
        } elseif ($total_paid > 0 && $total_paid < $billing_info['amount']) {
            $new_status = 'partial';
        } else {
            $new_status = 'unpaid';
        }

        error_log("New Status to be set: " . $new_status);

        // Update billing status and balance
        $update_billing = $conn->prepare("
            UPDATE billing 
            SET status = ?,
                balance = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        if (!$update_billing) {
            throw new Exception("Error preparing billing update statement: " . $conn->error);
        }
        
        $update_billing->bind_param("sdi", $new_status, $remaining_balance, $billing_id);
        
        if (!$update_billing->execute()) {
            error_log("SQL Error in update: " . $conn->error);
            throw new Exception("Error updating billing status: " . $conn->error);
        }

        // Verify the update
        $verify_update = $conn->prepare("
            SELECT status, balance 
            FROM billing 
            WHERE id = ?
        ");
        $verify_update->bind_param("i", $billing_id);
        $verify_update->execute();
        $verification = $verify_update->get_result()->fetch_assoc();
        
        error_log("Verification after update - Status: " . $verification['status'] . ", Balance: " . $verification['balance']);

        // Update customer's outstanding balance
        $get_customer_bills = $conn->prepare("
            SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN status = 'unpaid'
                        THEN amount - COALESCE((
                            SELECT SUM(amount) 
                            FROM payments 
                            WHERE billing_id = b.id 
                            AND status = 'completed'
                        ), 0)
                        ELSE 0 
                    END
                ), 0) as total_outstanding
            FROM billing b 
            WHERE customer_id = ?
        ");
        $get_customer_bills->bind_param("i", $billing_info['customer_id']);
        $get_customer_bills->execute();
        $outstanding_result = $get_customer_bills->get_result()->fetch_assoc();
        $new_outstanding = $outstanding_result['total_outstanding'];

        // Update customer outstanding balance
        $update_customer = $conn->prepare("
            UPDATE customers 
            SET outstanding_balance = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update_customer->bind_param("di", $new_outstanding, $billing_info['customer_id']);
        
        if (!$update_customer->execute()) {
            throw new Exception("Error updating customer balance");
        }

        $conn->commit();
        $_SESSION['success'] = "Payment added successfully";
        header("Location: billing_view.php?id=" . $billing_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: billing_payment_form.php?billing_id=" . $billing_id);
        exit();
    }
} else {
    header('Location: billing.php');
    exit();
}
?>