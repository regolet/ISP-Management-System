<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Subscription;
use App\Models\Admin\Customer;
use App\Models\Admin\Plan;

class SubscriptionController extends Controller {
    private $subscriptionModel;
    private $customerModel;
    private $planModel;

    public function __construct() {
        parent::__construct();
        $this->subscriptionModel = new Subscription();
        $this->customerModel = new Customer();
        $this->planModel = new Plan();
    }

    /**
     * Display subscriptions list
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'plan_id' => $_GET['plan_id'] ?? null
        ];

        $result = $this->subscriptionModel->getSubscriptions($filters, $page);
        $plans = $this->planModel->getActivePlans();

        return $this->view('admin/subscriptions/index', [
            'subscriptions' => $result['subscriptions'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'plans' => $plans,
            'layout' => 'navbar',
            'title' => 'Subscription Management'
        ]);
    }

    /**
     * Show subscription creation form
     */
    public function create() {
        $customers = $this->customerModel->getActiveCustomers();
        $plans = $this->planModel->getActivePlans();

        return $this->view('admin/subscriptions/create', [
            'customers' => $customers,
            'plans' => $plans,
            'layout' => 'navbar',
            'title' => 'Create Subscription'
        ]);
    }

    /**
     * Store new subscription
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->subscriptionModel->validate($data);
        if (!empty($errors)) {
            $customers = $this->customerModel->getActiveCustomers();
            $plans = $this->planModel->getActivePlans();
            return $this->view('admin/subscriptions/create', [
                'errors' => $errors,
                'data' => $data,
                'customers' => $customers,
                'plans' => $plans,
                'layout' => 'navbar',
                'title' => 'Create Subscription'
            ]);
        }

        try {
            $subscriptionId = $this->subscriptionModel->createSubscription($data);
            $this->setFlash('success', 'Subscription created successfully');
            return $this->redirect("/admin/subscriptions/view/{$subscriptionId}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to create subscription: ' . $e->getMessage());
            $customers = $this->customerModel->getActiveCustomers();
            $plans = $this->planModel->getActivePlans();
            return $this->view('admin/subscriptions/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'customers' => $customers,
                'plans' => $plans,
                'layout' => 'navbar',
                'title' => 'Create Subscription'
            ]);
        }
    }

    /**
     * Show subscription details
     */
    public function show($id) {
        $subscription = $this->subscriptionModel->getSubscriptionDetails($id);
        if (!$subscription) {
            $this->setFlash('error', 'Subscription not found');
            return $this->redirect('/admin/subscriptions');
        }

        return $this->view('admin/subscriptions/show', [
            'subscription' => $subscription
        ]);
    }

    /**
     * Show subscription edit form
     */
    public function edit($id) {
        $subscription = $this->subscriptionModel->getSubscriptionDetails($id);
        if (!$subscription) {
            $this->setFlash('error', 'Subscription not found');
            return $this->redirect('/admin/subscriptions');
        }

        $customers = $this->customerModel->getActiveCustomers();
        $plans = $this->planModel->getActivePlans();

        return $this->view('admin/subscriptions/edit', [
            'subscription' => $subscription,
            'customers' => $customers,
            'plans' => $plans,
            'layout' => 'navbar',
            'title' => 'Edit Subscription'
        ]);
    }

    /**
     * Update subscription
     */
    public function update($id) {
        $subscription = $this->subscriptionModel->find($id);
        if (!$subscription) {
            $this->setFlash('error', 'Subscription not found');
            return $this->redirect('/admin/subscriptions');
        }

        $data = $this->getRequestData();
        $data['id'] = $id;

        // Validate input
        $errors = $this->subscriptionModel->validate($data);
        if (!empty($errors)) {
            $customers = $this->customerModel->getActiveCustomers();
            $plans = $this->planModel->getActivePlans();
            return $this->view('admin/subscriptions/edit', [
                'errors' => $errors,
                'data' => $data,
                'subscription' => $subscription,
                'customers' => $customers,
                'plans' => $plans,
                'layout' => 'navbar',
                'title' => 'Edit Subscription'
            ]);
        }

        try {
            $this->subscriptionModel->update($id, $data);
            $this->setFlash('success', 'Subscription updated successfully');
            return $this->redirect("/admin/subscriptions/view/{$id}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to update subscription: ' . $e->getMessage());
            $customers = $this->customerModel->getActiveCustomers();
            $plans = $this->planModel->getActivePlans();
            return $this->view('admin/subscriptions/edit', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'subscription' => $subscription,
                'customers' => $customers,
                'plans' => $plans,
                'layout' => 'navbar',
                'title' => 'Edit Subscription'
            ]);
        }
    }

    /**
     * Update subscription status
     */
    public function updateStatus($id) {
        $subscription = $this->subscriptionModel->find($id);
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found'], 404);
        }

        $status = $_POST['status'] ?? null;
        if (!$status) {
            return $this->json(['error' => 'Status is required'], 400);
        }

        try {
            $this->subscriptionModel->updateStatus($id, $status);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete subscription
     */
    public function delete($id) {
        $subscription = $this->subscriptionModel->find($id);
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found'], 404);
        }

        if ($subscription['status'] === 'active') {
            return $this->json(['error' => 'Cannot delete active subscription'], 400);
        }

        try {
            $this->subscriptionModel->delete($id);
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
            'customer_id' => $_POST['customer_id'] ?? null,
            'plan_id' => $_POST['plan_id'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'installation_address' => $_POST['installation_address'] ?? null,
            'router_model' => $_POST['router_model'] ?? null,
            'router_serial' => $_POST['router_serial'] ?? null,
            'ont_model' => $_POST['ont_model'] ?? null,
            'ont_serial' => $_POST['ont_serial'] ?? null,
            'ip_type' => $_POST['ip_type'] ?? null,
            'ip_address' => $_POST['ip_address'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];
    }
}
