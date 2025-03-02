<?php
$title = 'Collect Asset - ' . htmlspecialchars($asset['name']);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Collect Asset</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/assets/<?= $asset['id'] ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Asset Details
        </a>
    </div>
</div>

<div class="row">
    <!-- Asset Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Asset Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Name</th>
                        <td><?= htmlspecialchars($asset['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><?= htmlspecialchars(ucfirst($asset['asset_type'])) ?></td>
                    </tr>
                    <tr>
                        <th>Serial Number</th>
                        <td><?= htmlspecialchars($asset['serial_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?= getStatusBadgeClass($asset['status']) ?>">
                                <?= ucfirst(htmlspecialchars($asset['status'])) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td><?= htmlspecialchars($asset['location']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Collection Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Collection Details</h5>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/assets/<?= $asset['id'] ?>/collect" class="row g-3">
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <!-- User Selection -->
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">Collected By *</label>
                        <select class="form-select <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>" 
                                id="user_id" name="user_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($users ?? [] as $user): ?>
                                <option value="<?= $user['id'] ?>" 
                                        <?= ($data['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['department']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['user_id'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['user_id']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Expected Return Date -->
                    <div class="col-md-6">
                        <label for="expected_return_date" class="form-label">Expected Return Date *</label>
                        <input type="date" class="form-control <?= isset($errors['expected_return_date']) ? 'is-invalid' : '' ?>" 
                               id="expected_return_date" name="expected_return_date" 
                               value="<?= htmlspecialchars($data['expected_return_date'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>" required>
                        <?php if (isset($errors['expected_return_date'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['expected_return_date']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Purpose -->
                    <div class="col-12">
                        <label for="purpose" class="form-label">Purpose *</label>
                        <textarea class="form-control <?= isset($errors['purpose']) ? 'is-invalid' : '' ?>" 
                                  id="purpose" name="purpose" rows="3" required><?= htmlspecialchars($data['purpose'] ?? '') ?></textarea>
                        <?php if (isset($errors['purpose'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['purpose']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Asset Condition -->
                    <div class="col-md-6">
                        <label for="condition" class="form-label">Current Condition *</label>
                        <select class="form-select <?= isset($errors['condition']) ? 'is-invalid' : '' ?>" 
                                id="condition" name="condition" required>
                            <option value="">Select Condition</option>
                            <option value="excellent" <?= ($data['condition'] ?? '') === 'excellent' ? 'selected' : '' ?>>Excellent</option>
                            <option value="good" <?= ($data['condition'] ?? '') === 'good' ? 'selected' : '' ?>>Good</option>
                            <option value="fair" <?= ($data['condition'] ?? '') === 'fair' ? 'selected' : '' ?>>Fair</option>
                            <option value="poor" <?= ($data['condition'] ?? '') === 'poor' ? 'selected' : '' ?>>Poor</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['condition']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Notes -->
                    <div class="col-12">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" 
                                  rows="3"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>" 
                                   type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I acknowledge responsibility for this asset and agree to return it in the same condition
                                by the specified return date.
                            </label>
                            <?php if (isset($errors['terms'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['terms']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Collect Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusBadgeClass($status) {
    return match ($status) {
        'available' => 'success',
        'collected' => 'warning',
        'maintenance' => 'danger',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for expected return date
    const expectedReturnDate = document.getElementById('expected_return_date');
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    expectedReturnDate.min = tomorrow.toISOString().split('T')[0];

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check if user is selected
        const userId = document.getElementById('user_id');
        if (!userId.value) {
            userId.classList.add('is-invalid');
            isValid = false;
        } else {
            userId.classList.remove('is-invalid');
        }

        // Check if purpose is filled
        const purpose = document.getElementById('purpose');
        if (!purpose.value.trim()) {
            purpose.classList.add('is-invalid');
            isValid = false;
        } else {
            purpose.classList.remove('is-invalid');
        }

        // Check if condition is selected
        const condition = document.getElementById('condition');
        if (!condition.value) {
            condition.classList.add('is-invalid');
            isValid = false;
        } else {
            condition.classList.remove('is-invalid');
        }

        // Check if terms are accepted
        const terms = document.getElementById('terms');
        if (!terms.checked) {
            terms.classList.add('is-invalid');
            isValid = false;
        } else {
            terms.classList.remove('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
