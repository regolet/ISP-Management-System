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
            'plans' => $plans,
            'layout' => 'navbar',
            'title' => 'Manage Plans'
        ]);
    }

    /**
     * Show plan creation form
     */
    public function create() {
        return $this->view('admin/plans/create', [
            'layout' => 'navbar',
            'title' => 'Add Plan'
        ]);
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
                'data' => $data,
                'layout' => 'navbar',
                'title' => 'Add Plan'
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
                'data' => $data,
                'layout' => 'navbar',
                'title' => 'Add Plan'
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
            'plan' => $plan,
            'layout' => 'navbar',
            'title' => 'Edit Plan'
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
        
        // Validate input
        $errors = $this->planModel->validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            return $this->view('admin/plans/edit', [
                'errors' => $errors,
                'data' => $data,
                'plan' => $plan,
                'layout' => 'navbar',
                'title' => 'Edit Plan'
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
                'plan' => $plan,
                'layout' => 'navbar',
                'title' => 'Edit Plan'
            ]);
        }
    }

    /**
     * Delete plan
     */
    public function delete($id) {
        try {
            // Check if plan has subscribers
            $plan = $this->planModel->getPlansWithSubscribers();
            $plan = array_filter($plan, function($p) use ($id) {
                return $p['id'] == $id && $p['subscribers'] > 0;
            });

            if (!empty($plan)) {
                return $this->json([
                    'error' => 'Cannot delete plan that has active subscribers'
                ], 400);
            }

            $this->planModel->delete($id);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        return [
            'name' => $_POST['name'] ?? null,
            'description' => $_POST['description'] ?? null,
            'bandwidth' => $_POST['bandwidth'] ?? null,
            'amount' => $_POST['amount'] ?? null,
            'status' => $_POST['status'] ?? 'active'
        ];
    }
}
