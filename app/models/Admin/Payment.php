<?php
namespace App\Models\Admin;

use App\Core\Model;

class Payment extends Model {
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'billing_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_no',
        'status',
        'notes',
        'created_by',
        'created_at',
        'updated_at'
    ];

    /**
     * Get payments with details
     */
    public function getPayments($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(b.invoiceid LIKE ? OR c.name LIKE ? OR c.customer_code LIKE ? OR p.reference_no LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
            $types .= 'ssss';
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['payment_method'])) {
            $where[] = "p.payment_method = ?";
            $params[] = $filters['payment_method'];
            $types .= 's';
        }

        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $where[] = "DATE(p.payment_date) = CURRENT_DATE";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(p.payment_date) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'month':
                    $where[] = "YEAR(p.payment_date) = YEAR(CURRENT_DATE) AND MONTH(p.payment_date) = MONTH(CURRENT_DATE)";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} p 
                     LEFT JOIN billing b ON p.billing_id = b.id
                     LEFT JOIN customers c ON b.customer_id = c.id
                     WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get payments
        $sql = "SELECT p.*, 
                       b.invoiceid,
                       b.amount as invoice_amount,
                       c.name as customer_name,
                       c.customer_code,
                       u.username as created_by_name
                FROM {$this->table} p 
                LEFT JOIN billing b ON p.billing_id = b.id
                LEFT JOIN customers c ON b.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE {$whereClause}
                ORDER BY p.payment_date DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'payments' => $payments,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails($id) {
        $sql = "SELECT p.*, 
                       b.invoiceid,
                       b.amount as invoice_amount,
                       b.due_date,
                       c.name as customer_name,
                       c.customer_code,
                       c.email,
                       c.phone,
                       u.username as created_by_name
                FROM {$this->table} p 
                LEFT JOIN billing b ON p.billing_id = b.id
                LEFT JOIN customers c ON b.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Record new payment
     */
    public function recordPayment($data) {
        $this->db->getConnection()->begin_transaction();

        try {
            // Create payment record
            $paymentId = $this->create($data);

            // Update billing status
            $billingModel = new Billing();
            $billingModel->updateStatus($data['billing_id']);

            $this->db->getConnection()->commit();
            return $paymentId;

        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Void payment
     */
    public function voidPayment($id) {
        $payment = $this->find($id);
        if (!$payment) {
            throw new \Exception('Payment not found');
        }

        $this->db->getConnection()->begin_transaction();

        try {
            // Update payment status
            $this->update($id, ['status' => 'void']);

            // Update billing status
            $billingModel = new Billing();
            $billingModel->updateStatus($payment['billing_id']);

            $this->db->getConnection()->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Validate payment data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['billing_id'])) {
            $errors['billing_id'] = 'Invoice is required';
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        if (empty($data['payment_date'])) {
            $errors['payment_date'] = 'Payment date is required';
        }

        if (empty($data['payment_method'])) {
            $errors['payment_method'] = 'Payment method is required';
        }

        // Check if payment amount exceeds remaining balance
        if (!empty($data['billing_id']) && !empty($data['amount'])) {
            $sql = "SELECT b.amount - COALESCE(SUM(p.amount), 0) as remaining
                    FROM billing b 
                    LEFT JOIN payments p ON b.id = p.billing_id AND p.status != 'void'
                    WHERE b.id = ?
                    GROUP BY b.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $data['billing_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result && $data['amount'] > $result['remaining']) {
                $errors['amount'] = 'Payment amount exceeds remaining balance';
            }
        }

        return $errors;
    }

    /**
     * Get payment methods
     */
    public static function getPaymentMethods() {
        return [
            'cash' => 'Cash',
            'check' => 'Check',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'online' => 'Online Payment'
        ];
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue() {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE status = 'completed'";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get monthly revenue
     */
    public function getMonthlyRevenue() {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE status = 'completed' 
                AND YEAR(payment_date) = YEAR(CURRENT_DATE)
                AND MONTH(payment_date) = MONTH(CURRENT_DATE)";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    /**
     * Get revenue chart data
     */
    public function getRevenueChartData($period = 'month') {
        $sql = match($period) {
            'year' => "SELECT 
                        DATE_FORMAT(payment_date, '%Y-%m') as period,
                        COALESCE(SUM(amount), 0) as total
                      FROM {$this->table}
                      WHERE status = 'completed'
                      AND payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                      GROUP BY period
                      ORDER BY period",
            'month' => "SELECT 
                        DATE_FORMAT(payment_date, '%Y-%m-%d') as period,
                        COALESCE(SUM(amount), 0) as total
                      FROM {$this->table}
                      WHERE status = 'completed'
                      AND payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                      GROUP BY period
                      ORDER BY period",
            'week' => "SELECT 
                        DATE_FORMAT(payment_date, '%Y-%m-%d') as period,
                        COALESCE(SUM(amount), 0) as total
                      FROM {$this->table}
                      WHERE status = 'completed'
                      AND payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                      GROUP BY period
                      ORDER BY period",
            default => throw new \Exception('Invalid period')
        };

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get payment status distribution
     */
    public function getStatusDistribution() {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total
                FROM {$this->table}
                GROUP BY status";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
