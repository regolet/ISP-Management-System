<?php
$title = 'Request Payment - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Request Payment</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/staff/payments" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/staff/payments/add" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <!-- Payment Type -->
                    <div class="mb-3">
                        <label for="payment_type" class="form-label">Payment Type *</label>
                        <select class="form-select <?= isset($errors['payment_type']) ? 'is-invalid' : '' ?>" 
                                id="payment_type" name="payment_type" required>
                            <option value="">Select Payment Type</option>
                            <?php foreach ($paymentTypes as $type): ?>
                                <option value="<?= $type['id'] ?>" 
                                        <?= ($type['id'] == ($data['payment_type'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['payment_type'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['payment_type']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" 
                                   id="amount" name="amount" value="<?= htmlspecialchars($data['amount'] ?? '') ?>" 
                                   required min="0.01">
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['amount']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Preferred Payment Method *</label>
                        <select class="form-select <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" 
                                id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <?php foreach ($paymentMethods as $method): ?>
                                <option value="<?= $method['id'] ?>" 
                                        <?= ($method['id'] == ($data['payment_method'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($method['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['payment_method'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['payment_method']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Reference Number -->
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control <?= isset($errors['reference_number']) ? 'is-invalid' : '' ?>" 
                               id="reference_number" name="reference_number" 
                               value="<?= htmlspecialchars($data['reference_number'] ?? '') ?>">
                        <?php if (isset($errors['reference_number'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['reference_number']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">Optional: Enter any reference number related to this payment</div>
                    </div>

                    <!-- Payment Date -->
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Requested Payment Date *</label>
                        <input type="date" class="form-control <?= isset($errors['payment_date']) ? 'is-invalid' : '' ?>" 
                               id="payment_date" name="payment_date" 
                               value="<?= htmlspecialchars($data['payment_date'] ?? date('Y-m-d')) ?>" 
                               min="<?= date('Y-m-d') ?>" required>
                        <?php if (isset($errors['payment_date'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['payment_date']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                  id="description" name="description" rows="3" required><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Supporting Documents -->
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Supporting Documents</label>
                        <input type="file" class="form-control <?= isset($errors['attachments']) ? 'is-invalid' : '' ?>" 
                               id="attachments" name="attachments[]" multiple 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <?php if (isset($errors['attachments'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['attachments']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">
                            Optional: Upload any supporting documents (Max 5MB each). 
                            Accepted formats: PDF, DOC, DOCX, JPG, PNG
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Additional Notes</label>
                        <textarea class="form-control <?= isset($errors['remarks']) ? 'is-invalid' : '' ?>" 
                                  id="remarks" name="remarks" rows="2"><?= htmlspecialchars($data['remarks'] ?? '') ?></textarea>
                        <?php if (isset($errors['remarks'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['remarks']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        Payment requests are subject to approval. You will be notified once your request is processed.
                    </div>

                    <!-- Form Actions -->
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // File size validation
    const attachments = document.getElementById('attachments');
    attachments.addEventListener('change', function() {
        const maxSize = 5 * 1024 * 1024; // 5MB
        let files = this.files;
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert(`File "${files[i].name}" exceeds 5MB limit`);
                this.value = '';
                return;
            }
        }
    });

    // Payment type dependent fields
    const paymentType = document.getElementById('payment_type');
    paymentType.addEventListener('change', function() {
        const selectedType = this.options[this.selectedIndex];
        const referenceField = document.getElementById('reference_number');
        
        // Example: Make reference number required for certain payment types
        if (['1', '2', '3'].includes(this.value)) { // Replace with actual payment type IDs
            referenceField.required = true;
            referenceField.closest('.mb-3').querySelector('.form-text').textContent = 
                'Required: Enter the reference number for this payment type';
        } else {
            referenceField.required = false;
            referenceField.closest('.mb-3').querySelector('.form-text').textContent = 
                'Optional: Enter any reference number related to this payment';
        }
    });

    // Payment method dependent fields
    const paymentMethod = document.getElementById('payment_method');
    paymentMethod.addEventListener('change', function() {
        // Add any payment method specific logic here
    });
});
</script>
