<?php
namespace App\Models;

use PDO;

class Subscription {
    private $conn;
    private $table = 'client_subscriptions';

    // Subscription properties
    public $id;
    public $client_id;
    public $plan_id;
    public $speed_mbps;
    public $price;
    public $subscription_number;
    public $status;
    public $start_date;
    public $billing_cycle;
    public $identifier;
    public $created_at;
    public $updated_at;
    public $plan_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all subscriptions with optional filtering and pagination
     */
    public function getAll($page = 1, $per_page = 10, $search = '', $status = '', $sort = 'id', $order = 'ASC') {
        $offset = ($page - 1) * $per_page;

        try {
            // Query subscriptions
            $query = "SELECT cs.*,
                        c.first_name,
                        c.last_name,
                        p.name as plan_name,
                        p.price as plan_price,
                        0 as pending_bills,
                        cs.subscription_number as identifier
                     FROM " . $this->table . " cs
                     JOIN clients c ON cs.client_id = c.id
                     LEFT JOIN plans p ON cs.plan_id = p.id
                     WHERE 1=1";

            if (!empty($search)) {
                $query .= " AND (cs.subscription_number LIKE :search OR p.name LIKE :search OR c.first_name LIKE :search OR c.last_name LIKE :search)";
            }

            if (!empty($status)) {
                $query .= " AND cs.status = :status";
            }

            $query .= " ORDER BY " . $sort . " " . $order . "
                       LIMIT :offset, :per_page";

            $stmt = $this->conn->prepare($query);

            if (!empty($search)) {
                $searchTerm = "%{$search}%";
                $stmt->bindParam(':search', $searchTerm);
            }

            if (!empty($status)) {
                $stmt->bindParam(':status', $status);
            }

            $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $stmt->bindParam(':per_page', $per_page, \PDO::PARAM_INT);

            $stmt->execute();
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // After fetching subscriptions, format the price
            foreach ($subscriptions as &$subscription) {
                $subscription['price'] = number_format($subscription['price'], 2);
            }

            return $subscriptions;
        } catch (\PDOException $e) {
            error_log("Error in getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total number of subscriptions
     */
    public function getTotal($search = '', $status = '') {
        try {
            // Count subscriptions
            $query = "SELECT COUNT(*) as total 
                     FROM " . $this->table . " cs
                     WHERE 1=1";

            if (!empty($search)) {
                $query .= " AND (cs.subscription_number LIKE :search OR cs.plan_name LIKE :search)";
            }
    
            if (!empty($status)) {
                $query .= " AND cs.status = :status";
            }
    
            $stmt = $this->conn->prepare($query);
    
            if (!empty($search)) {
                $searchTerm = "%{$search}%";
                $stmt->bindParam(':search', $searchTerm);
            }
    
            if (!empty($status)) {
                $stmt->bindParam(':status', $status);
            }
    
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch (\PDOException $e) {
            error_log("Error in getTotal: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get single subscription by ID
     */
    public function getById($id) {
        try {
            // Query subscription by ID
            $query = "SELECT cs.*,
                        p.price as plan_price,
                        p.name as plan_name
                 FROM " . $this->table . " cs
                 LEFT JOIN plans p ON cs.plan_id = p.id
                 WHERE cs.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error in getById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new subscription
     */
    public function create() {
        // Generate unique subscription number
        $this->subscription_number = $this->generateSubscriptionNumber();
        

        $query = "INSERT INTO " . $this->table . "
                (client_id, plan_id, speed_mbps, price, subscription_number, status, 
                 start_date, billing_cycle, identifier, plan_name)
                VALUES
                (:client_id, :plan_id, :speed_mbps, :price, :subscription_number, :status,
                 :start_date, :billing_cycle, :identifier, :plan_name)";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind data
        $this->plan_id = $this->plan_id ? htmlspecialchars(strip_tags($this->plan_id)) : null;
        $this->speed_mbps = !empty($this->speed_mbps) ? (int)$this->speed_mbps : 0;
        $this->price = !empty($this->price) ? (float)$this->price : 0;
        $this->subscription_number = $this->subscription_number ? htmlspecialchars(strip_tags($this->subscription_number)) : null;
        $this->status = $this->status ? htmlspecialchars(strip_tags($this->status)) : null;
        $this->start_date = $this->start_date ? htmlspecialchars(strip_tags($this->start_date)) : null;
        $this->billing_cycle = $this->billing_cycle ? htmlspecialchars(strip_tags($this->billing_cycle)) : null;
        $this->identifier = $this->identifier ? htmlspecialchars(strip_tags($this->identifier)) : null;
        $this->plan_name = $this->plan_name ? htmlspecialchars(strip_tags($this->plan_name)) : null;
        
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':plan_id', $this->plan_id);
        $stmt->bindParam(':speed_mbps', $this->speed_mbps);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':subscription_number', $this->subscription_number);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':billing_cycle', $this->billing_cycle);
        $stmt->bindParam(':identifier', $this->identifier);
        $stmt->bindParam(':plan_name', $this->plan_name);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Update subscription
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET client_id = :client_id,
                    plan_id = :plan_id,
                    speed_mbps = :speed_mbps,
                    price = :price,
                    status = :status,
                    start_date = :start_date,
                    billing_cycle = :billing_cycle,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind data
        $this->plan_id = $this->plan_id ? htmlspecialchars(strip_tags($this->plan_id)) : null;
        $this->speed_mbps = !empty($this->speed_mbps) ? (int)$this->speed_mbps : 0;
        $this->price = !empty($this->price) ? (float)$this->price : 0;
        $this->status = $this->status ? htmlspecialchars(strip_tags($this->status)) : null;
        $this->start_date = $this->start_date ? htmlspecialchars(strip_tags($this->start_date)) : null;
        $this->billing_cycle = $this->billing_cycle ? htmlspecialchars(strip_tags($this->billing_cycle)) : null;
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':plan_id', $this->plan_id);
        $stmt->bindParam(':speed_mbps', $this->speed_mbps);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':billing_cycle', $this->billing_cycle);

        return $stmt->execute();
    }

    /**
     * Delete subscription
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Get subscription statistics
     */
    public function getStats() {
        $stats = [
            'total_subscriptions' => 0,
            'active_subscriptions' => 0,
            'suspended_subscriptions' => 0,
            'monthly_revenue' => 0
        ];

        // Get subscription counts
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
                 FROM " . $this->table;
        $stmt = $this->conn->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_subscriptions'] = $result['total'];
        $stats['active_subscriptions'] = $result['active'];
        $stats['suspended_subscriptions'] = $result['suspended'];

        // Get monthly revenue
        try {
            $query = "SELECT SUM(price) as monthly_revenue
                     FROM " . $this->table . "
                     WHERE status = 'active'";
            $stmt = $this->conn->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['monthly_revenue'] = $result['monthly_revenue'] ?? 0;
        } catch (\PDOException $e) {
            error_log("Error getting monthly revenue: " . $e->getMessage());
            $stats['monthly_revenue'] = 0;
        }

        return $stats;
    }

    /**
     * Generate unique subscription number
     */
    private function generateSubscriptionNumber() {
        $prefix = 'SUB';
        $year = date('Y');
        
        $query = "SELECT MAX(CAST(SUBSTRING(subscription_number, 8) AS UNSIGNED)) as max_num 
                 FROM " . $this->table . " 
                 WHERE subscription_number LIKE :prefix";
        
        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . $year . '%';
        $stmt->bindParam(':prefix', $searchPrefix);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($row['max_num'] ?? 0) + 1;
        
        return $prefix . $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
