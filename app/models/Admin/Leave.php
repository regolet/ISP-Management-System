<?php
namespace App\Models\Admin;

use App\Core\Model;

class Leave extends Model {
    protected $table = 'leaves';
    protected $primaryKey = 'id';
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get leave applications with filters
     */
    public function getLeaveApplications($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['employee_id'])) {
            $where[] = "l.employee_id = ?";
            $params[] = $filters['employee_id'];
            $types .= 'i';
        }

        if (!empty($filters['status'])) {
            $where[] = "l.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['department'])) {
            $where[] = "e.department = ?";
            $params[] = $filters['department'];
            $types .= 's';
        }

        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $where[] = "DATE(l.start_date) = CURRENT_DATE";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(l.start_date) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'month':
                    $where[] = "YEAR(l.start_date) = YEAR(CURRENT_DATE) AND MONTH(l.start_date) = MONTH(CURRENT_DATE)";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} l
                     JOIN employees e ON l.employee_id = e.id 
                     WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get leave applications
        $sql = "SELECT l.*, 
                       e.employee_code,
                       e.first_name,
                       e.last_name,
                       e.department,
                       e.position,
                       u.username as approved_by_name
                FROM {$this->table} l
                JOIN employees e ON l.employee_id = e.id
                LEFT JOIN users u ON l.approved_by = u.id
                WHERE {$whereClause}
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'applications' => $applications,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get leave application details
     */
    public function getLeaveDetails($id) {
        $sql = "SELECT l.*, 
                       e.employee_code,
                       e.first_name,
                       e.last_name,
                       e.department,
                       e.position,
                       u.username as approved_by_name
                FROM {$this->table} l
                JOIN employees e ON l.employee_id = e.id
                LEFT JOIN users u ON l.approved_by = u.id
                WHERE l.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Apply for leave
     */
    public function applyLeave($data) {
        // Calculate number of days
        $start = strtotime($data['start_date']);
        $end = strtotime($data['end_date']);
        $data['days'] = floor(($end - $start) / (60 * 60 * 24)) + 1;

        // Set initial status
        $data['status'] = 'pending';

        // Check leave balance
        $balance = $this->getLeaveBalance($data['employee_id'], $data['leave_type']);
        if ($balance < $data['days']) {
            throw new \Exception('Insufficient leave balance');
        }

        return $this->create($data);
    }

    /**
     * Update leave status
     */
    public function updateStatus($id, $status, $approvedBy) {
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new \Exception('Invalid status');
        }

        $data = [
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        // If approved, deduct from leave balance
        if ($status === 'approved') {
            $leave = $this->find($id);
            $this->deductLeaveBalance(
                $leave['employee_id'],
                $leave['leave_type'],
                $leave['days']
            );
        }

        return $this->update($id, $data);
    }

    /**
     * Get leave balance
     */
    private function getLeaveBalance($employeeId, $leaveType) {
        $sql = "SELECT {$leaveType} as balance 
                FROM leave_balances 
                WHERE employee_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result ? $result['balance'] : 0;
    }

    /**
     * Deduct from leave balance
     */
    private function deductLeaveBalance($employeeId, $leaveType, $days) {
        $sql = "UPDATE leave_balances 
                SET {$leaveType} = {$leaveType} - ? 
                WHERE employee_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('di', $days, $employeeId);
        return $stmt->execute();
    }

    /**
     * Get leave types
     */
    public static function getLeaveTypes() {
        return [
            'sick_leave' => 'Sick Leave',
            'vacation_leave' => 'Vacation Leave',
            'emergency_leave' => 'Emergency Leave'
        ];
    }

    /**
     * Validate leave application
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required';
        }

        if (empty($data['leave_type'])) {
            $errors['leave_type'] = 'Leave type is required';
        }

        if (empty($data['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'End date is required';
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $start = strtotime($data['start_date']);
            $end = strtotime($data['end_date']);
            
            if ($end < $start) {
                $errors['end_date'] = 'End date must be after start date';
            }
        }

        if (empty($data['reason'])) {
            $errors['reason'] = 'Reason is required';
        }

        return $errors;
    }
}
