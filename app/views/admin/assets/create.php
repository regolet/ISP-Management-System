<?php
$title = 'Add New Asset - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Asset</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/assets" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Assets
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/assets/create" class="row g-3" enctype="multipart/form-data">
            <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

            <!-- Basic Information -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Asset Name *</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" name="name" 
                                   value="<?= htmlspecialchars($data['name'] ?? '') ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Asset Type -->
                        <div class="mb-3">
                            <label for="asset_type" class="form-label">Asset Type *</label>
                            <select class="form-select <?= isset($errors['asset_type']) ? 'is-invalid' : '' ?>" 
                                    id="asset_type" name="asset_type">
                                <option value="">Select Type</option>
                                <option value="hardware" <?= ($data['asset_type'] ?? '') === 'hardware' ? 'selected' : '' ?>>Hardware</option>
                                <option value="software" <?= ($data['asset_type'] ?? '') === 'software' ? 'selected' : '' ?>>Software</option>
                                <option value="network" <?= ($data['asset_type'] ?? '') === 'network' ? 'selected' : '' ?>>Network Equipment</option>
                                <option value="furniture" <?= ($data['asset_type'] ?? '') === 'furniture' ? 'selected' : '' ?>>Furniture</option>
                            </select>
                            <?php if (isset($errors['asset_type'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['asset_type']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Serial Number -->
                        <div class="mb-3">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control <?= isset($errors['serial_number']) ? 'is-invalid' : '' ?>" 
                                   id="serial_number" name="serial_number" 
                                   value="<?= htmlspecialchars($data['serial_number'] ?? '') ?>">
                            <?php if (isset($errors['serial_number'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['serial_number']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Information -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Purchase Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Purchase Date -->
                        <div class="mb-3">
                            <label for="purchase_date" class="form-label">Purchase Date *</label>
                            <input type="date" class="form-control <?= isset($errors['purchase_date']) ? 'is-invalid' : '' ?>" 
                                   id="purchase_date" name="purchase_date" 
                                   value="<?= htmlspecialchars($data['purchase_date'] ?? '') ?>">
                            <?php if (isset($errors['purchase_date'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['purchase_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Purchase Price -->
                        <div class="mb-3">
                            <label for="purchase_price" class="form-label">Purchase Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control <?= isset($errors['purchase_price']) ? 'is-invalid' : '' ?>" 
                                       id="purchase_price" name="purchase_price" 
                                       value="<?= htmlspecialchars($data['purchase_price'] ?? '') ?>">
                                <?php if (isset($errors['purchase_price'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['purchase_price']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Warranty Expiry -->
                        <div class="mb-3">
                            <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                            <input type="date" class="form-control <?= isset($errors['warranty_expiry']) ? 'is-invalid' : '' ?>" 
                                   id="warranty_expiry" name="warranty_expiry" 
                                   value="<?= htmlspecialchars($data['warranty_expiry'] ?? '') ?>">
                            <?php if (isset($errors['warranty_expiry'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['warranty_expiry']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Maintenance Schedule -->
                        <div class="mb-3">
                            <label for="maintenance_schedule" class="form-label">Maintenance Schedule</label>
                            <select class="form-select" id="maintenance_schedule" name="maintenance_schedule">
                                <option value="">None</option>
                                <option value="monthly" <?= ($data['maintenance_schedule'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                <option value="quarterly" <?= ($data['maintenance_schedule'] ?? '') === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                <option value="biannual" <?= ($data['maintenance_schedule'] ?? '') === 'biannual' ? 'selected' : '' ?>>Bi-Annual</option>
                                <option value="annual" <?= ($data['maintenance_schedule'] ?? '') === 'annual' ? 'selected' : '' ?>>Annual</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Location Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Location -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" 
                                           id="location" name="location" 
                                           value="<?= htmlspecialchars($data['location'] ?? '') ?>">
                                    <?php if (isset($errors['location'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['location']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="available" <?= ($data['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="maintenance" <?= ($data['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="col-12 text-end">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary">Create Asset</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate warranty expiry based on purchase date for certain asset types
    const assetTypeSelect = document.getElementById('asset_type');
    const purchaseDateInput = document.getElementById('purchase_date');
    const warrantyExpiryInput = document.getElementById('warranty_expiry');

    function updateWarrantyExpiry() {
        if (!purchaseDateInput.value) return;

        const purchaseDate = new Date(purchaseDateInput.value);
        let warrantyYears = 0;

        switch (assetTypeSelect.value) {
            case 'hardware':
                warrantyYears = 3;
                break;
            case 'network':
                warrantyYears = 2;
                break;
            case 'furniture':
                warrantyYears = 1;
                break;
        }

        if (warrantyYears > 0) {
            const expiryDate = new Date(purchaseDate);
            expiryDate.setFullYear(expiryDate.getFullYear() + warrantyYears);
            warrantyExpiryInput.value = expiryDate.toISOString().split('T')[0];
        }
    }

    assetTypeSelect.addEventListener('change', updateWarrantyExpiry);
    purchaseDateInput.addEventListener('change', updateWarrantyExpiry);
});
</script>
