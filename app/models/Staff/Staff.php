<?php
namespace App\Models\Staff;

use App\Core\Model;

class Staff extends Model {
    protected $table = 'staff';
    protected $primaryKey = 'id';
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'position',
        'hire_date',
        'status',
        'supervisor_id',
        'created_at',
        'updated_at'
    ];

    // Get staff member's attendance records
    public function getAttendance($startDate = null, $endDate = null) {
        $sql = "SELECT a.*, 
                       s.name as shift_name,
                       s.start_time as shift_start,
                       s.end_time as shift_end
                FROM attendance a
                LEFT JOIN shifts s ON a.shift_id = s.id
                WHERE a.staff_id = ?";
        
        $params = [$this->id];
        $types = 'i';
        
        if ($startDate && $endDate) {
            $sql .= " AND a.date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY a.date DESC, a.time_in ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get staff member's expenses
    public function getExpenses($startDate = null, $endDate = null) {
        $sql = "SELECT e.*, 
                       c.name as category_name,
                       p.date as payment_date,
                       p.method as payment_method
                FROM expenses e
                LEFT JOIN expense_categories c ON e.category_id = c.id
                LEFT JOIN payments p ON e.payment_id = p.id
                WHERE e.staff_id = ?";
        
        $params = [$this->id];
        $types = 'i';
        
        if ($startDate && $endDate) {
            $sql .= " AND e.date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY e.date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get staff member's payroll history
    public function getPayrollHistory() {
        $sql = "SELECT p.*,
                       d.amount as deduction_amount,
                       d.reason as deduction_reason,
                       b.amount as bonus_amount,
                       b.reason as bonus_reason
                FROM payroll p
                LEFT JOIN payroll_deductions d ON p.id = d.payroll_id
                LEFT JOIN payroll_bonuses b ON p.id = b.payroll_id
                WHERE p.staff_id = ?
                ORDER BY p.period_end DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get staff member's assigned tasks
    public function getAssignedTasks($status = null) {
        $sql = "SELECT t.*, 
                       p.name as project_name,
                       p.priority as project_priority
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.assigned_to = ?";
        
        if ($status) {
            $sql .= " AND t.status = ?";
        }
        
        $sql .= " ORDER BY t.due_date ASC";
        
        $stmt = $this->db->prepare($sql);
        if ($status) {
            $stmt->bind_param('is', $this->id, $status);
        } else {
            $stmt->bind_param('i', $this->id);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Record attendance
    public function recordAttendance($data) {
        $sql = "INSERT INTO attendance (
                    staff_id,
                    date,
                    time_in,
                    time_out,
                    shift_id,
                    status,
                    notes,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issssss',
            $this->id,
            $data['date'],
            $data['time_in'],
            $data['time_out'] ?? null,
            $data['shift_id'],
            $data['status'],
            $data['notes'] ?? null
        );
        return $stmt->execute();
    }

    // Submit expense claim
    public function submitExpense($data) {
        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Insert expense record
            $sql = "INSERT INTO expenses (
                        staff_id,
                        category_id,
                        amount,
                        date,
                        description,
                        status,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iidss',
                $this->id,
                $data['category_id'],
                $data['amount'],
                $data['date'],
                $data['description']
            );
            $stmt->execute();
            $expenseId = $stmt->insert_id;

            // Handle receipt upload if present
            if (isset($data['receipt'])) {
                $sql = "INSERT INTO expense_receipts (
                            expense_id,
                            file_name,
                            file_path,
                            uploaded_at
                        ) VALUES (?, ?, ?, NOW())";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iss',
                    $expenseId,
                    $data['receipt']['name'],
                    $data['receipt']['path']
                );
                $stmt->execute();
            }

            $this->db->getConnection()->commit();
            return $expenseId;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    // Get leave balance
    public function getLeaveBalance() {
        $sql = "SELECT 
                    lt.name as leave_type,
                    lb.total_days,
                    lb.used_days,
                    lb.remaining_days
                FROM leave_balances lb
                JOIN leave_types lt ON lb.leave_type_id = lt.id
                WHERE lb.staff_id = ?
                AND lb.year = YEAR(CURRENT_DATE())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Apply for leave
    public function applyLeave($data) {
        // Check leave balance
        $sql = "SELECT remaining_days 
                FROM leave_balances 
                WHERE staff_id = ? 
                AND leave_type_id = ? 
                AND year = YEAR(?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iis', 
            $this->id, 
            $data['leave_type_id'],
            $data['start_date']
        );
        $stmt->execute();
        $balance = $stmt->get_result()->fetch_assoc();

        if (!$balance || $balance['remaining_days'] <= 0) {
            throw new \Exception('Insufficient leave balance');
        }

        // Insert leave request
        $sql = "INSERT INTO leave_requests (
                    staff_id,
                    leave_type_id,
                    start_date,
                    end_date,
                    reason,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iisss',
            $this->id,
            $data['leave_type_id'],
            $data['start_date'],
            $data['end_date'],
            $data['reason']
        );
        return $stmt->execute();
    }

    // Validate staff data
    public function validate($data) {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['department'])) {
            $errors['department'] = 'Department is required';
        }

        if (empty($data['position'])) {
            $errors['position'] = 'Position is required';
        }

        if (empty($data['hire_date'])) {
            $errors['hire_date'] = 'Hire date is required';
        } elseif (!strtotime($data['hire_date'])) {
            $errors['hire_date'] = 'Invalid hire date';
        }

        return $errors;
    }
}
