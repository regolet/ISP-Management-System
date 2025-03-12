<?php
// This is a test comment to check if writing to the file works.
namespace App\Controllers;

require_once dirname(__DIR__) . '/Models/Billing.php';
require_once dirname(__DIR__) . '/Models/Payment.php';
require_once __DIR__ . '/AuthController.php'; // Include AuthController

class BillingController {
    private $db;
    private $billing;
    private $payment;
    private $auth;

    public function __construct($db) {
        $this->db = $db;
        $this->billing = new \App\Models\Billing($db);
        $this->payment = new \App\Models\Payment($db);
        $this->auth = new \App\Controllers\AuthController(); // Using fully qualified namespace
    }

    /**
     * Get all invoices with optional filtering and pagination
     */
    public function getInvoices($params = []) {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'ASC';

        $invoices = $this->billing->getAll($page, $per_page, $search, $status, $sort, $order);
        $total = $this->billing->getTotal($search, $status);

        return [
            'data' => $invoices,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    /**
     * Get single invoice by ID with related data
     */
    public function getInvoice($id) {
        $invoiceData = $this->billing->getById($id);
        if (!$invoiceData) {
            return null;
        }

        // Get payments for this invoice
        $payments = $this->billing->getPayments($id);

        return [
            'invoice' => $invoiceData,
            'payments' => $payments
        ];
    }

    /**
     * Create new invoice
     */
    public function createInvoice($data) {
        try {
            $this->validateInvoiceData($data);

            foreach ($data as $key => $value) {
                if (property_exists($this->billing, $key)) {
                    $this->billing->$key = $value;
                }
            }

            // Set default values if not provided
            $this->billing->status = $data['status'] ?? 'pending';
            $this->billing->billing_date = $data['billing_date'] ?? date('Y-m-d');
            $this->billing->due_date = $data['due_date'] ?? date('Y-m-d', strtotime('+14 days'));

            if ($this->billing->create()) {
                // Log activity
                $this->logActivity(
                    'invoice_created',
                    "New invoice created: {$this->billing->invoice_number}",
                    $data['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'invoice_id' => $this->billing->id
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create invoice'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing invoice
     */
    public function updateInvoice($id, $data) {
        try {
            $this->validateInvoiceData($data, true);

            $this->billing->id = $id;
            foreach ($data as $key => $value) {
                if (property_exists($this->billing, $key)) {
                    $this->billing->$key = $value;
                }
            }

            if ($this->billing->update()) {
                // Log activity
                $this->logActivity(
                    'invoice_updated',
                    "Invoice updated: {$this->billing->invoice_number}",
                    $data['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Invoice updated successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update invoice'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice($id) {
        try {
            $invoiceData = $this->billing->getById($id);
            if (!$invoiceData) {
                throw new \Exception('Invoice not found');
            }

            if ($this->billing->delete($id)) {
                // Log activity
                $this->logActivity(
                    'invoice_deleted',
                    "Invoice deleted: {$invoiceData['invoice_number']}",
                    $invoiceData['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Invoice deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete invoice'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all payments with optional filtering and pagination
     */
    public function getPayments($params = []) {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'ASC';

        $payments = $this->payment->getAll($page, $per_page, $search, $status, $sort, $order);
        $total = $this->payment->getTotal($search, $status);

        return [
            'data' => $payments,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    /**
     * Get single payment by ID
     */
    public function getPayment($id) {
        return $this->payment->getById($id);
    }

    /**
     * Create new payment
     */
    public function createPayment($data) {
        try {
            $this->validatePaymentData($data);

            foreach ($data as $key => $value) {
                if (property_exists($this->payment, $key)) {
                    $this->payment->$key = $value;
                }
            }

            // Set default values if not provided
            $this->payment->status = $data['status'] ?? 'pending';
            $this->payment->payment_date = $data['payment_date'] ?? date('Y-m-d H:i:s');

            if ($this->payment->create()) {
                // Get client ID from billing
                $invoice = $this->billing->getById($data['billing_id']);

                // Log activity
                $this->logActivity(
                    'payment_created',
                    "New payment recorded: {$this->payment->payment_number}",
                    $invoice['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Payment recorded successfully',
                    'payment_id' => $this->payment->id
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to record payment'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing payment
     */
    public function updatePayment($id, $data) {
        try {
            $this->validatePaymentData($data, true);

            $this->payment->id = $id;
            foreach ($data as $key => $value) {
                if (property_exists($this->payment, $key)) {
                    $this->payment->$key = $value;
                }
            }

            if ($this->payment->update()) {
                // Get client ID from billing
                $invoice = $this->billing->getById($data['billing_id']);

                // Log activity
                $this->logActivity(
                    'payment_updated',
                    "Payment updated: {$this->payment->payment_number}",
                    $invoice['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Payment updated successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update payment'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete payment
     */
    public function deletePayment($id) {
        try {
            $paymentData = $this->payment->getById($id);
            if (!$paymentData) {
                throw new \Exception('Payment not found');
            }

            if ($this->payment->delete($id)) {
                // Get client ID from billing
                $invoice = $this->billing->getById($paymentData['billing_id']);

                // Log activity
                $this->logActivity(
                    'payment_deleted',
                    "Payment deleted: {$paymentData['payment_number']}",
                    $invoice['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Payment deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete payment'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate monthly invoices
     */
    public function generateMonthlyInvoices() {
        try {
            $count = $this->billing->generateInvoices();
            return [
                'success' => true,
                'message' => "{$count} invoices generated successfully"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate invoices
     */
    public function generateInvoices() {
        try {
            $result = $this->billing->generateInvoices();
            return [
                'success' => true,
                'generated' => $result['generated'] ?? 0,
                'total_amount' => $result['total_amount'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update overdue invoices
     */
    public function updateOverdueInvoices() {
        try {
            $this->billing->updateOverdueInvoices();
            return [
                'success' => true,
                'message' => 'Overdue invoices updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get billing statistics
     */
    public function getBillingStats() {
        return $this->billing->getStats();
    }


    /**
     * Validate invoice data
     */
    private function validateInvoiceData($data, $isUpdate = false) {
        $errors = [];

        if (!$isUpdate) {
            if (empty($data['client_id']) || !is_numeric($data['client_id'])) {
                $errors[] = 'Valid client ID is required';
            }

            if (empty($data['subscription_id']) || !is_numeric($data['subscription_id'])) {
                $errors[] = 'Valid subscription ID is required';
            }
        }

        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = 'Valid amount is required';
        }

        if (!empty($data['tax']) && !is_numeric($data['tax'])) {
            $errors[] = 'Tax must be a valid number';
        }

        if (empty($data['total_amount']) || !is_numeric($data['total_amount'])) {
            $errors[] = 'Valid total amount is required';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['pending', 'paid', 'overdue', 'cancelled'])) {
            $errors[] = 'Invalid status value';
        }

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData($data, $isUpdate = false) {
        $errors = [];

        if (!$isUpdate) {
            if (empty($data['billing_id']) || !is_numeric($data['billing_id'])) {
                $errors[] = 'Valid billing ID is required';
            }
        }

        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = 'Valid amount is required';
        }

        if (!empty($data['payment_method']) && !in_array($data['payment_method'], ['cash', 'credit_card', 'bank_transfer', 'online'])) {
            $errors[] = 'Invalid payment method';
        }

        // Credit card and bank transfer should have transaction ID
        if (in_array($data['payment_method'], ['credit_card', 'bank_transfer']) && empty($data['transaction_id'])) {
            $errors[] = 'Transaction ID is required for ' . str_replace('_', ' ', $data['payment_method']) . ' payments';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['pending', 'completed', 'failed', 'refunded'])) {
            $errors[] = 'Invalid status value';
        }

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }

    /**
     * Log activity
     */
    private function logActivity($type, $description, $client_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs
                (user_id, client_id, activity_type, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $client_id,
                $type,
                $description,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
