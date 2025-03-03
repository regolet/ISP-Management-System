<?php
namespace App\Models\Staff;

use App\Core\Model;

class Payroll extends Model {
    protected $table = 'payroll';
    protected $primaryKey = 'id';
    protected $fillable = [
        'staff_id',
        'period_start',
        'period_end',
        'basic_salary',
        'allowances',
        'deductions',
        'overtime_hours',
        'overtime_rate',
        'tax',
        'net_salary',
        'payment_status',
        'payment_date',
        'remarks',
        'created_at',
        'updated_at'
    ];

    /**
     * Get staff payroll history
     */
    public function getPayrollHistory($staffId, $startDate = null, $endDate = null) {
        $sql = "SELECT p.*,
                       d.amount as deduction_amount,
                       d.reason as deduction_reason,
                       a.amount as allowance_amount,
                       a.type as allowance_type
                FROM {$this->table} p
                LEFT JOIN payroll_deductions d ON p.id = d.payroll_id
                LEFT JOIN payroll_allowances a ON p.id = a.payroll_id
                WHERE p.staff_id = ?";
        
        $params = [$staffId];
        $types = 'i';

        if ($startDate && $endDate) {
            $sql .= " AND p.period_start >= ? AND p.period_end <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }

        $sql .= " ORDER BY p.period_start DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get payroll details
     */
    public function getPayrollDetails($payrollId) {
        $sql = "SELECT p.*,
                       s.first_name,
                       s.last_name,
                       s.employee_id,
                       s.department,
                       s.position,
                       GROUP_CONCAT(DISTINCT d.amount) as deductions,
                       GROUP_CONCAT(DISTINCT d.reason) as deduction_reasons,
                       GROUP_CONCAT(DISTINCT a.amount) as allowances,
                       GROUP_CONCAT(DISTINCT a.type) as allowance_types
                FROM {$this->table} p
                JOIN staff s ON p.staff_id = s.id
                LEFT JOIN payroll_deductions d ON p.id = d.payroll_id
                LEFT JOIN payroll_allowances a ON p.id = a.payroll_id
                WHERE p.id = ?
                GROUP BY p.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $payrollId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary($basicSalary, $allowances, $deductions, $overtimeHours = 0, $overtimeRate = 0) {
        // Calculate overtime pay
        $overtimePay = $overtimeHours * $overtimeRate;

        // Calculate gross salary
        $grossSalary = $basicSalary + $allowances + $overtimePay;

        // Calculate tax (simplified example)
        $tax = $this->calculateTax($grossSalary);

        // Calculate net salary
        $netSalary = $grossSalary - $deductions - $tax;

        return [
            'gross_salary' => $grossSalary,
            'tax' => $tax,
            'net_salary' => $netSalary,
            'overtime_pay' => $overtimePay
        ];
    }

    /**
     * Calculate tax (simplified example)
     */
    private function calculateTax($grossSalary) {
        // This is a simplified tax calculation
        // In a real application, this would be more complex
        // and would likely use tax brackets and rates from configuration
        if ($grossSalary <= 20000) {
            return $grossSalary * 0.1;
        } elseif ($grossSalary <= 40000) {
            return $grossSalary * 0.15;
        } else {
            return $grossSalary * 0.2;
        }
    }

    /**
     * Generate payslip
     */
    public function generatePayslip($payrollId) {
        $details = $this->getPayrollDetails($payrollId);
        if (!$details) {
            return false;
        }

        // Format payslip data
        return [
            'employee' => [
                'name' => $details['first_name'] . ' ' . $details['last_name'],
                'id' => $details['employee_id'],
                'department' => $details['department'],
                'position' => $details['position']
            ],
            'payroll' => [
                'period_start' => $details['period_start'],
                'period_end' => $details['period_end'],
                'basic_salary' => $details['basic_salary'],
                'allowances' => array_combine(
                    explode(',', $details['allowance_types']),
                    explode(',', $details['allowances'])
                ),
                'deductions' => array_combine(
                    explode(',', $details['deduction_reasons']),
                    explode(',', $details['deductions'])
                ),
                'overtime' => [
                    'hours' => $details['overtime_hours'],
                    'rate' => $details['overtime_rate'],
                    'amount' => $details['overtime_hours'] * $details['overtime_rate']
                ],
                'tax' => $details['tax'],
                'net_salary' => $details['net_salary'],
                'payment_status' => $details['payment_status'],
                'payment_date' => $details['payment_date']
            ]
        ];
    }

    /**
     * Validate payroll data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['staff_id'])) {
            $errors['staff_id'] = 'Staff ID is required';
        }

        if (empty($data['period_start']) || !strtotime($data['period_start'])) {
            $errors['period_start'] = 'Valid period start date is required';
        }

        if (empty($data['period_end']) || !strtotime($data['period_end'])) {
            $errors['period_end'] = 'Valid period end date is required';
        }

        if (!empty($data['period_start']) && !empty($data['period_end'])) {
            if (strtotime($data['period_end']) <= strtotime($data['period_start'])) {
                $errors['period_end'] = 'Period end date must be after start date';
            }
        }

        if (!isset($data['basic_salary']) || !is_numeric($data['basic_salary']) || $data['basic_salary'] < 0) {
            $errors['basic_salary'] = 'Valid basic salary is required';
        }

        if (!empty($data['overtime_hours']) && (!is_numeric($data['overtime_hours']) || $data['overtime_hours'] < 0)) {
            $errors['overtime_hours'] = 'Overtime hours must be a positive number';
        }

        if (!empty($data['overtime_rate']) && (!is_numeric($data['overtime_rate']) || $data['overtime_rate'] < 0)) {
            $errors['overtime_rate'] = 'Overtime rate must be a positive number';
        }

        return $errors;
    }
}
