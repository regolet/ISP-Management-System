<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        // Start transaction
        $conn->begin_transaction();

        $payment_id = filter_input(INPUT_POST, 'delete_id', FILTER_SANITIZE_NUMBER_INT);
        
        // First get the payment and billing information before deleting
        $get_payment = $conn->prepare("
            SELECT p.*, b.amount as billing_amount, b.id as billing_id,
                   c.id as customer_id, c.credit_balance, c.outstanding_balance
            FROM payments p 
            LEFT JOIN billing b ON p.billing_id = b.id 
            LEFT JOIN customers c ON b.customer_id = c.id
            WHERE p.id = ?
        ");
        $get_payment->bind_param("i", $payment_id);
        $get_payment->execute();
        $payment_info = $get_payment->get_result()->fetch_assoc();

        if (!$payment_info) {
            throw new Exception("Payment not found");
        }

        // Get total paid amount for this billing (excluding this payment)
        $get_total = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_paid
            FROM payments 
            WHERE billing_id = ? AND status = 'completed' AND id != ?
        ");
        $get_total->bind_param("ii", $payment_info['billing_id'], $payment_id);
        $get_total->execute();
        $total_result = $get_total->get_result()->fetch_assoc();
        $total_paid = $total_result['total_paid'];

        // If this was a completed payment that created a credit balance
        if ($payment_info['status'] === 'completed') {
            $overpayment = ($total_paid + $payment_info['amount']) - $payment_info['billing_amount'];
            if ($overpayment > 0) {
                // Reduce customer's credit balance
                $new_credit = max(0, $payment_info['credit_balance'] - $overpayment);
                $update_credit = $conn->prepare("
                    UPDATE customers 
                    SET credit_balance = ?
                    WHERE id = ?
                ");
                $update_credit->bind_param("di", $new_credit, $payment_info['customer_id']);
                if (!$update_credit->execute()) {
                    throw new Exception("Error updating customer credit balance");
                }
            }
        }

        // Delete the payment
        $delete_stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
        $delete_stmt->bind_param("i", $payment_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Error deleting payment");
        }

        // Determine new billing status based on remaining payments
        $new_status = 'unpaid';
        if ($total_paid >= $payment_info['billing_amount']) {
            $new_status = 'paid';
        } elseif ($total_paid > 0) {
            $new_status = 'partial';
        }

        // Update billing status
        $update_billing = $conn->prepare("
            UPDATE billing 
            SET status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update_billing->bind_param("si", $new_status, $payment_info['billing_id']);
        
        if (!$update_billing->execute()) {
            throw new Exception("Error updating billing status");
        }

        // Update customer's outstanding balance
        $get_customer_bills = $conn->prepare("
            SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN status IN ('unpaid', 'partial') 
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
        $get_customer_bills->bind_param("i", $payment_info['customer_id']);
        $get_customer_bills->execute();
        $outstanding_result = $get_customer_bills->get_result()->fetch_assoc();
        $new_outstanding = $outstanding_result['total_outstanding'];

        // Update customer status and outstanding balance
        $update_customer = $conn->prepare("
            UPDATE customers 
            SET status = (
                CASE 
                    WHEN ? = 0 THEN 'paid'
                    WHEN EXISTS (
                        SELECT 1 FROM billing b 
                        WHERE b.customer_id = customers.id 
                        AND b.status = 'partial'
                    ) THEN 'partial'
                    ELSE 'unpaid'
                END
            ),
            outstanding_balance = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update_customer->bind_param("ddi", $new_outstanding, $new_outstanding, $payment_info['customer_id']);
        
        if (!$update_customer->execute()) {
            throw new Exception("Error updating customer status and balance");
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment deleted successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>