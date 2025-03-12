<?php
require_once dirname(__DIR__) . '/includes/BillingUtils.php';

class PaymentProcessor {
    private $db;
    private $utils;
    private $config;
    private $logFile;

    public function __construct($db) {
        $this->db = $db;
        $this->utils = new BillingUtils($db);
        $this->config = require dirname(__DIR__) . '/config/billing.php';
        $this->logFile = dirname(__DIR__) . '/logs/payments.log';

        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Process payment
     */
    public function processPayment($data) {
        $this->log("Processing payment for invoice #{$data['invoice_id']}");

        try {
            // Validate payment data
            $this->validatePaymentData($data);

            // Start transaction
            $this->db->beginTransaction();

            // Get invoice details
            $invoice = $this->getInvoice($data['invoice_id']);
            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            // Verify payment amount
            if ($data['amount'] > $invoice['total_amount']) {
                throw new \Exception('Payment amount exceeds invoice amount');
            }

            // Process payment through gateway
            $paymentResult = $this->processPaymentGateway($data);

            if ($paymentResult['success']) {
                // Create payment record
                $paymentId = $this->createPaymentRecord($data, $paymentResult);

                // Update invoice status
                $this->updateInvoiceStatus($invoice['id'], $data['amount']);

                // Create activity log
                $this->logActivity($invoice['client_id'], 'payment_received', [
                    'invoice_id' => $invoice['id'],
                    'payment_id' => $paymentId,
                    'amount' => $data['amount']
                ]);

                $this->db->commit();

                $this->log("Payment processed successfully: {$paymentResult['transaction_id']}");

                return [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'transaction_id' => $paymentResult['transaction_id']
                ];
            } else {
                throw new \Exception($paymentResult['message']);
            }

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log("Payment processing failed: " . $e->getMessage(), 'ERROR');
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process refund
     */
    public function processRefund($paymentId, $reason) {
        $this->log("Processing refund for payment #$paymentId");

        try {
            // Start transaction
            $this->db->beginTransaction();

            // Get payment details
            $payment = $this->getPayment($paymentId);
            if (!$payment) {
                throw new \Exception('Payment not found');
            }

            if ($payment['status'] !== 'completed') {
                throw new \Exception('Only completed payments can be refunded');
            }

            // Process refund through gateway
            $refundResult = $this->processRefundGateway($payment);

            if ($refundResult['success']) {
                // Update payment status
                $this->updatePaymentStatus($paymentId, 'refunded', [
                    'refund_reason' => $reason,
                    'refund_date' => date('Y-m-d H:i:s'),
                    'refund_transaction_id' => $refundResult['transaction_id']
                ]);

                // Update invoice status
                $this->updateInvoiceAfterRefund($payment['billing_id']);

                // Create activity log
                $this->logActivity($payment['client_id'], 'payment_refunded', [
                    'payment_id' => $paymentId,
                    'amount' => $payment['amount'],
                    'reason' => $reason
                ]);

                $this->db->commit();

                $this->log("Refund processed successfully: {$refundResult['transaction_id']}");

                return [
                    'success' => true,
                    'refund_id' => $refundResult['refund_id']
                ];
            } else {
                throw new \Exception($refundResult['message']);
            }

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log("Refund processing failed: " . $e->getMessage(), 'ERROR');
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment through gateway
     */
    private function processPaymentGateway($data) {
        $gateway = $this->config['integrations']['payment_gateway']['provider'];
        
        switch ($gateway) {
            case 'stripe':
                return $this->processStripePayment($data);
            
            case 'paypal':
                return $this->processPayPalPayment($data);
            
            default:
                // For testing/development, simulate successful payment
                return [
                    'success' => true,
                    'transaction_id' => 'TEST_' . uniqid(),
                    'message' => 'Test payment processed'
                ];
        }
    }

    /**
     * Process refund through gateway
     */
    private function processRefundGateway($payment) {
        $gateway = $this->config['integrations']['payment_gateway']['provider'];
        
        switch ($gateway) {
            case 'stripe':
                return $this->processStripeRefund($payment);
            
            case 'paypal':
                return $this->processPayPalRefund($payment);
            
            default:
                // For testing/development, simulate successful refund
                return [
                    'success' => true,
                    'refund_id' => 'REFUND_' . uniqid(),
                    'transaction_id' => 'TEST_' . uniqid(),
                    'message' => 'Test refund processed'
                ];
        }
    }

    /**
     * Create payment record
     */
    private function createPaymentRecord($data, $paymentResult) {
        $stmt = $this->db->prepare("
            INSERT INTO payments (
                payment_number, billing_id, amount, payment_method,
                payment_date, transaction_id, status, notes
            ) VALUES (
                ?, ?, ?, ?, NOW(), ?, 'completed', ?
            )
        ");

        $paymentNumber = $this->generatePaymentNumber();
        
        $stmt->execute([
            $paymentNumber,
            $data['invoice_id'],
            $data['amount'],
            $data['payment_method'],
            $paymentResult['transaction_id'],
            $data['notes'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update invoice status
     */
    private function updateInvoiceStatus($invoiceId, $paymentAmount) {
        // Get invoice and total paid amount
        $stmt = $this->db->prepare("
            SELECT b.*, 
                   (SELECT COALESCE(SUM(amount), 0)
                    FROM payments
                    WHERE billing_id = b.id
                    AND status = 'completed') as total_paid
            FROM billing b
            WHERE b.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate new total paid
        $totalPaid = $invoice['total_paid'] + $paymentAmount;

        // Determine new status
        $newStatus = $totalPaid >= $invoice['total_amount'] ? 'paid' : 'pending';

        // Update invoice
        $stmt = $this->db->prepare("
            UPDATE billing
            SET status = ?,
                paid_amount = ?,
                payment_date = CASE WHEN ? = 'paid' THEN NOW() ELSE NULL END
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $totalPaid, $newStatus, $invoiceId]);
    }

    /**
     * Update invoice after refund
     */
    private function updateInvoiceAfterRefund($invoiceId) {
        // Get total paid amount excluding refunded payments
        $stmt = $this->db->prepare("
            SELECT b.*, 
                   (SELECT COALESCE(SUM(amount), 0)
                    FROM payments
                    WHERE billing_id = b.id
                    AND status = 'completed') as total_paid
            FROM billing b
            WHERE b.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        // Determine new status
        $newStatus = $invoice['total_paid'] >= $invoice['total_amount'] ? 'paid' : 'pending';

        // Update invoice
        $stmt = $this->db->prepare("
            UPDATE billing
            SET status = ?,
                paid_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $invoice['total_paid'], $invoiceId]);
    }

    /**
     * Generate unique payment number
     */
    private function generatePaymentNumber() {
        $prefix = $this->config['payment']['receipt_prefix'];
        $year = date('Y');
        
        $stmt = $this->db->prepare("
            SELECT MAX(CAST(SUBSTRING(payment_number, ?) AS UNSIGNED)) as max_num 
            FROM payments 
            WHERE payment_number LIKE ?
        ");
        
        $prefixLength = strlen($prefix) + strlen($year);
        $searchPrefix = $prefix . $year . '%';
        $stmt->execute([$prefixLength + 1, $searchPrefix]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($row['max_num'] ?? 0) + 1;
        
        return $prefix . $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData($data) {
        if (empty($data['invoice_id'])) {
            throw new \Exception('Invoice ID is required');
        }

        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            throw new \Exception('Valid payment amount is required');
        }

        if ($data['amount'] < $this->config['payment']['minimum_amount']) {
            throw new \Exception('Payment amount is below minimum allowed');
        }

        if (empty($data['payment_method'])) {
            throw new \Exception('Payment method is required');
        }

        $method = $this->utils->getPaymentMethod($data['payment_method']);
        if (!$method['enabled']) {
            throw new \Exception('Payment method is not enabled');
        }
    }

    /**
     * Get invoice details
     */
    private function getInvoice($id) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM billing
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get payment details
     */
    private function getPayment($id) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM payments
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update payment status
     */
    private function updatePaymentStatus($id, $status, $data = []) {
        $sql = "UPDATE payments SET status = ?";
        $params = [$status];

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $sql .= ", $key = ?";
                $params[] = $value;
            }
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Log activity
     */
    private function logActivity($clientId, $type, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs 
            (user_id, client_id, activity_type, description, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $clientId,
            $type,
            json_encode($data),
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    /**
     * Log message with enhanced details
     */
    private function log($message, $level = 'INFO', $context = []) {
        // Add request ID for tracking related log entries
        $requestId = $_SESSION['request_id'] ?? uniqid('req_');
        
        // Convert context array to JSON if present
        $contextStr = !empty($context) ? ' - Context: ' . json_encode($context) : '';
        
        $logMessage = sprintf(
            "[%s] [%s] [%s] %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            $requestId,
            $message,
            $contextStr
        );
        
        // Ensure log directory exists
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
        
        // Append to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // For critical errors, also log to PHP error log
        if ($level === 'ERROR' || $level === 'CRITICAL') {
            error_log("Payment Processor: $message$contextStr");
        }
    }
}
