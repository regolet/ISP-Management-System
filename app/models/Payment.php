<?php
namespace App\Models;

use PDO;
use DateTime;

class Payment {
    // Database connection and table name
    private $conn;
    private $table_name = "payments";

    // Object properties
    public $id;
    public $payment_number;
    public $billing_id;
    public $amount;
    public $payment_method;
    public $payment_date;
    public $transaction_id;
    public $status;
    public $notes;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all payments with optional filtering
     */
    public function getAll($page = 1, $per_page = 10, $search = '', $status = '', $sort = 'id', $order = 'DESC') {
        // Prepare query
        $query = "SELECT p.*, b.invoice_number, c.first_name, c.last_name, c.email
                FROM " . $this->table_name . " p
                JOIN billing b ON p.billing_id = b.id
                JOIN client_subscriptions cs ON b.id = cs.id -- Assuming billing.id relates to client_subscriptions.id
                JOIN clients c ON cs.client_id = c.id
                WHERE 1=1";

        // Apply search filter
        if (!empty($search)) {
            $query .= " AND (
                p.payment_number LIKE :search
                OR p.transaction_id LIKE :search
                OR c.first_name LIKE :search
                OR c.last_name LIKE :search
                OR c.email LIKE :search
                OR b.invoice_number LIKE :search
            )";
        }

        // Apply status filter
        if (!empty($status)) {
            $query .= " AND p.status = :status";
        }

        // Apply sorting
        $allowed_sort_fields = ['id', 'payment_number', 'amount', 'payment_date', 'status'];
        $sort = in_array($sort, $allowed_sort_fields) ? $sort : 'payment_date';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query .= " ORDER BY p." . $sort . " " . $order;

