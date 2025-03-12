<?php
namespace App\Controllers;

require_once dirname(__DIR__) . '/Models/Subscription.php';

class SubscriptionController {
    private $db;
    private $subscription;

    public function __construct($db) {
        $this->db = $db;
        $this->subscription = new \App\Models\Subscription($db);
    }

    /**
     * Get all subscriptions with optional filtering and pagination
     */
    public function getSubscriptions($params = []) {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'ASC';
        $client_id = $params['client_id'] ?? null;

        // If client_id is provided, we need to modify the query
        if ($client_id) {
            try {
                // For now, we'll use the existing getAll method
                // In a real-world scenario, you might want to add a specific method for this
                $subscriptions = $this->subscription->getAll($page, $per_page, $search, $status, $sort, $order);
                
                // Check if client_id column exists in the results
                $hasClientIdColumn = false;
                if (!empty($subscriptions)) {
                    $hasClientIdColumn = array_key_exists('client_id', $subscriptions[0]);
                }
                
                if ($hasClientIdColumn) {
                    // Filter subscriptions for the specific client
                    $subscriptions = array_filter($subscriptions, function($sub) use ($client_id) {
                        return $sub['client_id'] == $client_id;
                    });
                } else {
                    // If client_id column doesn't exist, return empty array
                    // In a real system, you might want to add the column or handle differently
                    $subscriptions = [];
                }
                
                $total = count($subscriptions);
                
                return [
                    'subscriptions' => array_values($subscriptions), // Reset array keys
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total / $per_page)
                ];
            } catch (\Exception $e) {
                error_log("Error in getSubscriptions: " . $e->getMessage());
                return [
                    'subscriptions' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => 0
                ];
            }
        } else {
            // Regular subscription listing
            $subscriptions = $this->subscription->getAll($page, $per_page, $search, $status, $sort, $order);
            $total = $this->subscription->getTotal($search, $status);

            return [
                'data' => $subscriptions,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ];
        }
    }

    /**
     * Get single subscription by ID
     */
    public function getSubscription($id) {
        return $this->subscription->getById($id);
    }

    /**
     * Create new subscription
     */
    public function createSubscription($data) {
        try {
            // Verify CSRF token
            if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Invalid CSRF token');
            }

            $this->validateSubscriptionData($data);

            foreach ($data as $key => $value) {
                if (property_exists($this->subscription, $key)) {
                    $this->subscription->$key = $value;
                }
            }

            // Set default values if not provided
            $this->subscription->status = $data['status'] ?? 'active';
            $this->subscription->start_date = $data['start_date'] ?? date('Y-m-d');
            $this->subscription->billing_cycle = $data['billing_cycle'] ?? 'monthly';

            if ($this->subscription->create()) {
                // Log activity
                $this->logActivity(
                    'subscription_created',
                    "New subscription created: {$this->subscription->subscription_number}",
                    $data['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Subscription created successfully',
                    'subscription_id' => $this->subscription->id
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create subscription'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing subscription
     */
    public function updateSubscription($id, $data) {
        try {
            $this->validateSubscriptionData($data, true);

            // Get existing subscription
            $existingSubscription = $this->subscription->getById($id);
            if (!$existingSubscription) {
                throw new \Exception('Subscription not found');
            }

            $this->subscription->id = $id;
            foreach ($data as $key => $value) {
                if (property_exists($this->subscription, $key)) {
                    $this->subscription->$key = $value === '' ? null : $value;
                }
            }

            // Keep existing values for fields not provided in update
            foreach ($existingSubscription as $key => $value) {
                if (!isset($data[$key]) && property_exists($this->subscription, $key)) {
                    $this->subscription->$key = $value;
                }
            }

            if ($this->subscription->update()) {
                // Log activity
                $this->logActivity(
                    'subscription_updated',
                    "Subscription updated: {$this->subscription->subscription_number}",
                    $data['client_id'] ?? $existingSubscription['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Subscription updated successfully',
                    'subscription' => $this->subscription->getById($id)
                ];
            }

            throw new \Exception('Failed to update subscription');

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete subscription
     */
    public function deleteSubscription($id) {
        try {
            $subscriptionData = $this->subscription->getById($id);
            if (!$subscriptionData) {
                throw new \Exception('Subscription not found');
            }

            if ($this->subscription->delete($id)) {
                // Log activity
                $this->logActivity(
                    'subscription_deleted',
                    "Subscription deleted: {$subscriptionData['subscription_number']}",
                    $subscriptionData['client_id']
                );

                return [
                    'success' => true,
                    'message' => 'Subscription deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete subscription'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get subscription statistics
     */
    public function getStats() {
        return $this->subscription->getStats();
    }

    /**
     * Validate subscription data
     */
    private function validateSubscriptionData($data, $isUpdate = false) {
        $errors = [];

        if (!$isUpdate) {
            // These fields are required only for new subscriptions
            if (empty($data['client_id']) || !is_numeric($data['client_id'])) {
                $errors[] = 'Valid client ID is required';
            }

            // Check for plan_id
            if (empty($data['plan_id']) || !is_numeric($data['plan_id'])) {
                $errors[] = 'Plan is required';
            }
        }

        // For updates, validate fields only if they are provided
        if (isset($data['status']) && $data['status'] !== null && !in_array($data['status'], ['active', 'suspended', 'cancelled'])) {
            $errors[] = 'Invalid status value';
        }

        if (isset($data['billing_cycle']) && $data['billing_cycle'] !== null && !in_array($data['billing_cycle'], ['monthly', 'quarterly', 'yearly'])) {
            $errors[] = 'Invalid billing cycle value';
        }

        if (isset($data['start_date']) && $data['start_date'] !== null && !strtotime($data['start_date'])) {
            $errors[] = 'Invalid start date format';
        }

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }

    /**
     * Log activity
     */
    private function logActivity($type, $description, $client_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs 
                (user_id, client_id, activity_type, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $client_id,
                $type,
                $description,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

        } catch (\Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
