<?php
$title = 'Edit Payment - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Payment</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/payments" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Payments
            </a>
            <a href="/admin/payments/<?= $payment['id'] ?>" class="btn btn-info">
                <i class="fa fa-eye"></i> View Payment
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/payments/<?= $payment['id'] ?>" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>

                    <!-- Invoice Details (Read-only) -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title">Invoice Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">Customer:</p>
                                    <strong><?= htmlspecialchars($payment['customer_name']) ?></strong>
                                    <small class="d-block text-muted">
                                        <?= htmlspecialchars($payment['customer_code']) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">Invoice:</p>
                                    <strong>#<?= htmlspecialchars($payment['invoiceid']) ?></strong>
                                    <small class="d-block text-muted">
                                        Balance: <?= formatCurrency($payment['balance_due'] + $payment['amount']) ?>
                                    </small>
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
                                       value="<?= old('amount', $payment['amount']) ?>" 
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
                                   value="<?= old('payment_date', date('Y-m-d\TH:i', strtotime($payment['payment_date']))) ?>" 
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
                                            <?= (old('payment_method', $payment['payment_method']) === $key) ? 'selected' : '' ?>>
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
                                   value="<?= old('reference_no', $payment['reference_no']) ?>">
                            <?php if (isset($errors['reference_no'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['reference_no'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" 
                                      rows="3"><?= old('notes', $payment['notes']) ?></textarea>
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
                        <button type="submit" class="btn btn-primary">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const amount = document.getElementById('amount');
    const maxAmount = <?= $payment['balance_due'] + $payment['amount'] ?>;

    // Validate amount doesn't exceed invoice balance
    amount.addEventListener('input', function() {
        const value = parseFloat(this.value) || 0;
        if (value > maxAmount) {
            this.value = maxAmount;
        }
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
