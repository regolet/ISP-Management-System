<?php
$title = 'Add New OLT - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New OLT</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/network/olts" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to OLTs
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/network/olts/create" class="row g-3">
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
                                    <label for="name" class="form-label">OLT Name *</label>
                                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                           id="name" name="name" 
                                           value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Model -->
                                <div class="mb-3">
                                    <label for="model" class="form-label">Model *</label>
                                    <input type="text" class="form-control <?= isset($errors['model']) ? 'is-invalid' : '' ?>" 
                                           id="model" name="model" 
                                           value="<?= htmlspecialchars($data['model'] ?? '') ?>" required>
                                    <?php if (isset($errors['model'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['model']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Serial Number -->
                                <div class="mb-3">
                                    <label for="serial_number" class="form-label">Serial Number *</label>
                                    <input type="text" class="form-control <?= isset($errors['serial_number']) ? 'is-invalid' : '' ?>" 
                                           id="serial_number" name="serial_number" 
                                           value="<?= htmlspecialchars($data['serial_number'] ?? '') ?>" required>
                                    <?php if (isset($errors['serial_number'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['serial_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Vendor -->
                                <div class="mb-3">
                                    <label for="vendor" class="form-label">Vendor *</label>
                                    <select class="form-select <?= isset($errors['vendor']) ? 'is-invalid' : '' ?>" 
                                            id="vendor" name="vendor" required>
                                        <option value="">Select Vendor</option>
                                        <option value="huawei" <?= ($data['vendor'] ?? '') === 'huawei' ? 'selected' : '' ?>>Huawei</option>
                                        <option value="zte" <?= ($data['vendor'] ?? '') === 'zte' ? 'selected' : '' ?>>ZTE</option>
                                        <option value="nokia" <?= ($data['vendor'] ?? '') === 'nokia' ? 'selected' : '' ?>>Nokia</option>
                                        <option value="fiberhome" <?= ($data['vendor'] ?? '') === 'fiberhome' ? 'selected' : '' ?>>FiberHome</option>
                                        <option value="other" <?= ($data['vendor'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                    <?php if (isset($errors['vendor'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['vendor']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Network Configuration -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Network Configuration</h5>
                            </div>
                            <div class="card-body">
                                <!-- IP Address -->
                                <div class="mb-3">
                                    <label for="ip_address" class="form-label">IP Address *</label>
                                    <input type="text" class="form-control <?= isset($errors['ip_address']) ? 'is-invalid' : '' ?>" 
                                           id="ip_address" name="ip_address" 
                                           value="<?= htmlspecialchars($data['ip_address'] ?? '') ?>" required
                                           pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$">
                                    <?php if (isset($errors['ip_address'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['ip_address']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Management VLAN -->
                                <div class="mb-3">
                                    <label for="management_vlan" class="form-label">Management VLAN</label>
                                    <input type="number" class="form-control <?= isset($errors['management_vlan']) ? 'is-invalid' : '' ?>" 
                                           id="management_vlan" name="management_vlan" 
                                           value="<?= htmlspecialchars($data['management_vlan'] ?? '') ?>"
                                           min="1" max="4094">
                                    <?php if (isset($errors['management_vlan'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['management_vlan']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Uplink Capacity -->
                                <div class="mb-3">
                                    <label for="uplink_capacity" class="form-label">Uplink Capacity (Mbps) *</label>
                                    <input type="number" class="form-control <?= isset($errors['uplink_capacity']) ? 'is-invalid' : '' ?>" 
                                           id="uplink_capacity" name="uplink_capacity" 
                                           value="<?= htmlspecialchars($data['uplink_capacity'] ?? '') ?>" required>
                                    <?php if (isset($errors['uplink_capacity'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['uplink_capacity']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Total PON Ports -->
                                <div class="mb-3">
                                    <label for="total_pon_ports" class="form-label">Total PON Ports *</label>
                                    <input type="number" class="form-control <?= isset($errors['total_pon_ports']) ? 'is-invalid' : '' ?>" 
                                           id="total_pon_ports" name="total_pon_ports" 
                                           value="<?= htmlspecialchars($data['total_pon_ports'] ?? '') ?>" required>
                                    <?php if (isset($errors['total_pon_ports'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['total_pon_ports']) ?>
                                        </div>
                                    <?php endif; ?>
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
                                                   value="<?= htmlspecialchars($data['location'] ?? '') ?>" required>
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
                                                <option value="active" <?= ($data['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="inactive" <?= ($data['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                <option value="maintenance" <?= ($data['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" 
                                                      rows="3"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create OLT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // IP Address validation
    const ipInput = document.getElementById('ip_address');
    ipInput.addEventListener('input', function() {
        const isValid = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(this.value);
        if (!isValid && this.value) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Required fields validation
        ['name', 'model', 'serial_number', 'ip_address', 'location', 'total_pon_ports', 'uplink_capacity'].forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        // IP Address validation
        if (!ipInput.value.match(/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/)) {
            ipInput.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
