<?php
namespace App\Models\Admin;

use App\Core\Model;

class Billing extends Model {
    protected $table = 'billing';
    protected $primaryKey = 'id';
    protected $fillable = [
        'customer_id',
        'invoiceid',
        'amount',
        'due_date',
        'status',
        'description',
        'created_at',
        'updated_at'
    ];

    /**
     * Get billing records with customer details
     */
    public function getBillingRecords($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(b.invoiceid LIKE ? OR c.name LIKE ? OR c.customer_code LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
            $types .= 'sss';
        }

        if (!empty($filters['status'])) {
            $where[] = "b.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'overdue':
                    $where[] = "b.due_date < CURRENT_DATE AND b.status != 'paid'";
                    break;
                case 'today':
                    $where[] = "DATE(b.due_date) = CURRENT_DATE";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(b.due_date) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'month':
                    $where[] = "YEAR(b.due_date) = YEAR(CURRENT_DATE) AND MONTH(b.due_date) = MONTH(CURRENT_DATE)";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} b 
                     LEFT JOIN customers c ON b.customer_id = c.id 
                     WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get billing records
        $sql = "SELECT b.*, 
                       c.name as customer_name,
                       c.customer_code,
                       p.name as plan_name,
                       p.amount as plan_amount,
                       (SELECT SUM(amount) FROM payments WHERE billing_id = b.id) as paid_amount
                FROM {$this->table} b 
                LEFT JOIN customers c ON b.customer_id = c.id
                LEFT JOIN plans p ON c.plan_id = p.id
                WHERE {$whereClause}
                ORDER BY b.due_date ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'bills' => $bills,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get billing details with payments
     */
    public function getBillingDetails($id) {
        $sql = "SELECT b.*, 
                       c.name as customer_name,
                       c.customer_code,
                       c.email,
                       c.phone,
                       c.address,
                       p.name as plan_name,
                       p.amount as plan_amount
                FROM {$this->table} b 
                LEFT JOIN customers c ON b.customer_id = c.id
                LEFT JOIN plans p ON c.plan_id = p.id
                WHERE b.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();

        if ($bill) {
            // Get payments for this bill
            $sql = "SELECT * FROM payments WHERE billing_id = ? ORDER BY payment_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $bill['payments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $bill;
    }

    /**
     * Generate invoice ID
     */
    public function generateInvoiceId() {
        $prefix = date('Ym');
        
        $sql = "SELECT invoiceid FROM {$this->table} 
                WHERE invoiceid LIKE ? 
                ORDER BY id DESC LIMIT 1";
        
        $pattern = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $lastNumber = intval(substr($result['invoiceid'], -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Create new billing record
     */
    public function createBilling($data) {
        // Generate invoice ID
        $data['invoiceid'] = $this->generateInvoiceId();
        
        // Set initial status
        $data['status'] = 'unpaid';

        return $this->create($data);
    }

    /**
     * Update billing status based on payments
     */
    public function updateStatus($id) {
        $sql = "SELECT b.amount, COALESCE(SUM(p.amount), 0) as paid_amount 
                FROM {$this->table} b 
                LEFT JOIN payments p ON b.id = p.billing_id 
                WHERE b.id = ?
                GROUP BY b.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $status = 'unpaid';
            if ($result['paid_amount'] >= $result['amount']) {
                $status = 'paid';
            } elseif ($result['paid_amount'] > 0) {
                $status = 'partial';
            }

            $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $status, $id);
            return $stmt->execute();
        }

        return false;
    }

    /**
     * Validate billing data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['customer_id'])) {
            $errors['customer_id'] = 'Customer is required';
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        if (empty($data['due_date'])) {
            $errors['due_date'] = 'Due date is required';
        } elseif (strtotime($data['due_date']) < strtotime('today')) {
            $errors['due_date'] = 'Due date cannot be in the past';
        }

        return $errors;
    }
}
