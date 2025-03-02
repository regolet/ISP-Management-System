<?php
namespace App\Models\Admin;

use App\Core\Model;

class Subscription extends Model {
    protected $table = 'subscriptions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'customer_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'installation_address',
        'router_model',
        'router_serial',
        'ont_model',
        'ont_serial',
        'ip_type',
        'ip_address',
        'notes',
        'created_at',
        'updated_at'
    ];

    /**
     * Get subscriptions with details
     */
    public function getSubscriptions($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(c.name LIKE ? OR c.customer_code LIKE ? OR p.name LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
            $types .= 'sss';
        }

        if (!empty($filters['status'])) {
            $where[] = "s.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['plan_id'])) {
            $where[] = "s.plan_id = ?";
            $params[] = $filters['plan_id'];
            $types .= 'i';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} s 
                     LEFT JOIN customers c ON s.customer_id = c.id
                     LEFT JOIN plans p ON s.plan_id = p.id
                     WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get subscriptions
        $sql = "SELECT s.*, 
                       c.name as customer_name,
                       c.customer_code,
                       p.name as plan_name,
                       p.amount as plan_amount,
                       p.bandwidth
                FROM {$this->table} s 
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE {$whereClause}
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'subscriptions' => $subscriptions,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get subscription details
     */
    public function getSubscriptionDetails($id) {
        $sql = "SELECT s.*, 
                       c.name as customer_name,
                       c.customer_code,
                       c.email,
                       c.phone,
                       c.address,
                       p.name as plan_name,
                       p.amount as plan_amount,
                       p.bandwidth
                FROM {$this->table} s 
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE s.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create new subscription
     */
    public function createSubscription($data) {
        // Set initial status
        $data['status'] = 'active';

        return $this->create($data);
    }

    /**
     * Update subscription status
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['active', 'suspended', 'terminated', 'pending'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status');
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Validate subscription data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['customer_id'])) {
            $errors['customer_id'] = 'Customer is required';
        }

        if (empty($data['plan_id'])) {
            $errors['plan_id'] = 'Plan is required';
        }

        if (empty($data['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }

        if (empty($data['installation_address'])) {
            $errors['installation_address'] = 'Installation address is required';
        }

        if (empty($data['router_model'])) {
            $errors['router_model'] = 'Router model is required';
        }

        if (empty($data['router_serial'])) {
            $errors['router_serial'] = 'Router serial number is required';
        }

        // Check if router serial is unique
        if (!empty($data['router_serial'])) {
            $sql = "SELECT id FROM {$this->table} WHERE router_serial = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['router_serial'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['router_serial'] = 'Router serial number already exists';
            }
        }

        // Check if ONT serial is unique if provided
        if (!empty($data['ont_serial'])) {
            $sql = "SELECT id FROM {$this->table} WHERE ont_serial = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['ont_serial'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['ont_serial'] = 'ONT serial number already exists';
            }
        }

        return $errors;
    }

    /**
     * Get active subscriptions by customer
     */
    public function getActiveSubscriptionsByCustomer($customerId) {
        $sql = "SELECT s.*, p.name as plan_name, p.amount as plan_amount, p.bandwidth
                FROM {$this->table} s 
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE s.customer_id = ? AND s.status = 'active'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total number of subscriptions
     */
    public function getTotalSubscriptions() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get number of active subscriptions
     */
    public function getActiveSubscriptions() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'active'";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get subscription distribution data
     */
    public function getDistributionData() {
        $sql = "SELECT 
                    p.name as plan,
                    COUNT(*) as count,
                    s.status,
                    p.bandwidth
                FROM {$this->table} s
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE s.status = 'active'
                GROUP BY p.id, s.status
                ORDER BY count DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
