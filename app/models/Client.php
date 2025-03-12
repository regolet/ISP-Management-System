<?php
namespace App\Models;

class Client {
    private $db;
    private $table_name = "clients";

    // Client properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $address;
    public $city;
    public $state;
    public $postal_code;
    public $status;
    public $client_number;
    public $connection_date;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all clients with pagination and filtering
     */
    public function getClients($params = []) {
        // Default parameters
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'ASC';
        $page = $params['page'] ?? 1;
        $per_page = $params['per_page'] ?? 10;
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Base query
        $query = "SELECT 
            c.*,
            (SELECT s.plan_name FROM client_subscriptions s WHERE s.client_id = c.id AND s.status = 'active' ORDER BY s.created_at DESC LIMIT 1) AS subscription_plan
        FROM " . $this->table_name . " c";
        
        // Add search condition
        $conditions = [];
        $params_array = [];
        
        if (!empty($search)) {
            $conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.client_number LIKE ?)";
            $search_param = "%" . $search . "%";
            $params_array = array_merge($params_array, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        // Add status condition
        if (!empty($status)) {
            $conditions[] = "c.status = ?";
            $params_array[] = $status;
        }
        
        // Combine conditions
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add sorting
        $query .= " ORDER BY " . $sort . " " . $order;
        
        // Add pagination
        $query .= " LIMIT " . $per_page . " OFFSET " . $offset;
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->execute($params_array);
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        if (!empty($conditions)) {
            $count_query .= " WHERE " . implode(" AND ", $conditions);
        }
        $count_stmt = $this->db->prepare($count_query);
        $count_stmt->execute($params_array);
        $count_row = $count_stmt->fetch(\PDO::FETCH_ASSOC);
        $total = $count_row['total'];
        
        // Return results with pagination info
        return [
            'clients' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'last_page' => ceil($total / $per_page),
                'from' => $offset + 1,
                'to' => min($offset + $per_page, $total)
            ]
        ];
    }

    /**
     * Get a single client by ID
     */
    public function getClient($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Create a new client
     */
    public function createClient($data) {
        // Sanitize and validate data
        $sanitized_data = $this->sanitizeData($data);
        
        // Generate client number if not provided
        if (empty($sanitized_data['client_number'])) {
            $sanitized_data['client_number'] = $this->generateClientNumber();
        }
        
        // Set timestamps
        $now = date('Y-m-d H:i:s');
        $sanitized_data['created_at'] = $now;
        $sanitized_data['updated_at'] = $now;
        
        // Build query
        $fields = array_keys($sanitized_data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO " . $this->table_name . " (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute(array_values($sanitized_data));
            
            // Get the last inserted ID
            $last_id = $this->db->lastInsertId();
            
            // Return the created client
            return [
                'success' => true,
                'id' => $last_id,
                'client' => $this->getClient($last_id)
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing client
     */
    public function updateClient($id, $data) {
        // Sanitize and validate data
        $sanitized_data = $this->sanitizeData($data);
        
        // Set updated timestamp
        $sanitized_data['updated_at'] = date('Y-m-d H:i:s');
        
        // Build query
        $set_clause = [];
        foreach ($sanitized_data as $key => $value) {
            $set_clause[] = $key . " = ?";
        }
        
        $query = "UPDATE " . $this->table_name . " SET " . implode(", ", $set_clause) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($query);
            $params = array_values($sanitized_data);
            $params[] = $id;
            $stmt->execute($params);
            
            // Check if update was successful
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Client updated successfully',
                    'client' => $this->getClient($id)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No changes made or client not found'
                ];
            }
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a client
     */
    public function deleteClient($id) {
        // Check if client has subscriptions
        $check_query = "SELECT COUNT(*) as count FROM client_subscriptions WHERE client_id = ?";
        $check_stmt = $this->db->prepare($check_query);
        $check_stmt->execute([$id]);
        $result = $check_stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete client with active subscriptions'
            ];
        }
        
        // Delete client
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Check if delete was successful
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Client deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Client not found'
                ];
            }
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique client number
     */
    private function generateClientNumber() {
        $prefix = 'CL';
        $year = date('y');
        
        // Get the last client number
        $query = "SELECT client_number FROM " . $this->table_name . " WHERE client_number LIKE ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$prefix . $year . '%']);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result) {
            // Extract the sequence number and increment
            $last_number = $result['client_number'];
            $sequence = (int)substr($last_number, -4);
            $sequence++;
        } else {
            // Start with 1
            $sequence = 1;
        }
        
        // Format the new client number
        return $prefix . $year . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sanitize and validate client data
     */
    private function sanitizeData($data) {
        $sanitized = [];
        
        // First name (required)
        if (isset($data['first_name']) && !empty($data['first_name'])) {
            $sanitized['first_name'] = trim($data['first_name']);
        }
        
        // Last name (required)
        if (isset($data['last_name']) && !empty($data['last_name'])) {
            $sanitized['last_name'] = trim($data['last_name']);
        }
        
        // Email (optional)
        if (isset($data['email'])) {
            $sanitized['email'] = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        }
        
        // Phone (optional)
        if (isset($data['phone'])) {
            $sanitized['phone'] = preg_replace('/[^0-9+\-\(\) ]/', '', $data['phone']);
        }
        
        // Address (optional)
        if (isset($data['address'])) {
            $sanitized['address'] = trim($data['address']);
        }
        
        // City (optional)
        if (isset($data['city'])) {
            $sanitized['city'] = trim($data['city']);
        }
        
        // State (optional)
        if (isset($data['state'])) {
            $sanitized['state'] = trim($data['state']);
        }
        
        // Postal code (optional)
        if (isset($data['postal_code'])) {
            $sanitized['postal_code'] = trim($data['postal_code']);
        }
        
        // Status (default: active)
        if (isset($data['status']) && in_array($data['status'], ['active', 'inactive', 'suspended'])) {
            $sanitized['status'] = $data['status'];
        } else {
            $sanitized['status'] = 'active';
        }
        
        // Client number (optional, will be generated if not provided)
        if (isset($data['client_number']) && !empty($data['client_number'])) {
            $sanitized['client_number'] = trim($data['client_number']);
        }
        
        // Connection date (optional)
        if (isset($data['connection_date']) && !empty($data['connection_date'])) {
            $sanitized['connection_date'] = $data['connection_date'];
        }
        
        return $sanitized;
    }

    /**
     * Get client statistics
     */
    public function getClientStats() {
        $stats = [];
        
        // Total clients
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total'] = $result['total'];
        
        // Clients by status
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " GROUP BY status";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['by_status'] = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // New clients this month
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE created_at >= date('now', 'start of month')";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['new_this_month'] = $result['count'];
        
        return $stats;
    }
}