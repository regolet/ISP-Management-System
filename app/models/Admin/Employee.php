<?php
namespace App\Models\Admin;

use App\Core\Model;

class Employee extends Model {
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'position',
        'department',
        'daily_rate',
        'hire_date',
        'status',
        'email',
        'phone',
        'address',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'tin_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'created_at',
        'updated_at'
    ];

    /**
     * Get employees with filters
     */
    public function getEmployees($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(employee_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR position LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
            $types .= 'ssss';
        }

        if (!empty($filters['department'])) {
            $where[] = "department = ?";
            $params[] = $filters['department'];
            $types .= 's';
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
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

        // Get employees
        $sql = "SELECT * FROM {$this->table} 
                WHERE {$whereClause}
                ORDER BY last_name, first_name
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'employees' => $employees,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get employee details with attendance summary
     */
    public function getEmployeeDetails($id) {
        $sql = "SELECT e.*,
                       (SELECT COUNT(*) FROM attendance 
                        WHERE employee_id = e.id AND status = 'present') as present_days,
                       (SELECT COUNT(*) FROM attendance 
                        WHERE employee_id = e.id AND status = 'absent') as absent_days,
                       (SELECT COUNT(*) FROM attendance 
                        WHERE employee_id = e.id AND status = 'late') as late_days
                FROM {$this->table} e
                WHERE e.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Generate employee code
     */
    public function generateEmployeeCode() {
        $prefix = date('Y');
        
        $sql = "SELECT employee_code FROM {$this->table} 
                WHERE employee_code LIKE ? 
                ORDER BY id DESC LIMIT 1";
        
        $pattern = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $lastNumber = intval(substr($result['employee_code'], -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Create new employee
     */
    public function createEmployee($data) {
        // Generate employee code
        $data['employee_code'] = $this->generateEmployeeCode();
        
        // Set initial status
        $data['status'] = 'active';

        return $this->create($data);
    }

    /**
     * Update employee status
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['active', 'inactive', 'terminated'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status');
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Get departments list
     */
    public static function getDepartments() {
        return [
            'IT' => 'Information Technology',
            'HR' => 'Human Resources',
            'Finance' => 'Finance',
            'Operations' => 'Operations',
            'Sales' => 'Sales',
            'Support' => 'Support',
            'Admin' => 'Administration'
        ];
    }

    /**
     * Get total number of employees
     */
    public function getTotalEmployees() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get active employees
     * Returns either a count or full list based on $returnList parameter
     */
    public function getActiveEmployees($returnList = false) {
        if ($returnList) {
            $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY last_name, first_name";
            return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
        } else {
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'active'";
            $result = $this->db->query($sql);
            return $result->fetch_assoc()['total'];
        }
    }

    /**
     * Validate employee data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        if (empty($data['position'])) {
            $errors['position'] = 'Position is required';
        }

        if (empty($data['department'])) {
            $errors['department'] = 'Department is required';
        }

        if (empty($data['daily_rate']) || !is_numeric($data['daily_rate']) || $data['daily_rate'] <= 0) {
            $errors['daily_rate'] = 'Valid daily rate is required';
        }

        if (empty($data['hire_date'])) {
            $errors['hire_date'] = 'Hire date is required';
        }

        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }

            // Check email uniqueness
            $sql = "SELECT id FROM {$this->table} WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['email'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = 'Email already exists';
            }
        }

        return $errors;
    }
}
