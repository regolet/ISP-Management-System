<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Deduction;
use App\Models\Admin\Employee;

class DeductionController extends Controller {
    private $deductionModel;
    private $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->deductionModel = new Deduction();
        $this->employeeModel = new Employee();
    }

    /**
     * Display deductions list
     */
    public function index() {
        $types = $this->deductionModel->getDeductionTypes();
        $active_types = $this->deductionModel->getDeductionTypes(['is_active' => 1]);
        $deductions = $this->deductionModel->getEmployeeDeductions();
        $employees = $this->employeeModel->getActiveEmployees(true);

        return $this->view('admin/deductions/index', [
            'types' => $types,
            'active_types' => $active_types,
            'deductions' => $deductions,
            'employees' => $employees,
            'layout' => 'navbar',
            'title' => 'Deduction Management'
        ]);
    }

    /**
     * Save deduction type
     */
    public function saveType() {
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'type' => $_POST['type'],
            'calculation_type' => $_POST['calculation_type'],
            'percentage_value' => $_POST['percentage_value'] ?? null,
            'is_active' => 1
        ];

        // Validate input
        $errors = $this->deductionModel->validateType($data);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        try {
            if (!empty($_POST['id'])) {
                $this->deductionModel->update($_POST['id'], $data);
                $message = 'Deduction type updated successfully';
            } else {
                $this->deductionModel->create($data);
                $message = 'Deduction type created successfully';
            }

            return $this->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete deduction type
     */
    public function deleteType($id) {
        try {
            // Check if type is in use
            $sql = "SELECT COUNT(*) as count FROM employee_deductions WHERE deduction_type_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result['count'] > 0) {
                return $this->json([
                    'error' => 'Cannot delete: This deduction type is being used by employees'
                ], 400);
            }

            $this->deductionModel->delete($id);
            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save employee deduction
     */
    public function save() {
        $data = [
            'employee_id' => $_POST['employee_id'],
            'deduction_type_id' => $_POST['deduction_type_id'],
            'amount' => $_POST['amount'],
            'frequency' => $_POST['frequency'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?: null,
            'remarks' => $_POST['remarks']
        ];

        // Validate input
        $errors = $this->deductionModel->validateEmployeeDeduction($data);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        try {
            if (!empty($_POST['id'])) {
                $this->deductionModel->update($_POST['id'], $data);
                $message = 'Employee deduction updated successfully';
            } else {
                $this->deductionModel->addEmployeeDeduction($data);
                $message = 'Employee deduction created successfully';
            }

            return $this->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel employee deduction
     */
    public function cancel($id) {
        try {
            $this->deductionModel->cancelDeduction($id);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * View deduction history
     */
    public function history($id) {
        $deduction = $this->deductionModel->find($id);
        if (!$deduction) {
            $this->setFlash('error', 'Deduction not found');
            return $this->redirect('/admin/deductions');
        }

        $history = $this->deductionModel->getDeductionHistory($id);
        $stats = $this->deductionModel->getDeductionStats($id);

        return $this->view('admin/deductions/history', [
            'deduction' => $deduction,
            'history' => $history,
            'stats' => $stats,
            'total_deducted' => array_sum(array_column($history, 'amount')),
            'layout' => 'navbar',
            'title' => 'Deduction History'
        ]);
    }

    /**
     * Export deduction history
     */
    public function exportHistory($id) {
        $deduction = $this->deductionModel->find($id);
        if (!$deduction) {
            $this->setFlash('error', 'Deduction not found');
            return $this->redirect('/admin/deductions');
        }

        $history = $this->deductionModel->getDeductionHistory($id);
        
        // Generate CSV content
        $csv = "Date,Payroll Period,Amount,Reference,Status,Notes\n";
        foreach ($history as $record) {
            $csv .= implode(',', [
                date('Y-m-d', strtotime($record['transaction_date'])),
                $record['period_start'] ? 
                    date('Y-m-d', strtotime($record['period_start'])) . ' to ' . 
                    date('Y-m-d', strtotime($record['period_end'])) : 
                    'N/A',
                $record['amount'],
                '"' . str_replace('"', '""', $record['reference']) . '"',
                $record['status'],
                '"' . str_replace('"', '""', $record['notes']) . '"'
            ]) . "\n";
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="deduction_history_' . $id . '.csv"');
        
        echo $csv;
        exit;
    }
}
