<?php
$title = 'Record Payment - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Record Payment</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/payments" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/payments" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- Invoice Selection -->
                    <?php if (!isset($bill)): ?>
                        <div class="mb-4">
                            <label for="billing_id" class="form-label">Invoice *</label>
                            <select class="form-select" id="billing_id" name="billing_id" required>
                                <option value="">Select Invoice</option>
                                <?php foreach ($unpaidBills as $unpaidBill): ?>
                                    <option value="<?= $unpaidBill['id'] ?>" 
                                            data-amount="<?= $unpaidBill['balance_due'] ?>"
                                            data-customer="<?= htmlspecialchars(json_encode([
                                                'name' => $unpaidBill['customer_name'],
                                                'code' => $unpaidBill['customer_code']
                                            ])) ?>"
                                            <?= old('billing_id') == $unpaidBill['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($unpaidBill['invoiceid']) ?> - 
                                        <?= htmlspecialchars($unpaidBill['customer_name']) ?> 
                                        (Balance: <?= formatCurrency($unpaidBill['balance_due']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['billing_id'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['billing_id'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="billing_id" value="<?= $bill['id'] ?>">
                    <?php endif; ?>

                    <!-- Invoice Details -->
                    <div class="card bg-light mb-4" id="invoiceDetails" 
                         style="<?= isset($bill) ? '' : 'display: none;' ?>">
                        <div class="card-body">
                            <h6 class="card-title">Invoice Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">Customer:</p>
                                    <strong id="customerName">
                                        <?= isset($bill) ? htmlspecialchars($bill['customer_name']) : '' ?>
                                    </strong>
                                    <small class="d-block text-muted" id="customerCode">
                                        <?= isset($bill) ? htmlspecialchars($bill['customer_code']) : '' ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">Balance Due:</p>
                                    <strong id="balanceDue">
                                        <?= isset($bill) ? formatCurrency($bill['balance_due']) : '' ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" 
                                       value="<?= old('amount', isset($bill) ? $bill['balance_due'] : '') ?>" 
                                       required>
                            </div>
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['amount'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_date" class="form-label">Payment Date *</label>
                            <input type="datetime-local" class="form-control" id="payment_date" 
                                   name="payment_date" 
                                   value="<?= old('payment_date', date('Y-m-d\TH:i')) ?>" 
                                   required>
                            <?php if (isset($errors['payment_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['payment_date'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Method</option>
                                <?php foreach ($paymentMethods as $key => $name): ?>
                                    <option value="<?= $key ?>" 
                                            <?= old('payment_method') === $key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['payment_method'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['payment_method'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="reference_no" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                   value="<?= old('reference_no') ?>">
                            <?php if (isset($errors['reference_no'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['reference_no'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" 
                                      rows="3"><?= old('notes') ?></textarea>
                            <?php if (isset($errors['notes'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['notes'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" 
                                onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const billingSelect = document.getElementById('billing_id');
    const invoiceDetails = document.getElementById('invoiceDetails');
    const customerName = document.getElementById('customerName');
    const customerCode = document.getElementById('customerCode');
    const balanceDue = document.getElementById('balanceDue');
    const amount = document.getElementById('amount');

    // Handle invoice selection
    if (billingSelect) {
        billingSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                const customer = JSON.parse(option.dataset.customer);
                customerName.textContent = customer.name;
                customerCode.textContent = customer.code;
                balanceDue.textContent = formatCurrency(option.dataset.amount);
                amount.value = option.dataset.amount;
                invoiceDetails.style.display = 'block';
            } else {
                invoiceDetails.style.display = 'none';
                amount.value = '';
            }
        });

        // Initialize if value is selected
        if (billingSelect.value) {
            billingSelect.dispatchEvent(new Event('change'));
        }
    }

    // Format currency
    function formatCurrency(value) {
        return '$' + parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
});
</script>
