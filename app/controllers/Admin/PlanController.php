<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Plan;

class PlanController extends Controller {
    private $planModel;

    public function __construct() {
        parent::__construct();
        $this->planModel = new Plan();
    }

    /**
     * Display plans list
     */
    public function index() {
        $plans = $this->planModel->getPlansWithSubscribers();
        
        return $this->view('admin/plans/index', [
            'plans' => $plans
        ]);
    }

    /**
     * Show plan creation form
     */
    public function create() {
        return $this->view('admin/plans/create');
    }

    /**
     * Store new plan
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->planModel->validate($data);
        if (!empty($errors)) {
            return $this->view('admin/plans/create', [
                'errors' => $errors,
                'data' => $data
            ]);
        }

        try {
            $this->planModel->create($data);
            $this->setFlash('success', 'Plan created successfully');
            return $this->redirect('/admin/plans');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to create plan: ' . $e->getMessage());
            return $this->view('admin/plans/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data
            ]);
        }
    }

    /**
     * Show plan edit form
     */
    public function edit($id) {
        $plan = $this->planModel->find($id);
        if (!$plan) {
            $this->setFlash('error', 'Plan not found');
            return $this->redirect('/admin/plans');
        }

        return $this->view('admin/plans/edit', [
            'plan' => $plan
        ]);
    }

    /**
     * Update plan
     */
    public function update($id) {
        $plan = $this->planModel->find($id);
        if (!$plan) {
            $this->setFlash('error', 'Plan not found');
            return $this->redirect('/admin/plans');
        }

        $data = $this->getRequestData();
        $data['id'] = $id;

        // Validate input
        $errors = $this->planModel->validate($data);
        if (!empty($errors)) {
            return $this->view('admin/plans/edit', [
                'errors' => $errors,
                'data' => $data,
                'plan' => $plan
            ]);
        }

        try {
            $this->planModel->update($id, $data);
            $this->setFlash('success', 'Plan updated successfully');
            return $this->redirect('/admin/plans');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to update plan: ' . $e->getMessage());
            return $this->view('admin/plans/edit', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'plan' => $plan
            ]);
        }
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus($id) {
        $plan = $this->planModel->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found'], 404);
        }

        $newStatus = $plan['status'] === 'active' ? 'inactive' : 'active';

        // Check if plan can be deactivated
        if ($newStatus === 'inactive' && !$this->planModel->canDeactivate($id)) {
            return $this->json([
                'error' => 'Cannot deactivate plan: Plan has active subscribers'
            ], 400);
        }

        try {
            $this->planModel->toggleStatus($id, $newStatus);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get plan details
     */
    public function getPlanDetails($id) {
        $plan = $this->planModel->getPlanDetails($id);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found'], 404);
        }

        return $this->json($plan);
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        return [
            'name' => $_POST['name'] ?? null,
            'description' => $_POST['description'] ?? null,
            'bandwidth' => $_POST['bandwidth'] ?? null,
            'amount' => $_POST['amount'] ?? null
        ];
    }
}
