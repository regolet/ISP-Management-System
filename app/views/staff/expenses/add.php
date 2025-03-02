<?php
$title = ($expense ?? null) ? 'Edit Expense - ISP Management System' : 'Add Expense - ISP Management System';
$isEdit = isset($expense);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><?= $isEdit ? 'Edit Expense' : 'Add New Expense' ?></h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/staff/expenses" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Expenses
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

                <form method="POST" action="<?= $isEdit ? "/staff/expenses/edit/{$expense['id']}" : '/staff/expenses/add' ?>" 
                      enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <!-- Category -->
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Expense Category *</label>
                        <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" 
                                id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= ($category['id'] == ($expense['category_id'] ?? $data['category_id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['category_id']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" 
                                   id="amount" name="amount" required min="0.01"
                                   value="<?= htmlspecialchars($expense['amount'] ?? $data['amount'] ?? '') ?>">
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['amount']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label for="date" class="form-label">Expense Date *</label>
                        <input type="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>" 
                               id="date" name="date" required max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($expense['date'] ?? $data['date'] ?? date('Y-m-d')) ?>">
                        <?php if (isset($errors['date'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['date']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Reference Number -->
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control <?= isset($errors['reference_number']) ? 'is-invalid' : '' ?>" 
                               id="reference_number" name="reference_number" 
                               value="<?= htmlspecialchars($expense['reference_number'] ?? $data['reference_number'] ?? '') ?>">
                        <?php if (isset($errors['reference_number'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['reference_number']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">Optional: Invoice number, receipt number, etc.</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                  id="description" name="description" rows="3" required><?= htmlspecialchars($expense['description'] ?? $data['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Attachment -->
                    <div class="mb-3">
                        <label for="attachment" class="form-label">Receipt/Invoice</label>
                        <?php if ($isEdit && !empty($expense['attachment_path'])): ?>
                            <div class="mb-2">
                                <a href="<?= $expense['attachment_path'] ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fa fa-file"></i> View Current Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control <?= isset($errors['attachment']) ? 'is-invalid' : '' ?>" 
                               id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (isset($errors['attachment'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['attachment']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">
                            Optional: Upload receipt or invoice (Max 5MB). Accepted formats: PDF, JPG, PNG
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Additional Notes</label>
                        <textarea class="form-control <?= isset($errors['remarks']) ? 'is-invalid' : '' ?>" 
                                  id="remarks" name="remarks" rows="2"><?= htmlspecialchars($expense['remarks'] ?? $data['remarks'] ?? '') ?></textarea>
                        <?php if (isset($errors['remarks'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['remarks']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        Expense claims are subject to approval. You will be notified once your claim is processed.
                    </div>

                    <!-- Form Actions -->
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Update Expense' : 'Submit Expense' ?>
                        </button>
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
    const attachment = document.getElementById('attachment');
    attachment.addEventListener('change', function() {
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (this.files[0] && this.files[0].size > maxSize) {
            alert('File size exceeds 5MB limit');
            this.value = '';
        }
    });

    // Category dependent fields
    const category = document.getElementById('category_id');
    category.addEventListener('change', function() {
        const selectedCategory = this.options[this.selectedIndex];
        const referenceField = document.getElementById('reference_number');
        
        // Example: Make reference number required for certain categories
        if (['1', '2', '3'].includes(this.value)) { // Replace with actual category IDs
            referenceField.required = true;
            referenceField.closest('.mb-3').querySelector('.form-text').textContent = 
                'Required: Enter the reference number for this expense type';
        } else {
            referenceField.required = false;
            referenceField.closest('.mb-3').querySelector('.form-text').textContent = 
                'Optional: Invoice number, receipt number, etc.';
        }
    });
});
</script>
