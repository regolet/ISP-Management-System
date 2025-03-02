<?php
namespace App\Models\Staff;

use App\Core\Model;

class Expense extends Model {
    protected $table = 'staff_expenses';
    protected $primaryKey = 'id';
    protected $fillable = [
        'staff_id',
        'category_id',
        'amount',
        'date',
        'description',
        'reference_number',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
        'created_at',
        'updated_at'
    ];

    /**
     * Get staff expense history
     */
    public function getExpenseHistory($staffId, $startDate = null, $endDate = null) {
        $sql = "SELECT e.*,
                       c.name as category_name,
                       u.username as approved_by_name
                FROM {$this->table} e
                LEFT JOIN expense_categories c ON e.category_id = c.id
                LEFT JOIN users u ON e.approved_by = u.id
                WHERE e.staff_id = ?";
        
        $params = [$staffId];
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

    /**
     * Get expense details
     */
    public function getExpenseDetails($expenseId) {
        $sql = "SELECT e.*,
                       c.name as category_name,
                       u.username as approved_by_name,
                       a.file_path as attachment_path
                FROM {$this->table} e
                LEFT JOIN expense_categories c ON e.category_id = c.id
                LEFT JOIN users u ON e.approved_by = u.id
                LEFT JOIN expense_attachments a ON e.id = a.expense_id
                WHERE e.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $expenseId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Add expense attachment
     */
    public function addAttachment($expenseId, $file) {
        $sql = "INSERT INTO expense_attachments (
                    expense_id,
                    file_name,
                    file_path,
                    file_type,
                    file_size,
                    uploaded_at
                ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isssi',
            $expenseId,
            $file['name'],
            $file['path'],
            $file['type'],
            $file['size']
        );
        return $stmt->execute();
    }

    /**
     * Get expense categories
     */
    public function getCategories() {
        $sql = "SELECT * FROM expense_categories WHERE status = 'active' ORDER BY name";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get expense summary
     */
    public function getExpenseSummary($staffId, $period = 'month') {
        $sql = "SELECT 
                    COUNT(*) as total_expenses,
                    SUM(amount) as total_amount,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                    SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as rejected_amount
                FROM {$this->table}
                WHERE staff_id = ?";

        if ($period === 'month') {
            $sql .= " AND MONTH(date) = MONTH(CURRENT_DATE) 
                      AND YEAR(date) = YEAR(CURRENT_DATE)";
        } elseif ($period === 'year') {
            $sql .= " AND YEAR(date) = YEAR(CURRENT_DATE)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get expense report
     */
    public function getExpenseReport($staffId, $startDate, $endDate) {
        $sql = "SELECT e.*,
                       c.name as category_name,
                       u.username as approved_by_name
                FROM {$this->table} e
                LEFT JOIN expense_categories c ON e.category_id = c.id
                LEFT JOIN users u ON e.approved_by = u.id
                WHERE e.staff_id = ?
                AND e.date BETWEEN ? AND ?
                ORDER BY e.date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $staffId, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get category summary
     */
    public function getCategorySummary($staffId, $startDate, $endDate) {
        $sql = "SELECT c.name as category_name,
                       COUNT(*) as expense_count,
                       SUM(e.amount) as total_amount
                FROM {$this->table} e
                JOIN expense_categories c ON e.category_id = c.id
                WHERE e.staff_id = ?
                AND e.date BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY total_amount DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $staffId, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Validate expense data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Category is required';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        if (empty($data['date']) || !strtotime($data['date'])) {
            $errors['date'] = 'Valid date is required';
        }

        if (empty($data['description'])) {
            $errors['description'] = 'Description is required';
        }

        return $errors;
    }
}
