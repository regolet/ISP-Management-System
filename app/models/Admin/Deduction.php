<?php
namespace App\Models\Admin;

use App\Core\Model;

class Deduction extends Model {
    protected $table = 'deduction_types';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'type',
        'calculation_type',
        'percentage_value',
        'is_active',
        'created_at',
        'updated_at'
    ];

    /**
     * Get all deduction types
     */
    public function getDeductionTypes($filters = []) {
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (isset($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
            $types .= 'i';
        }

        if (!empty($filters['type'])) {
            $where[] = "type = ?";
            $params[] = $filters['type'];
            $types .= 's';
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY name";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get active employee deductions
     */
    public function getEmployeeDeductions($filters = []) {
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['employee_id'])) {
            $where[] = "ed.employee_id = ?";
            $params[] = $filters['employee_id'];
            $types .= 'i';
        }

        if (!empty($filters['status'])) {
            $where[] = "ed.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT ed.*, 
                       e.first_name, 
                       e.last_name, 
                       e.employee_code,
                       dt.name as deduction_name,
                       dt.type
                FROM employee_deductions ed
                JOIN employees e ON ed.employee_id = e.id
                JOIN deduction_types dt ON ed.deduction_type_id = dt.id
                WHERE {$whereClause}
                ORDER BY e.last_name, e.first_name, dt.name";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get deduction history
     */
    public function getDeductionHistory($deductionId) {
        $sql = "SELECT dh.*, 
                       pp.period_start,
                       pp.period_end,
                       pp.pay_date
                FROM deduction_history dh
                LEFT JOIN payroll_periods pp ON dh.payroll_id = pp.id
                WHERE dh.employee_deduction_id = ?
                ORDER BY dh.transaction_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $deductionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get deduction statistics
     */
    public function getDeductionStats($deductionId) {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    (SELECT amount FROM employee_deductions WHERE id = ?) - 
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as remaining_balance
                FROM deduction_history 
                WHERE employee_deduction_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $deductionId, $deductionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Add employee deduction
     */
    public function addEmployeeDeduction($data) {
        $this->db->begin_transaction();

        try {
            // Create employee deduction record
            $sql = "INSERT INTO employee_deductions (
                        employee_id, deduction_type_id, amount, frequency,
                        start_date, end_date, status, remarks
                    ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'iidssss',
                $data['employee_id'],
                $data['deduction_type_id'],
                $data['amount'],
                $data['frequency'],
                $data['start_date'],
                $data['end_date'],
                $data['remarks']
            );
            $stmt->execute();
            $deductionId = $stmt->insert_id;

            // Add initial history record
            $sql = "INSERT INTO deduction_history (
                        employee_deduction_id, transaction_date, amount,
                        status, reference, notes
                    ) VALUES (?, NOW(), ?, 'pending', 'Initial Setup', 'Deduction setup')";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('id', $deductionId, $data['amount']);
            $stmt->execute();

            $this->db->commit();
            return $deductionId;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Cancel employee deduction
     */
    public function cancelDeduction($id) {
        $this->db->begin_transaction();

        try {
            // Update deduction status
            $sql = "UPDATE employee_deductions SET status = 'cancelled' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();

            // Add history record
            $sql = "INSERT INTO deduction_history (
                        employee_deduction_id, transaction_date, status,
                        reference, notes
                    ) VALUES (?, NOW(), 'cancelled', 'Manual Cancellation', 'Deduction cancelled')";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Validate deduction type data
     */
    public function validateType($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (empty($data['type'])) {
            $errors['type'] = 'Type is required';
        }

        if (empty($data['calculation_type'])) {
            $errors['calculation_type'] = 'Calculation type is required';
        }

        if ($data['calculation_type'] === 'percentage' && 
            (!isset($data['percentage_value']) || $data['percentage_value'] <= 0 || $data['percentage_value'] > 100)) {
            $errors['percentage_value'] = 'Valid percentage value is required (1-100)';
        }

        return $errors;
    }

    /**
     * Validate employee deduction data
     */
    public function validateEmployeeDeduction($data) {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required';
        }

        if (empty($data['deduction_type_id'])) {
            $errors['deduction_type_id'] = 'Deduction type is required';
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        if (empty($data['frequency'])) {
            $errors['frequency'] = 'Frequency is required';
        }

        if (empty($data['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }

        if (!empty($data['end_date']) && strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            $errors['end_date'] = 'End date must be after start date';
        }

        return $errors;
    }
}
