<?php
namespace App\Models;

use App\Core\Model;

class Customer extends Model {
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'status',
        'created_at',
        'updated_at'
    ];

    // Get customer's active subscriptions
    public function getSubscriptions() {
        $sql = "SELECT s.* FROM subscriptions s 
                WHERE s.customer_id = ? AND s.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get customer's billing history
    public function getBillingHistory() {
        $sql = "SELECT b.*, p.amount as paid_amount, p.payment_date 
                FROM billing b 
                LEFT JOIN payments p ON b.id = p.billing_id 
                WHERE b.customer_id = ? 
                ORDER BY b.billing_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get customer's network devices (ONUs)
    public function getNetworkDevices() {
        $sql = "SELECT o.*, n.name as nap_name 
                FROM onus o 
                LEFT JOIN nap_boxes n ON o.nap_id = n.id 
                WHERE o.customer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Search customers by various criteria
    public function search($criteria) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $types = '';
        $values = [];

        if (!empty($criteria['name'])) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ?)";
            $searchTerm = "%{$criteria['name']}%";
            $types .= 'ss';
            $values[] = $searchTerm;
            $values[] = $searchTerm;
        }

        if (!empty($criteria['email'])) {
            $sql .= " AND email LIKE ?";
            $types .= 's';
            $values[] = "%{$criteria['email']}%";
        }

        if (!empty($criteria['status'])) {
            $sql .= " AND status = ?";
            $types .= 's';
            $values[] = $criteria['status'];
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get customer's current balance
    public function getCurrentBalance() {
        $sql = "SELECT 
                    COALESCE(SUM(b.amount), 0) - COALESCE(SUM(p.amount), 0) as balance 
                FROM billing b 
                LEFT JOIN payments p ON b.id = p.billing_id 
                WHERE b.customer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['balance'] ?? 0;
    }

    // Validate customer data
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

        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        }

        return $errors;
    }
}
