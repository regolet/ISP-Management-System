<?php
namespace App\Models\Staff;

use App\Core\Model;

class Payment extends Model {
    protected $table = 'staff_payments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'staff_id',
        'amount',
        'payment_type',
        'payment_method',
        'reference_number',
        'payment_date',
        'description',
        'status',
        'receipt_number',
        'processed_by',
        'remarks',
        'created_at',
        'updated_at'
    ];

    /**
     * Get staff payment history
     */
    public function getPaymentHistory($staffId, $startDate = null, $endDate = null) {
        $sql = "SELECT p.*,
                       pt.name as payment_type_name,
                       pm.name as payment_method_name,
                       u.username as processed_by_name
                FROM {$this->table} p
                LEFT JOIN payment_types pt ON p.payment_type = pt.id
                LEFT JOIN payment_methods pm ON p.payment_method = pm.id
                LEFT JOIN users u ON p.processed_by = u.id
                WHERE p.staff_id = ?";
        
        $params = [$staffId];
        $types = 'i';

        if ($startDate && $endDate) {
            $sql .= " AND p.payment_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }

        $sql .= " ORDER BY p.payment_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails($paymentId) {
        $sql = "SELECT p.*,
                       s.first_name,
                       s.last_name,
                       s.employee_id,
                       pt.name as payment_type_name,
                       pm.name as payment_method_name,
                       u.username as processed_by_name,
                       a.file_path as attachment_path
                FROM {$this->table} p
                JOIN staff s ON p.staff_id = s.id
                LEFT JOIN payment_types pt ON p.payment_type = pt.id
                LEFT JOIN payment_methods pm ON p.payment_method = pm.id
                LEFT JOIN users u ON p.processed_by = u.id
                LEFT JOIN payment_attachments a ON p.id = a.payment_id
                WHERE p.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Generate receipt
     */
    public function generateReceipt($paymentId) {
        $payment = $this->getPaymentDetails($paymentId);
        if (!$payment) {
            return false;
        }

        // Generate unique receipt number if not exists
        if (empty($payment['receipt_number'])) {
            $receiptNumber = $this->generateReceiptNumber();
            $this->update($paymentId, ['receipt_number' => $receiptNumber]);
            $payment['receipt_number'] = $receiptNumber;
        }

        return [
            'receipt_number' => $payment['receipt_number'],
            'payment_date' => $payment['payment_date'],
            'amount' => $payment['amount'],
            'payment_method' => $payment['payment_method_name'],
            'reference_number' => $payment['reference_number'],
            'staff' => [
                'name' => $payment['first_name'] . ' ' . $payment['last_name'],
                'id' => $payment['employee_id']
            ],
            'description' => $payment['description'],
            'processed_by' => $payment['processed_by_name'],
            'status' => $payment['status']
        ];
    }

    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber() {
        $prefix = date('Ym');
        
        // Get last receipt number
        $sql = "SELECT receipt_number 
                FROM {$this->table} 
                WHERE receipt_number LIKE ?
                ORDER BY id DESC 
                LIMIT 1";
        
        $pattern = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            // Increment last number
            $lastNumber = intval(substr($result['receipt_number'], -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Start with 0001
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Add payment attachment
     */
    public function addAttachment($paymentId, $file) {
        $sql = "INSERT INTO payment_attachments (
                    payment_id,
                    file_name,
                    file_path,
                    file_type,
                    file_size,
                    uploaded_at
                ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isssi',
            $paymentId,
            $file['name'],
            $file['path'],
            $file['type'],
            $file['size']
        );
        return $stmt->execute();
    }

    /**
     * Search payments
     */
    public function searchPayments($query, $filters = []) {
        $sql = "SELECT p.*,
                       s.first_name,
                       s.last_name,
                       s.employee_id,
                       pt.name as payment_type_name
                FROM {$this->table} p
                JOIN staff s ON p.staff_id = s.id
                LEFT JOIN payment_types pt ON p.payment_type = pt.id
                WHERE (s.first_name LIKE ? OR 
                      s.last_name LIKE ? OR 
                      s.employee_id LIKE ? OR
                      p.receipt_number LIKE ? OR
                      p.reference_number LIKE ?)";
        
        $searchTerm = "%{$query}%";
        $params = array_fill(0, 5, $searchTerm);
        $types = 'sssss';

        // Add filters
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['payment_type'])) {
            $sql .= " AND p.payment_type = ?";
            $params[] = $filters['payment_type'];
            $types .= 'i';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        $sql .= " ORDER BY p.payment_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Validate payment data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['staff_id'])) {
            $errors['staff_id'] = 'Staff ID is required';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        if (empty($data['payment_type'])) {
            $errors['payment_type'] = 'Payment type is required';
        }

        if (empty($data['payment_method'])) {
            $errors['payment_method'] = 'Payment method is required';
        }

        if (empty($data['payment_date']) || !strtotime($data['payment_date'])) {
            $errors['payment_date'] = 'Valid payment date is required';
        }

        if (!empty($data['reference_number'])) {
            // Check if reference number is unique
            $sql = "SELECT id FROM {$this->table} WHERE reference_number = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['reference_number'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['reference_number'] = 'Reference number must be unique';
            }
        }

        return $errors;
    }
}
