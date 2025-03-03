<?php
namespace App\Models\Admin;

use App\Core\Model;

class Customer extends Model {
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'account_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'installation_address',
        'plan_id',
        'installation_date',
        'contract_period',
        'contract_end_date',
        'ip_type',
        'ip_address',
        'router_model',
        'router_serial',
        'ont_model',
        'ont_serial',
        'username',
        'password',
        'status',
        'notes',
        'created_at',
        'updated_at'
    ];

    /**
     * Get customers with filters
     */
    public function getCustomers($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR account_number LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
            $types .= 'ssss';
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['plan'])) {
            $where[] = "plan_id = ?";
            $params[] = $filters['plan'];
            $types .= 'i';
        }

        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $where[] = "DATE(created_at) = CURRENT_DATE";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(created_at) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'month':
                    $where[] = "YEAR(created_at) = YEAR(CURRENT_DATE) AND MONTH(created_at) = MONTH(CURRENT_DATE)";
                    break;
                case 'year':
                    $where[] = "YEAR(created_at) = YEAR(CURRENT_DATE)";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get customers
        $sql = "SELECT c.*, 
                       p.name as plan_name,
                       p.bandwidth
                FROM {$this->table} c
                LEFT JOIN plans p ON c.plan_id = p.id
                WHERE {$whereClause}
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'customers' => $customers,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Create new customer
     */
    public function createCustomer($data) {
        // Generate account number
        $data['account_number'] = $this->generateAccountNumber();

        // Calculate contract end date
        $data['contract_end_date'] = date('Y-m-d', strtotime($data['installation_date'] . " +{$data['contract_period']} months"));

        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->create($data);
    }

    /**
     * Generate unique account number
     */
    private function generateAccountNumber() {
        $prefix = date('Ym');
        
        $sql = "SELECT account_number 
                FROM {$this->table} 
                WHERE account_number LIKE ?
                ORDER BY id DESC 
                LIMIT 1";
        
        $pattern = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $lastNumber = intval(substr($result['account_number'], -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Bulk action on customers
     */
    public function bulkAction($ids, $action) {
        $validActions = ['suspend', 'activate', 'delete'];
        if (!in_array($action, $validActions)) {
            throw new \Exception('Invalid action');
        }

        $this->db->getConnection()->begin_transaction();

        try {
            if ($action === 'delete') {
                $sql = "DELETE FROM {$this->table} WHERE id IN (" . str_repeat('?,', count($ids) - 1) . "?)";
            } else {
                $status = $action === 'suspend' ? 'suspended' : 'active';
                $sql = "UPDATE {$this->table} SET status = ? WHERE id IN (" . str_repeat('?,', count($ids) - 1) . "?)";
                array_unshift($ids, $status);
            }

            $stmt = $this->db->prepare($sql);
            $types = str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();

            $this->db->getConnection()->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Get total number of customers
     */
    public function getTotalCustomers() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get number of active customers
     */
    public function getActiveCustomers() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'active'";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get customer growth chart data
     */
    public function getGrowthChartData($period = 'month') {
        $sql = match($period) {
            'year' => "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as period,
                        COUNT(*) as total
                      FROM {$this->table}
                      WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                      GROUP BY period
                      ORDER BY period",
            'month' => "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m-%d') as period,
                        COUNT(*) as total
                      FROM {$this->table}
                      WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                      GROUP BY period
                      ORDER BY period",
            'week' => "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m-%d') as period,
                        COUNT(*) as total
                      FROM {$this->table}
                      WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                      GROUP BY period
                      ORDER BY period",
            default => throw new \Exception('Invalid period')
        };

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function validate($data) {
        $errors = [];

        // Required fields
        $required = [
            'first_name' => 'First name is required',
            'last_name' => 'Last name is required',
            'email' => 'Email is required',
            'phone' => 'Phone is required',
            'address' => 'Address is required',
            'installation_address' => 'Installation address is required',
            'plan_id' => 'Service plan is required',
            'installation_date' => 'Installation date is required',
            'contract_period' => 'Contract period is required',
            'ip_type' => 'IP type is required',
            'router_model' => 'Router model is required',
            'router_serial' => 'Router serial number is required',
            'username' => 'Username is required',
            'password' => 'Password is required'
        ];

        foreach ($required as $field => $message) {
            if (empty($data[$field])) {
                $errors[$field] = $message;
            }
        }

        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Check email uniqueness
        if (!empty($data['email'])) {
            $sql = "SELECT id FROM {$this->table} WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['email'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = 'Email already exists';
            }
        }

        // Check username uniqueness
        if (!empty($data['username'])) {
            $sql = "SELECT id FROM {$this->table} WHERE username = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['username'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['username'] = 'Username already exists';
            }
        }

        // Installation date validation
        if (!empty($data['installation_date'])) {
            $installDate = strtotime($data['installation_date']);
            if ($installDate === false || $installDate < strtotime('today')) {
                $errors['installation_date'] = 'Installation date must be today or later';
            }
        }

        return $errors;
    }
}
