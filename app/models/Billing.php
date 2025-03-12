<?php
namespace App\Models;

use PDO;

class Billing {
    // Database connection and table name
    private $conn;
    private $table_name = "billing";

    // Object properties
    public $id;
    public $invoice_number;
    public $subscription_id;
    public $amount;
    public $total_amount;
    public $billing_date;
    public $due_date;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all invoices with optional filtering
     */
    public function getAll($page = 1, $per_page = 10, $search = '', $status = '', $sort = 'billing_date', $order = 'DESC') {
        // Query to get invoices with subscription information
        $query = "SELECT b.*, cs.subscription_number, 
                         p.name as plan_name, p.speed_mbps
                  FROM " . $this->table_name . " b
                  JOIN client_subscriptions cs ON b.subscription_id = cs.id
                  LEFT JOIN plans p ON cs.plan_id = p.id
                  WHERE 1=1";

        // Apply search filter if provided
        if (!empty($search)) {
            $query .= " AND (b.invoice_number LIKE :search OR cs.subscription_number LIKE :search)";
        }

        // Apply status filter if provided
        if (!empty($status)) {
            $query .= " AND b.status = :status";
        }

        // Apply sorting
        $allowed_sort_fields = ['id', 'invoice_number', 'billing_date', 'due_date', 'amount', 'status'];
        $sort = in_array($sort, $allowed_sort_fields) ? $sort : 'billing_date';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query .= " ORDER BY b." . $sort . " " . $order;

        // Apply pagination
        $offset = ($page - 1) * $per_page;
        $query .= " LIMIT :offset, :per_page";

        // Prepare statement
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
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of records with filters
     */
    public function getTotal($search = '', $status = '') {
        // Query to count total records
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " b
                  JOIN client_subscriptions cs ON b.subscription_id = cs.id
                  WHERE 1=1";
        
        // Apply search filter if provided
        if (!empty($search)) {
            $query .= " AND (b.invoice_number LIKE :search OR cs.subscription_number LIKE :search)";
        }
        
        // Apply status filter if provided
        if (!empty($status)) {
            $query .= " AND b.status = :status";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }
        
        if (!empty($status)) {
            $stmt->bindParam(':status', $status);
        }
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ?? 0;
    }

    /**
     * Get billing record by ID
     */
    public function getById($id) {
        // Query to get billing record details with subscription info
        $query = "SELECT b.*,
                         cs.subscription_number, p.name as plan_name, p.speed_mbps, p.data_cap_gb
                  FROM " . $this->table_name . " b
                  JOIN client_subscriptions cs ON b.subscription_id = cs.id
                  LEFT JOIN plans p ON cs.plan_id = p.id
                  WHERE b.id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch result
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new billing record
     */
    public function create() {
        // Generate invoice number if not provided
        if (empty($this->invoice_number)) {
            $this->invoice_number = $this->generateInvoiceNumber();
        }
        
        // Query to insert billing record
        $query = "INSERT INTO " . $this->table_name . "
                  (invoice_number, subscription_id, amount, total_amount, billing_date, due_date, status)
                  VALUES
                  (:invoice_number, :subscription_id, :amount, :total_amount, 
                   :billing_date, :due_date, :status)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind parameters
        $this->invoice_number = htmlspecialchars(strip_tags($this->invoice_number));
        $this->subscription_id = htmlspecialchars(strip_tags($this->subscription_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->billing_date = htmlspecialchars(strip_tags($this->billing_date));
        $this->due_date = htmlspecialchars(strip_tags($this->due_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        $stmt->bindParam(':invoice_number', $this->invoice_number);
        $stmt->bindParam(':subscription_id', $this->subscription_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':billing_date', $this->billing_date);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':status', $this->status);
        
        // Execute statement
        if ($stmt->execute()) {
            // Get the last inserted ID
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Update billing record
     */
    public function update() {
        // Query to update billing record
        $query = "UPDATE " . $this->table_name . "
                  SET amount = :amount,
                      total_amount = :total_amount,
                      billing_date = :billing_date,
                      due_date = :due_date,
                      status = :status,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind parameters
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->billing_date = htmlspecialchars(strip_tags($this->billing_date));
        $this->due_date = htmlspecialchars(strip_tags($this->due_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':billing_date', $this->billing_date);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        // Execute statement
        return $stmt->execute();
    }

    /**
     * Delete billing record
     */
    public function delete($id) {
        // Query to delete billing record
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $id);
        
        // Execute statement
        return $stmt->execute();
    }

    /**
     * Get payments for specific invoice
     */
    public function getPayments($billing_id) {
        // Query to get payments for an invoice
        $query = "SELECT * FROM payments WHERE billing_id = :billing_id ORDER BY payment_date DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':billing_id', $billing_id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate monthly invoices for active subscriptions
     */
    public function generateInvoices() {
        try {
            // Begin transaction
            $this->conn->beginTransaction();
            
            // Get active subscriptions eligible for billing
            $query = "SELECT cs.id as subscription_id, p.price, p.name
                      FROM client_subscriptions cs
                      JOIN plans p ON cs.plan_id = p.id
                      WHERE cs.status = 'active'
                      AND NOT EXISTS (
                          SELECT 1 FROM " . $this->table_name . " b
                          WHERE b.subscription_id = cs.id
                          AND YEAR(b.billing_date) = YEAR(CURRENT_DATE)
                          AND MONTH(b.billing_date) = MONTH(CURRENT_DATE)
                      )";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $generated = 0;
            
            // Current date information
            $billingDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days'));
            
            foreach ($subscriptions as $subscription) {
                // Create a unique invoice number
                $invoiceNumber = $this->generateInvoiceNumber();
                
                // Set invoice amount from plan price
                $amount = $subscription['price'];
                
                // Create the invoice
                $invoiceQuery = "INSERT INTO " . $this->table_name . "
                                (invoice_number, subscription_id, amount, total_amount, billing_date, due_date, status)
                                VALUES
                                (:invoice_number, :subscription_id, :amount, :amount, 
                                 :billing_date, :due_date, 'pending')";
                
                $invoiceStmt = $this->conn->prepare($invoiceQuery);
                
                $invoiceStmt->bindParam(':invoice_number', $invoiceNumber);
                $invoiceStmt->bindParam(':subscription_id', $subscription['subscription_id']);
                $invoiceStmt->bindParam(':amount', $amount);
                $invoiceStmt->bindParam(':billing_date', $billingDate);
                $invoiceStmt->bindParam(':due_date', $dueDate);
                
                // Execute statement and increment counter
                if ($invoiceStmt->execute()) {
                    $generated++;
                }
            }
            
            // Commit transaction if no errors
            $this->conn->commit();
            
            return [
                'generated' => $generated,
                'total_amount' => array_sum(array_column($subscriptions, 'price'))
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Update overdue invoices
     */
    public function updateOverdueInvoices() {
        // Query to update pending invoices that are past due date
        $query = "UPDATE " . $this->table_name . "
                  SET status = 'overdue',
                      updated_at = CURRENT_TIMESTAMP
                  WHERE status = 'pending'
                  AND due_date < CURRENT_DATE";
        
        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice($id, $reason = '') {
        // Query to update invoice status to cancelled
        $query = "UPDATE " . $this->table_name . "
                  SET status = 'cancelled',
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $id);
        
        // Execute statement
        return $stmt->execute();
    }

    /**
     * Get billing statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total outstanding amount
        $query = "SELECT SUM(total_amount) AS outstanding_amount
                  FROM " . $this->table_name . "
                  WHERE status IN ('pending', 'overdue')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['outstanding_amount'] = $result['outstanding_amount'] ?: 0;
        
        // Total collected this month
        $query = "SELECT SUM(p.amount) AS collected_amount
                  FROM payments p
                  WHERE p.status = 'completed'
                  AND YEAR(p.payment_date) = YEAR(CURRENT_DATE)
                  AND MONTH(p.payment_date) = MONTH(CURRENT_DATE)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['collected_this_month'] = $result['collected_amount'] ?: 0;
        
        // Overdue amount
        $query = "SELECT SUM(total_amount) AS overdue_amount
                  FROM " . $this->table_name . "
                  WHERE status = 'overdue'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['overdue_amount'] = $result['overdue_amount'] ?: 0;
        
        // Cancelled amount
        $query = "SELECT SUM(total_amount) AS cancelled_amount
                  FROM " . $this->table_name . "
                  WHERE status = 'cancelled'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['cancelled_amount'] = $result['cancelled_amount'] ?: 0;
        
        // Count by status
        $query = "SELECT status, COUNT(*) AS count
                  FROM " . $this->table_name . "
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['count'] = [
            'pending' => 0,
            'paid' => 0,
            'overdue' => 0,
            'cancelled' => 0
        ];
        
        foreach ($statusCounts as $status) {
            $stats['count'][$status['status']] = $status['count'];
        }
        
        return $stats;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber() {
        $prefix = "INV";
        $year = date('Y');
        
        $query = "SELECT MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)) as max_num 
                 FROM " . $this->table_name . " 
                 WHERE invoice_number LIKE ?";
        
        $prefixLength = strlen($prefix) + strlen($year);
        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . $year . '%';
        $stmt->execute([$prefixLength + 1, $searchPrefix]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($row['max_num'] ?? 0) + 1;
        
        return $prefix . $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
?>
