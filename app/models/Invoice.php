<?php
namespace App\Models;

use PDO;
use DateTime;

class Invoice {
    // Database connection and table name
    private $conn;
    private $table_name = "billing"; // Assuming "billing" table is used for invoices

    // Object properties
    public $id;
    public $invoice_number;
    public $client_id;
    public $total_amount;
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
    public function getAll($page = 1, $per_page = 10, $search = '', $status = '', $sort = 'id', $order = 'DESC') {
        // Prepare query
        $query = "SELECT b.*, c.first_name, c.last_name, c.email
                FROM " . $this->table_name . " b
                JOIN clients c ON b.client_id = c.id
                WHERE 1=1";

        // Apply search filter
        if (!empty($search)) {
            $query .= " AND (
                b.invoice_number LIKE :search
                OR c.first_name LIKE :search
                OR c.last_name LIKE :search
                OR c.email LIKE :search
            )";
        }

        // Apply status filter
        if (!empty($status)) {
            $query .= " AND b.status = :status";
        }

        // Apply sorting
        $allowed_sort_fields = ['id', 'invoice_number', 'total_amount', 'due_date', 'status'];
        $sort = in_array($sort, $allowed_sort_fields) ? $sort : 'due_date';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query .= " ORDER BY b." . $sort . " " . $order;

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
     * Get total number of invoices with filters
     */
    public function getTotal($search = '', $status = '') {
        // Prepare query
        $query = "SELECT COUNT(*) as total
                FROM " . $this->table_name . " b
                JOIN clients c ON b.client_id = c.id
                WHERE 1=1";

        // Apply search filter
        if (!empty($search)) {
            $query .= " AND (
                b.invoice_number LIKE :search
                OR c.first_name LIKE :search
                OR c.last_name LIKE :search
                OR c.email LIKE :search
            )";
        }

        // Apply status filter
        if (!empty($status)) {
            $query .= " AND b.status = :status";
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
     * Get single invoice by ID
     */
    public function getById($id) {
        // Prepare query
        $query = "SELECT
                    b.*,
                    c.first_name,
                    c.last_name,
                    c.address,
                    c.city,
                    c.state,
                    c.postal_code,
                    p.name AS plan_name,
                    p.price AS plan_price,
                    GROUP_CONCAT(ii.description || ' x ' || ii.quantity || ' @ ' || ii.unit_price) AS item_details,
                    ci.company_name,
                    ci.address AS company_address,
                    ci.city AS company_city,
                    ci.state AS company_state,
                    ci.postal_code AS company_postal_code
                FROM " . $this->table_name . " b
                JOIN clients c ON b.client_id = c.id
                JOIN client_subscriptions cs ON p.id = cs.plan_id
                JOIN plans p ON p.id = cs.plan_id
                LEFT JOIN invoice_items ii ON b.id = ii.invoice_id
                LEFT JOIN company_info ci ON 1=1  -- Assuming only one company info record
                WHERE b.id = :id
                GROUP BY b.id
                LIMIT 0,1";

        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStats() {
        $query = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as overdue
            FROM
                " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create invoice
     */
    public function create($client_id, $invoice_number, $total_amount, $due_date, $status, $amount) {
        // Prepare query
        $query = "INSERT INTO " . $this->table_name . "
                (client_id, invoice_number, total_amount, due_date, status, billing_date, amount)
                VALUES
                (:client_id, :invoice_number, :total_amount, :due_date, :status, :billing_date, :amount)";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':client_id', $client_id);
        $stmt->bindParam(':invoice_number', $invoice_number);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':billing_date', date('Y-m-d H:i:s'));
        $stmt->bindParam(':amount', $amount);

        // Execute statement
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    /**
     * Create invoice item
     */
    public function createInvoiceItem($invoice_id, $description, $quantity, $unit_price, $total) {
        // Prepare query
        $query = "INSERT INTO invoice_items
                (invoice_id, description, quantity, unit_price, total)
                VALUES
                (:invoice_id, :description, :quantity, :unit_price, :total)";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':invoice_id', $invoice_id);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':total', $total);

        // Execute statement
        return $stmt->execute();
    }

    /**
     * Delete invoice
     */
    public function delete($id) {
        // Prepare query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        // Prepare and execute statement
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}