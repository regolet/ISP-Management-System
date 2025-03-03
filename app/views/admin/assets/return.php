<?php
$title = 'Return Asset - ' . htmlspecialchars($collection['asset_name']);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Return Asset</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/assets/<?= $collection['asset_id'] ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Asset Details
        </a>
    </div>
</div>

<div class="row">
    <!-- Collection Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Collection Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Asset</th>
                        <td><?= htmlspecialchars($collection['asset_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Collected By</th>
                        <td><?= htmlspecialchars($collection['user_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Collection Date</th>
                        <td><?= date('Y-m-d', strtotime($collection['collection_date'])) ?></td>
                    </tr>
                    <tr>
                        <th>Expected Return</th>
                        <td>
                            <?= date('Y-m-d', strtotime($collection['expected_return_date'])) ?>
                            <?php if (strtotime($collection['expected_return_date']) < time()): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Original Condition</th>
                        <td><?= htmlspecialchars(ucfirst($collection['condition_on_collection'])) ?></td>
                    </tr>
                    <tr>
                        <th>Purpose</th>
                        <td><?= htmlspecialchars($collection['purpose']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Collection Notes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Collection Notes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($collection['notes'])): ?>
                    <p class="text-muted">No notes recorded during collection.</p>
                <?php else: ?>
                    <p><?= nl2br(htmlspecialchars($collection['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Return Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Return Details</h5>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/assets/collections/<?= $collection['id'] ?>/return" class="row g-3">
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <!-- Return Condition -->
                    <div class="col-md-6">
                        <label for="condition" class="form-label">Return Condition *</label>
                        <select class="form-select <?= isset($errors['condition']) ? 'is-invalid' : '' ?>" 
                                id="condition" name="condition" required>
                            <option value="">Select Condition</option>
                            <option value="excellent" <?= ($data['condition'] ?? '') === 'excellent' ? 'selected' : '' ?>>Excellent</option>
                            <option value="good" <?= ($data['condition'] ?? '') === 'good' ? 'selected' : '' ?>>Good</option>
                            <option value="fair" <?= ($data['condition'] ?? '') === 'fair' ? 'selected' : '' ?>>Fair</option>
                            <option value="poor" <?= ($data['condition'] ?? '') === 'poor' ? 'selected' : '' ?>>Poor</option>
                            <option value="damaged" <?= ($data['condition'] ?? '') === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['condition']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Asset Status -->
                    <div class="col-md-6">
                        <label for="asset_status" class="form-label">Set Asset Status *</label>
                        <select class="form-select <?= isset($errors['asset_status']) ? 'is-invalid' : '' ?>" 
                                id="asset_status" name="asset_status" required>
                            <option value="available" <?= ($data['asset_status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="maintenance" <?= ($data['asset_status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Needs Maintenance</option>
                        </select>
                        <?php if (isset($errors['asset_status'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['asset_status']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Return Notes -->
                    <div class="col-12">
                        <label for="notes" class="form-label">Return Notes</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" 
                                  id="notes" name="notes" rows="4"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['notes']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">
                            Please note any damages, issues, or observations about the asset's condition.
                        </div>
                    </div>

                    <!-- Maintenance Required -->
                    <div class="col-12 maintenance-section" style="display: none;">
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">Maintenance Details</h6>
                            <div class="mb-3">
                                <label for="maintenance_description" class="form-label">Maintenance Description *</label>
                                <textarea class="form-control" id="maintenance_description" 
                                          name="maintenance_description" rows="3"><?= htmlspecialchars($data['maintenance_description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="maintenance_priority" class="form-label">Priority Level</label>
                                <select class="form-select" id="maintenance_priority" name="maintenance_priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Process Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const assetStatusSelect = document.getElementById('asset_status');
    const maintenanceSection = document.querySelector('.maintenance-section');
    const maintenanceDescription = document.getElementById('maintenance_description');
    const conditionSelect = document.getElementById('condition');

    // Show/hide maintenance section based on asset status
    function toggleMaintenanceSection() {
        if (assetStatusSelect.value === 'maintenance') {
            maintenanceSection.style.display = 'block';
            maintenanceDescription.required = true;
        } else {
            maintenanceSection.style.display = 'none';
            maintenanceDescription.required = false;
        }
    }

    // Update asset status based on condition
    function updateAssetStatus() {
        if (conditionSelect.value === 'damaged' || conditionSelect.value === 'poor') {
            assetStatusSelect.value = 'maintenance';
            toggleMaintenanceSection();
        }
    }

    assetStatusSelect.addEventListener('change', toggleMaintenanceSection);
    conditionSelect.addEventListener('change', updateAssetStatus);

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;

        if (!conditionSelect.value) {
            conditionSelect.classList.add('is-invalid');
            isValid = false;
        }

        if (!assetStatusSelect.value) {
            assetStatusSelect.classList.add('is-invalid');
            isValid = false;
        }

        if (assetStatusSelect.value === 'maintenance' && !maintenanceDescription.value.trim()) {
            maintenanceDescription.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Initial state
    toggleMaintenanceSection();
});
</script>
