<?php
$title = 'Create Invoice - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Create Invoice</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/billing" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Billing
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/billing" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- Customer Selection -->
                    <div class="mb-4">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" 
                                        data-plan="<?= htmlspecialchars(json_encode([
                                            'name' => $customer['plan_name'],
                                            'amount' => $customer['plan_amount']
                                        ])) ?>"
                                        <?= old('customer_id') == $customer['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['name']) ?> 
                                    (<?= htmlspecialchars($customer['customer_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['customer_id'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= $errors['customer_id'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Plan Details -->
                    <div class="card bg-light mb-4" id="planDetails" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title">Current Plan</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">Plan Name:</p>
                                    <strong id="planName"></strong>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">Monthly Fee:</p>
                                    <strong id="planAmount"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" value="<?= old('amount') ?>" required>
                            </div>
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['amount'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date *</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?= old('due_date', date('Y-m-d', strtotime('+30 days'))) ?>" 
                                   required>
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['due_date'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= old('description') ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['description'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Items -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Additional Items</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                                <i class="fa fa-plus"></i> Add Item
                            </button>
                        </div>
                        <div id="itemsContainer">
                            <!-- Items will be added here -->
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="card-title">Summary</h6>
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong id="totalAmount">$0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" 
                                onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="itemTemplate">
    <div class="row g-3 mb-3 item-row">
        <div class="col-md-6">
            <input type="text" class="form-control" name="items[][description]" 
                   placeholder="Item Description" required>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control item-amount" name="items[][amount]" 
                       step="0.01" min="0" required>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger w-100 remove-item">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    const planDetails = document.getElementById('planDetails');
    const planName = document.getElementById('planName');
    const planAmount = document.getElementById('planAmount');
    const amount = document.getElementById('amount');
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItem');
    const itemTemplate = document.getElementById('itemTemplate');

    // Handle customer selection
    customerSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            const plan = JSON.parse(option.dataset.plan);
            planName.textContent = plan.name;
            planAmount.textContent = formatCurrency(plan.amount);
            amount.value = plan.amount;
            planDetails.style.display = 'block';
        } else {
            planDetails.style.display = 'none';
            amount.value = '';
        }
        updateTotal();
    });

    // Add item
    addItemBtn.addEventListener('click', function() {
        const itemRow = document.importNode(itemTemplate.content, true);
        itemsContainer.appendChild(itemRow);
        
        // Add event listeners to new row
        const row = itemsContainer.lastElementChild;
        row.querySelector('.remove-item').addEventListener('click', function() {
            row.remove();
            updateTotal();
        });
        row.querySelector('.item-amount').addEventListener('input', updateTotal);
    });

    // Update total
    function updateTotal() {
        let total = parseFloat(amount.value) || 0;
        document.querySelectorAll('.item-amount').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('totalAmount').textContent = formatCurrency(total);
    }

    // Listen for amount changes
    amount.addEventListener('input', updateTotal);

    // Format currency
    function formatCurrency(value) {
        return '$' + parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Initialize
    if (customerSelect.value) {
        customerSelect.dispatchEvent(new Event('change'));
    }
});
</script>
