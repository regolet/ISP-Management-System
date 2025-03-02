<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Admin\Deduction;

class DeductionController extends Controller {
    private $deductionModel;

    public function __construct() {
        parent::__construct();
        $this->deductionModel = new Deduction();
    }

    /**
     * Get all deduction types
     */
    public function types() {
        try {
            $types = $this->deductionModel->getDeductionTypes([
                'is_active' => $_GET['active'] ?? true
            ]);
            return $this->jsonResponse([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee deductions
     */
    public function employeeDeductions($employeeId) {
        try {
            $deductions = $this->deductionModel->getEmployeeDeductions([
                'employee_id' => $employeeId,
                'status' => $_GET['status'] ?? 'active'
            ]);
            return $this->jsonResponse([
                'success' => true,
                'data' => $deductions
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deduction history
     */
    public function history($deductionId) {
        try {
            $history = $this->deductionModel->getDeductionHistory($deductionId);
            $stats = $this->deductionModel->getDeductionStats($deductionId);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'history' => $history,
                    'stats' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate deduction amount
     */
    public function calculate() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['type_id']) || !isset($data['base_amount'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing required parameters'
                ], 400);
            }

            // Get deduction type
            $type = $this->deductionModel->find($data['type_id']);
            if (!$type) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid deduction type'
                ], 404);
            }

            // Calculate amount
            $amount = 0;
            if ($type['calculation_type'] === 'percentage') {
                $amount = $data['base_amount'] * ($type['percentage_value'] / 100);
            } else {
                $amount = $type['fixed_amount'];
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'amount' => $amount,
                    'calculation' => [
                        'type' => $type['calculation_type'],
                        'base_amount' => $data['base_amount'],
                        'value' => $type['calculation_type'] === 'percentage' ? 
                                 $type['percentage_value'] . '%' : 
                                 $type['fixed_amount']
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deduction summary for payroll
     */
    public function payrollSummary($employeeId, $startDate, $endDate) {
        try {
            $sql = "SELECT 
                        dt.name,
                        dt.type,
                        SUM(dh.amount) as total_amount,
                        COUNT(*) as occurrences
                    FROM deduction_history dh
                    JOIN employee_deductions ed ON dh.employee_deduction_id = ed.id
                    JOIN deduction_types dt ON ed.deduction_type_id = dt.id
                    WHERE ed.employee_id = ?
                    AND dh.transaction_date BETWEEN ? AND ?
                    AND dh.status = 'completed'
                    GROUP BY dt.id, dt.name, dt.type
                    ORDER BY dt.type, dt.name";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iss', $employeeId, $startDate, $endDate);
            $stmt->execute();
            $summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            return $this->jsonResponse([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to send JSON response
     */
    private function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
