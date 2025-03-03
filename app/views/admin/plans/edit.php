<?php
$title = 'Edit Plan - Admin Panel';
$data = $data ?? $plan;
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-package'></i> Edit Plan</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/plans" class="btn btn-outline-secondary">
            <i class='bx bx-arrow-back'></i> Back to Plans
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= $errors['general'] ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/plans/update/<?= $plan['id'] ?>">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">

                    <div class="mb-3">
                        <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= hasError('name', $errors) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= old('name', $data['name']) ?>" required>
                        <?php if (hasError('name', $errors)): ?>
                            <div class="invalid-feedback"><?= getError('name', $errors) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= old('description', $data['description']) ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bandwidth" class="form-label">Bandwidth (Mbps) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control <?= hasError('bandwidth', $errors) ? 'is-invalid' : '' ?>" 
                                   id="bandwidth" name="bandwidth" value="<?= old('bandwidth', $data['bandwidth']) ?>" min="1" required>
                            <?php if (hasError('bandwidth', $errors)): ?>
                                <div class="invalid-feedback"><?= getError('bandwidth', $errors) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control <?= hasError('amount', $errors) ? 'is-invalid' : '' ?>" 
                                       id="amount" name="amount" value="<?= old('amount', $data['amount']) ?>" min="0" step="0.01" required>
                                <?php if (hasError('amount', $errors)): ?>
                                    <div class="invalid-feedback"><?= getError('amount', $errors) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= old('status', $data['status']) === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status', $data['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save'></i> Update Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class='bx bx-info-circle'></i> Help</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Update the plan details:
                </p>
                <ul class="text-muted small">
                    <li><strong>Plan Name:</strong> A unique name for the plan (e.g., "Basic", "Premium")</li>
                    <li><strong>Description:</strong> Optional details about the plan features</li>
                    <li><strong>Bandwidth:</strong> Internet speed in Mbps (e.g., 10, 50, 100)</li>
                    <li><strong>Amount:</strong> Monthly subscription fee</li>
                    <li><strong>Status:</strong> Set to inactive to hide from new subscriptions</li>
                </ul>
                <?php if ($plan['subscribers'] > 0): ?>
                    <div class="alert alert-warning mb-0">
                        <i class='bx bx-info-circle'></i> This plan has <?= number_format($plan['subscribers']) ?> active subscribers.
                        Changes will affect their billing on the next cycle.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
