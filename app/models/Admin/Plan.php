<?php
namespace App\Models\Admin;

use App\Core\Model;

class Plan extends Model {
    protected $table = 'plans';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'bandwidth',
        'amount',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Get all plans with subscriber count
     */
    public function getPlansWithSubscribers() {
        $sql = "SELECT p.*, 
                (SELECT COUNT(*) FROM subscriptions 
                 WHERE plan_id = p.id AND status = 'active') as subscriber_count 
                FROM {$this->table} p 
                ORDER BY p.name";
        
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    /**
     * Check if plan can be deactivated
     */
    public function canDeactivate($id) {
        $sql = "SELECT COUNT(*) as active_subscribers 
                FROM subscriptions 
                WHERE plan_id = ? AND status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['active_subscribers'] == 0;
    }

    /**
     * Validate plan data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Plan name is required';
        }

        if (empty($data['description'])) {
            $errors['description'] = 'Description is required';
        }

        if (empty($data['bandwidth']) || !is_numeric($data['bandwidth']) || $data['bandwidth'] <= 0) {
            $errors['bandwidth'] = 'Valid bandwidth is required';
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }

        // Check name uniqueness
        if (!empty($data['name'])) {
            $sql = "SELECT id FROM {$this->table} WHERE name = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['name'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['name'] = 'Plan name already exists';
            }
        }

        return $errors;
    }

    /**
     * Get active plans
     */
    public function getActivePlans() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY amount";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get plan details with subscriber count
     */
    public function getPlanDetails($id) {
        $sql = "SELECT p.*, 
                (SELECT COUNT(*) FROM subscriptions 
                 WHERE plan_id = p.id AND status = 'active') as subscriber_count 
                FROM {$this->table} p 
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
