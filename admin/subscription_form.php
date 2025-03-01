<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Subscription ID is required";
    header("Location: subscriptions.php");
    exit;
}

$page_title = 'Edit Subscription';
$_SESSION['active_menu'] = 'subscriptions';

// Initialize subscription data with all required fields
$subscription = [
    'id' => '',
    'customer_id' => '',
    'plan_id' => '',
    'start_date' => date('Y-m-d'),
    'end_date' => '',
    'billing_cycle' => 'monthly',
    'status' => 'active',
    'notes' => '',  // Initialize notes as empty string
    'customer_name' => '',  // Added for view mode
    'plan_name' => '',     // Added for view mode
    'amount' => '0.00'     // Added for amount display
];

// Get subscription data
$id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("
    SELECT s.*, 
           c.name as customer_name, 
           p.name as plan_name, 
           p.amount as current_plan_amount,
           COALESCE(s.notes, '') as notes,
           COALESCE(s.auto_renew, 1) as auto_renew
    FROM subscriptions s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN plans p ON s.plan_id = p.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

if (!$subscription) {
    $_SESSION['error'] = "Subscription not found";
    header("Location: subscriptions.php");
    exit;
}

// Get available plans for changing plan
$plans_query = "SELECT * FROM plans WHERE status = 'active' ORDER BY name";
$plans = $conn->query($plans_query);

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Edit Subscription</h1>
            </div>
            <div class="col text-end">
                <a href="subscriptions.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back
                </a>
            </div>
        </div>

        <?php include 'alerts.php'; ?>

        <div class="card">
            <div class="card-body">
                <form action="subscription_save.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" value="<?php echo $subscription['id']; ?>">
                    
                    <!-- Customer Info (Read-only) -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($subscription['customer_name']); ?>" readonly>
                            <input type="hidden" name="customer_id" value="<?php echo $subscription['customer_id']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Plan</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($subscription['plan_name']); ?>" readonly>
                        </div>
                    </div>

                    <!-- Editable Fields -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Change Plan To</label>
                            <select class="form-select" name="plan_id" id="planSelect">
                                <option value="">Keep Current Plan</option>
                                <?php while ($plan = $plans->fetch_assoc()): ?>
                                    <?php if ($plan['id'] != $subscription['plan_id']): ?>
                                    <option value="<?php echo $plan['id']; ?>" 
                                            data-amount="<?php echo $plan['amount']; ?>">
                                        <?php echo htmlspecialchars($plan['name']); ?>
                                        (₱<?php echo number_format($plan['amount'], 2); ?>)
                                    </option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Billing Cycle</label>
                            <select class="form-select" name="billing_cycle" id="billingCycle" required>
                                <option value="monthly" <?php echo $subscription['billing_cycle'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="quarterly" <?php echo $subscription['billing_cycle'] == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                <option value="annually" <?php echo $subscription['billing_cycle'] == 'annually' ? 'selected' : ''; ?>>Annually</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="amount" id="amount"
                                       step="0.01" required 
                                       value="<?php echo $subscription['current_plan_amount']; ?>" readonly>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active" <?php echo $subscription['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $subscription['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="cancelled" <?php echo $subscription['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="autoRenew" 
                                       name="auto_renew" value="1" 
                                       <?php echo $subscription['auto_renew'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="autoRenew">Auto-renew subscription</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($subscription['notes']); ?></textarea>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <a href="subscriptions.php" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save'></i> Update Subscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const planSelect = document.getElementById('planSelect');
        const billingCycle = document.getElementById('billingCycle');
        const status = document.querySelector('select[name="status"]');
        
        let errors = [];
        
        // Validate required fields
        if (!billingCycle.value) {
            errors.push('Billing cycle is required');
            billingCycle.classList.add('is-invalid');
        }
        
        if (!status.value) {
            errors.push('Status is required');
            status.classList.add('is-invalid');
        }

        // If changing plan, ensure new plan is selected
        if (planSelect.value === '') {
            planSelect.value = document.querySelector('input[name="plan_id"]').value;
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert('Please fill in all required fields:\n- ' + errors.join('\n- '));
        }
    });

    // Remove invalid class on input
    document.querySelectorAll('select, input').forEach(element => {
        element.addEventListener('change', function() {
            this.classList.remove('is-invalid');
        });
    });
});

// ...rest of existing script...
</script>

<style>
.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>

<?php include 'footer.php'; ?>
