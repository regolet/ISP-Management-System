<?php
$title = 'Edit Plan - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Plan</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/plans" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Plans
            </a>
            <a href="/admin/plans/<?= $plan['id'] ?>" class="btn btn-info">
                <i class="fa fa-eye"></i> View Plan
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/plans/<?= $plan['id'] ?>" id="planForm" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>

                    <!-- Plan Status -->
                    <div class="alert alert-<?= $plan['status'] === 'active' ? 'success' : 'danger' ?> mb-4">
                        Status: <strong><?= ucfirst($plan['status']) ?></strong>
                        <div class="float-end">
                            <?php if ($plan['status'] === 'active'): ?>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deactivatePlan()">
                                    <i class="fa fa-power-off"></i> Deactivate Plan
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-success" onclick="activatePlan()">
                                    <i class="fa fa-power-off"></i> Activate Plan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <h5 class="mb-4">Basic Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Plan Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= old('name', $plan['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="bandwidth" class="form-label">Bandwidth (Mbps) *</label>
                            <input type="number" class="form-control" id="bandwidth" name="bandwidth" 
                                   value="<?= old('bandwidth', $plan['bandwidth']) ?>" required>
                            <?php if (isset($errors['bandwidth'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['bandwidth'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="amount" class="form-label">Monthly Fee *</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" value="<?= old('amount', $plan['amount']) ?>" required>
                            </div>
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['amount'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= old('description', $plan['description']) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['description'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Features</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addFeature">
                                <i class="fa fa-plus"></i> Add Feature
                            </button>
                        </div>
                        <div id="featuresContainer">
                            <?php foreach ($plan['features'] as $index => $feature): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="features[]" 
                                           value="<?= htmlspecialchars($feature) ?>" 
                                           placeholder="Enter feature">
                                    <button type="button" class="btn btn-outline-danger remove-feature">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <h5 class="mb-4">Advanced Settings</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="upload_speed" class="form-label">Upload Speed (Mbps)</label>
                            <input type="number" class="form-control" id="upload_speed" name="upload_speed" 
                                   value="<?= old('upload_speed', $plan['upload_speed']) ?>">
                            <?php if (isset($errors['upload_speed'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['upload_speed'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="download_speed" class="form-label">Download Speed (Mbps)</label>
                            <input type="number" class="form-control" id="download_speed" name="download_speed" 
                                   value="<?= old('download_speed', $plan['download_speed']) ?>">
                            <?php if (isset($errors['download_speed'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['download_speed'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="data_cap" class="form-label">Data Cap (GB)</label>
                            <input type="number" class="form-control" id="data_cap" name="data_cap" 
                                   value="<?= old('data_cap', $plan['data_cap']) ?>">
                            <small class="text-muted">Leave empty for unlimited data</small>
                            <?php if (isset($errors['data_cap'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['data_cap'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="contract_period" class="form-label">Contract Period (months)</label>
                            <input type="number" class="form-control" id="contract_period" name="contract_period" 
                                   value="<?= old('contract_period', $plan['contract_period']) ?>">
                            <small class="text-muted">Leave empty for no contract</small>
                            <?php if (isset($errors['contract_period'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['contract_period'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" 
                                onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const featuresContainer = document.getElementById('featuresContainer');
    const addFeatureBtn = document.getElementById('addFeature');

    // Add feature
    addFeatureBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        div.innerHTML = `
            <input type="text" class="form-control" name="features[]" placeholder="Enter feature">
            <button type="button" class="btn btn-outline-danger remove-feature">
                <i class="fa fa-trash"></i>
            </button>
        `;
        featuresContainer.appendChild(div);
    });

    // Remove feature
    featuresContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-feature')) {
            e.target.closest('.input-group').remove();
        }
    });

    // Plan status actions
    window.activatePlan = function() {
        updatePlanStatus('active');
    };

    window.deactivatePlan = function() {
        if (confirm('Are you sure you want to deactivate this plan? This will prevent new subscriptions.')) {
            updatePlanStatus('inactive');
        }
    };

    function updatePlanStatus(status) {
        fetch('/admin/plans/<?= $plan['id'] ?>/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update plan status');
            }
        });
    }

    // Form validation
    const form = document.getElementById('planForm');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
