<?php
namespace App\Models\Asset;

use App\Core\Model;

class AssetExpense extends Model {
    protected $table = 'asset_expenses';
    protected $primaryKey = 'id';
    protected $fillable = [
        'asset_id',
        'expense_type',
        'amount',
        'date',
        'vendor',
        'invoice_number',
        'description',
        'payment_status',
        'payment_method',
        'receipt_file',
        'approved_by',
        'approved_at',
        'created_by',
        'created_at',
        'updated_at'
    ];

    // Get expense details with asset and approver information
    public function getDetails() {
        $sql = "SELECT ae.*, 
                       a.name as asset_name,
                       a.serial_number,
                       u1.name as created_by_name,
                       u2.name as approved_by_name
                FROM {$this->table} ae
                LEFT JOIN assets a ON ae.asset_id = a.id
                LEFT JOIN users u1 ON ae.created_by = u1.id
                LEFT JOIN users u2 ON ae.approved_by = u2.id
                WHERE ae.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get expenses by asset
    public function getExpensesByAsset($assetId, $startDate = null, $endDate = null) {
        $sql = "SELECT ae.*, 
                       u1.name as created_by_name,
                       u2.name as approved_by_name
                FROM {$this->table} ae
                LEFT JOIN users u1 ON ae.created_by = u1.id
                LEFT JOIN users u2 ON ae.approved_by = u2.id
                WHERE ae.asset_id = ?";
        
        $params = [$assetId];
        $types = 'i';
        
        if ($startDate && $endDate) {
            $sql .= " AND ae.date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY ae.date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Approve expense
    public function approve($approverId, $notes = '') {
        if ($this->approved_by) {
            throw new \Exception('Expense already approved');
        }

        $sql = "UPDATE {$this->table} 
                SET approved_by = ?,
                    approved_at = NOW(),
                    description = CONCAT(description, '\nApproval Notes: ', ?),
                    updated_at = NOW()
                WHERE id = ? AND approved_by IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isi', $approverId, $notes, $this->id);
        return $stmt->execute();
    }

    // Get total expenses for an asset
    public function getTotalExpenses($assetId, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(amount) as total_amount,
                    expense_type,
                    payment_status
                FROM {$this->table}
                WHERE asset_id = ?";
        
        $params = [$assetId];
        $types = 'i';
        
        if ($startDate && $endDate) {
            $sql .= " AND date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }
        
        $sql .= " GROUP BY expense_type, payment_status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Upload receipt
    public function uploadReceipt($fileData) {
        if (!isset($fileData['tmp_name']) || !isset($fileData['name'])) {
            throw new \Exception('Invalid file data');
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $fileData['name']);
        $uploadDir = 'uploads/receipts/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($fileData['tmp_name'], $filePath)) {
            $sql = "UPDATE {$this->table} 
                    SET receipt_file = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $fileName, $this->id);
            return $stmt->execute();
        }
        
        return false;
    }

    // Validate expense data
    public function validate($data) {
        $errors = [];

        if (empty($data['asset_id'])) {
            $errors['asset_id'] = 'Asset is required';
        }

        if (empty($data['expense_type'])) {
            $errors['expense_type'] = 'Expense type is required';
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        if (empty($data['date'])) {
            $errors['date'] = 'Date is required';
        } elseif (!strtotime($data['date'])) {
            $errors['date'] = 'Invalid date format';
        }

        if (empty($data['vendor'])) {
            $errors['vendor'] = 'Vendor is required';
        }

        if (empty($data['payment_status'])) {
            $errors['payment_status'] = 'Payment status is required';
        }

        return $errors;
    }

    // Get expense statistics
    public function getStatistics($assetId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_expenses,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    expense_type,
                    payment_status,
                    DATE_FORMAT(date, '%Y-%m') as month
                FROM {$this->table}
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($assetId) {
            $sql .= " AND asset_id = ?";
            $params[] = $assetId;
            $types .= 'i';
        }
        
        if ($startDate && $endDate) {
            $sql .= " AND date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }
        
        $sql .= " GROUP BY expense_type, payment_status, month
                  ORDER BY month DESC, expense_type";
        
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