        // Apply pagination
        $offset = ($page - 1) * $per_page;
        $query .= " LIMIT :offset, :per_page";

        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }

        if (!empty($status)) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of payments with filters
     */
    public function getTotal($search = '', $status = '') {
        // Prepare query
        $query = "SELECT COUNT(*) as total
                FROM " . $this->table_name . " p
                JOIN billing b ON p.billing_id = b.id
                JOIN client_subscriptions cs ON b.id = cs.id -- Assuming billing.id relates to client_subscriptions.id
                JOIN clients c ON cs.client_id = c.id
                WHERE 1=1";

        // Apply search filter
        if (!empty($search)) {
            $query .= " AND (
                p.payment_number LIKE :search
                OR p.transaction_id LIKE :search
                OR c.first_name LIKE :search
                OR c.last_name LIKE :search
                OR c.email LIKE :search
                OR b.invoice_number LIKE :search
            )";
        }

        // Apply status filter
        if (!empty($status)) {
            $query .= " AND p.status = :status";
        }

        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }

        if (!empty($status)) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Get single payment by ID
     */
    public function getById($id) {
        // Prepare query
        $query = "SELECT p.*, b.invoice_number, b.client_id, c.first_name, c.last_name
                FROM " . $this->table_name . " p
                JOIN billing b ON p.billing_id = b.id
                JOIN clients c ON b.client_id = c.id
                WHERE p.id = :id
                LIMIT 0,1";

        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new payment
     */
    public function create() {
        try {
            // Generate payment number
            $this->payment_number = $this->generatePaymentNumber();

            // Prepare query
            $query = "INSERT INTO " . $this->table_name . "
                    (payment_number, billing_id, amount, payment_method, payment_date, transaction_id, status, notes)
                    VALUES
                    (:payment_number, :billing_id, :amount, :payment_method, :payment_date, :transaction_id, :status, :notes)";

            // Prepare statement
            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->payment_number = htmlspecialchars(strip_tags($this->payment_number));
            $this->billing_id = htmlspecialchars(strip_tags($this->billing_id));
            $this->amount = htmlspecialchars(strip_tags($this->amount));
            $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
            $this->status = htmlspecialchars(strip_tags($this->status));
            
            // Transaction ID can be null
            if ($this->transaction_id !== null) {
                $this->transaction_id = htmlspecialchars(strip_tags($this->transaction_id));
            }
            
            // Notes can be null
            if ($this->notes !== null) {
                $this->notes = htmlspecialchars(strip_tags($this->notes));
            }

            // If payment_date is not set, use current datetime
            if (empty($this->payment_date)) {
                $this->payment_date = date('Y-m-d H:i:s');
            }

            // Bind parameters
            $stmt->bindParam(':payment_number', $this->payment_number);
            $stmt->bindParam(':billing_id', $this->billing_id);
            $stmt->bindParam(':amount', $this->amount);
            $stmt->bindParam(':payment_method', $this->payment_method);
            $stmt->bindParam(':payment_date', $this->payment_date);
            $stmt->bindParam(':transaction_id', $this->transaction_id);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':notes', $this->notes);

            // Execute statement
            if ($stmt->execute()) {
                // Get the last inserted ID
                $this->id = $this->conn->lastInsertId();

                // Update invoice status if payment amount equals invoice amount
                $this->updateInvoiceStatus();

                return true;
            }

            return false;
        } catch (PDOException $e) {
            // Log the error to a file instead of sending to output
            error_log('Payment::create PDOException: ' . $e->getMessage());
            throw new Exception('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Payment::create Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing payment
     */
    public function update() {
        // Prepare query
        $query = "UPDATE " . $this->table_name . "
                SET
                    amount = :amount,
                    payment_method = :payment_method,
                    payment_date = :payment_date,
                    transaction_id = :transaction_id,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE
                    id = :id";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind parameters
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->transaction_id = htmlspecialchars(strip_tags($this->transaction_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':transaction_id', $this->transaction_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':id', $this->id);

        // Execute statement
        if ($stmt->execute()) {
            // Update invoice status if payment status changed
            $this->updateInvoiceStatus();
            return true;
        }

        return false;
    }

    /**
     * Delete payment
     */
    public function delete($id) {
        // First get the payment to update invoice status after
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Prepare delete query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        // Execute statement
        if ($stmt->execute()) {
            // Update invoice status after payment deletion
            if ($payment) {
                $this->billing_id = $payment['billing_id'];
                $this->updateInvoiceStatus();
            }
            return true;
        }

        return false;
    }

    /**
     * Generate unique payment number
     */
    private function generatePaymentNumber() {
        $prefix = "PAY";
        $year = date('Y');
        $month = date('m');

        $query = "SELECT MAX(CAST(SUBSTRING(payment_number, " . (strlen($prefix) + strlen($year) + strlen($month) + 1) . ") AS UNSIGNED)) as max_num
                 FROM " . $this->table_name . "
                 WHERE payment_number LIKE :search_prefix";
        
        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . $year . $month . '%';
        $stmt->bindParam(':search_prefix', $searchPrefix);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($row['max_num'] ?? 0) + 1;
        
        return $prefix . $year . $month . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update invoice status based on payments
     */
    private function updateInvoiceStatus() {
        if (!$this->billing_id) {
            return false;
        }

        // Get invoice details
        $query = "SELECT * FROM billing WHERE id = :billing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':billing_id', $this->billing_id);
        $stmt->execute();
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return false;
        }

        // Get total payments for this invoice
        $query = "SELECT SUM(amount) as paid_amount
                 FROM " . $this->table_name . "
                 WHERE billing_id = :billing_id
                 AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':billing_id', $this->billing_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $paidAmount = $result['paid_amount'] ?? 0;

        // Determine new status
        $newStatus = $invoice['status'];
        if ($paidAmount >= $invoice['total_amount']) {
            $newStatus = 'paid';
        } else if ($paidAmount > 0) {
            $newStatus = 'pending'; // Partial payment
        } else {
            // Check if overdue
            $dueDate = new DateTime($invoice['due_date']);
            $today = new DateTime();
            if ($today > $dueDate && $invoice['status'] !== 'cancelled') {
                $newStatus = 'overdue';
            }
        }

        // Update invoice status if changed
        if ($newStatus !== $invoice['status']) {
            $query = "UPDATE billing
                     SET status = :status,
                         updated_at = NOW()
                     WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $newStatus);
            $stmt->bindParam(':id', $this->billing_id);
            return $stmt->execute();
        }

        return true;
    }

    /**
     * Get payments for specific invoice
     */
    public function getPaymentsByInvoice($billing_id) {
        // Prepare query
        $query = "SELECT *
                FROM " . $this->table_name . "
                WHERE billing_id = :billing_id
                ORDER BY payment_date DESC";

        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':billing_id', $billing_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
