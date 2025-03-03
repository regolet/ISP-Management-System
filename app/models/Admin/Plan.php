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
        'status'
    ];

    /**
     * Get all active plans
     */
    public function getActivePlans() {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY amount";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting active plans: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get plans with subscriber count
     */
    public function getPlansWithSubscribers() {
        try {
            $sql = "SELECT p.*, COALESCE(COUNT(c.id), 0) as subscribers
                    FROM {$this->table} p
                    LEFT JOIN customers c ON p.id = c.plan_id AND c.status != 'terminated'
                    GROUP BY p.id, p.name, p.description, p.bandwidth, p.amount, p.status, p.created_at, p.updated_at
                    ORDER BY p.amount";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting plans with subscribers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate plan data
     */
    public function validate($data) {
        $errors = [];

        // Required fields
        $required = [
            'name' => 'Plan name is required',
            'bandwidth' => 'Bandwidth is required',
            'amount' => 'Amount is required'
        ];

        foreach ($required as $field => $message) {
            if (empty($data[$field])) {
                $errors[$field] = $message;
            }
        }

        // Bandwidth validation
        if (!empty($data['bandwidth']) && (!is_numeric($data['bandwidth']) || $data['bandwidth'] <= 0)) {
            $errors['bandwidth'] = 'Bandwidth must be a positive number';
        }

        // Amount validation
        if (!empty($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors['amount'] = 'Amount must be a positive number';
        }

        // Check plan name uniqueness
        if (!empty($data['name'])) {
            $sql = "SELECT id FROM {$this->table} WHERE name = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->execute([$data['name'], $id]);
            if ($stmt->rowCount() > 0) {
                $errors['name'] = 'Plan name already exists';
            }
        }

        return $errors;
    }

    /**
     * Delete a plan
     */
    public function delete($id) {
        try {
            // Check if plan has subscribers
            $sql = "SELECT COUNT(*) FROM customers WHERE plan_id = ? AND status != 'terminated'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                throw new \Exception('Cannot delete plan that has active subscribers');
            }

            // Delete the plan
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            error_log("Error deleting plan: " . $e->getMessage());
            throw $e;
        }
    }
}
