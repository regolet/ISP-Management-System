<?php
$title = 'Edit Invoice - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Invoice #<?= htmlspecialchars($bill['invoiceid']) ?></h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/billing" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Billing
            </a>
            <a href="/admin/billing/<?= $bill['id'] ?>" class="btn btn-info">
                <i class="fa fa-eye"></i> View Invoice
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/billing/<?= $bill['id'] ?>" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>

                    <!-- Customer Information (Read-only) -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title">Customer Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">Customer:</p>
                                    <strong><?= htmlspecialchars($bill['customer_name']) ?></strong>
                                    <small class="d-block text-muted">
                                        <?= htmlspecialchars($bill['customer_code']) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">Current Plan:</p>
                                    <strong><?= htmlspecialchars($bill['plan_name']) ?></strong>
                                    <small class="d-block text-muted">
                                        <?= formatCurrency($bill['plan_amount']) ?>/month
                                    </small>
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
                                       step="0.01" min="0" value="<?= old('amount', $bill['amount']) ?>" 
                                       required>
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
                                   value="<?= old('due_date', date('Y-m-d', strtotime($bill['due_date']))) ?>" 
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
                                      rows="3"><?= old('description', $bill['description']) ?></textarea>
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
                            <?php foreach ($bill['items'] as $index => $item): ?>
                                <div class="row g-3 mb-3 item-row">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" 
                                               name="items[<?= $index ?>][description]" 
                                               value="<?= htmlspecialchars($item['description']) ?>" 
                                               placeholder="Item Description" required>
                                        <input type="hidden" name="items[<?= $index ?>][id]" 
                                               value="<?= $item['id'] ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control item-amount" 
                                                   name="items[<?= $index ?>][amount]" 
                                                   value="<?= $item['amount'] ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger w-100 remove-item">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
                                    <strong id="totalAmount">
                                        <?= formatCurrency($bill['amount']) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" 
                                onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
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
    const amount = document.getElementById('amount');
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItem');
    const itemTemplate = document.getElementById('itemTemplate');

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

    // Add event listeners to existing items
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            button.closest('.item-row').remove();
            updateTotal();
        });
    });

    document.querySelectorAll('.item-amount').forEach(input => {
        input.addEventListener('input', updateTotal);
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
});
</script>
