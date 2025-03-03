<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Admin\Plan;

class PlanController extends Controller {
    private $planModel;

    public function __construct() {
        parent::__construct();
        $this->planModel = new Plan();
    }

    /**
     * Get all active plans
     */
    public function index() {
        try {
            $plans = $this->planModel->getActivePlans();
            return $this->jsonResponse([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plan details
     */
    public function show($id) {
        try {
            $plan = $this->planModel->find($id);
            if (!$plan) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Plan not found'
                ], 404);
            }

            // Get plan statistics
            $stats = $this->planModel->getPlanStats($id);
            $plan['stats'] = $stats;

            return $this->jsonResponse([
                'success' => true,
                'data' => $plan
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plan subscribers
     */
    public function subscribers($id) {
        try {
            $subscribers = $this->planModel->getPlanSubscribers($id);
            return $this->jsonResponse([
                'success' => true,
                'data' => $subscribers
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search plans
     */
    public function search() {
        try {
            $query = $_GET['q'] ?? '';
            $filters = [
                'bandwidth_min' => $_GET['bandwidth_min'] ?? null,
                'bandwidth_max' => $_GET['bandwidth_max'] ?? null,
                'price_min' => $_GET['price_min'] ?? null,
                'price_max' => $_GET['price_max'] ?? null,
                'status' => $_GET['status'] ?? 'active'
            ];

            $plans = $this->planModel->searchPlans($query, $filters);
            return $this->jsonResponse([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare plans
     */
    public function compare() {
        try {
            $planIds = $_GET['plans'] ?? [];
            if (empty($planIds)) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'No plans selected for comparison'
                ], 400);
            }

            $plans = [];
            foreach ($planIds as $id) {
                $plan = $this->planModel->find($id);
                if ($plan) {
                    $plans[] = $plan;
                }
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to send JSON response
     */
    private function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
