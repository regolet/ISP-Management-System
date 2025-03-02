<?php
$title = 'Add Subscription - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add Subscription</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/subscriptions" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Subscriptions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/subscriptions" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- Customer Selection -->
                    <div class="mb-4">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" 
                                        <?= old('customer_id') == $customer['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['name']) ?> 
                                    (<?= htmlspecialchars($customer['customer_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['customer_id'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= $errors['customer_id'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Plan Selection -->
                    <div class="mb-4">
                        <label for="plan_id" class="form-label">Service Plan *</label>
                        <select class="form-select" id="plan_id" name="plan_id" required>
                            <option value="">Select Plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" 
                                        data-bandwidth="<?= $plan['bandwidth'] ?>"
                                        data-amount="<?= $plan['amount'] ?>"
                                        <?= old('plan_id') == $plan['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['name']) ?> - 
                                    <?= formatBandwidth($plan['bandwidth']) ?> - 
                                    <?= formatCurrency($plan['amount']) ?>/mo
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['plan_id'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= $errors['plan_id'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Subscription Period -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?= old('start_date', date('Y-m-d')) ?>" required>
                            <?php if (isset($errors['start_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['start_date'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?= old('end_date') ?>">
                            <small class="text-muted">Leave blank for indefinite subscription</small>
                            <?php if (isset($errors['end_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['end_date'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Installation Details -->
                    <div class="mb-4">
                        <label for="installation_address" class="form-label">Installation Address *</label>
                        <textarea class="form-control" id="installation_address" name="installation_address" 
                                  rows="3" required><?= old('installation_address') ?></textarea>
                        <?php if (isset($errors['installation_address'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= $errors['installation_address'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Equipment Information -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="router_model" class="form-label">Router Model *</label>
                            <select class="form-select" id="router_model" name="router_model" required>
                                <option value="">Select Router</option>
                                <?php foreach ($routers as $router): ?>
                                    <option value="<?= $router['id'] ?>" 
                                            <?= old('router_model') == $router['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($router['model']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['router_model'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['router_model'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="router_serial" class="form-label">Router Serial Number *</label>
                            <input type="text" class="form-control" id="router_serial" name="router_serial" 
                                   value="<?= old('router_serial') ?>" required>
                            <?php if (isset($errors['router_serial'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['router_serial'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="ont_model" class="form-label">ONT Model</label>
                            <select class="form-select" id="ont_model" name="ont_model">
                                <option value="">Select ONT</option>
                                <?php foreach ($onts as $ont): ?>
                                    <option value="<?= $ont['id'] ?>" 
                                            <?= old('ont_model') == $ont['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ont['model']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['ont_model'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['ont_model'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="ont_serial" class="form-label">ONT Serial Number</label>
                            <input type="text" class="form-control" id="ont_serial" name="ont_serial" 
                                   value="<?= old('ont_serial') ?>">
                            <?php if (isset($errors['ont_serial'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['ont_serial'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Network Configuration -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="ip_type" class="form-label">IP Address Type *</label>
                            <select class="form-select" id="ip_type" name="ip_type" required>
                                <option value="dynamic" <?= old('ip_type') === 'dynamic' ? 'selected' : '' ?>>
                                    Dynamic IP
                                </option>
                                <option value="static" <?= old('ip_type') === 'static' ? 'selected' : '' ?>>
                                    Static IP
                                </option>
                            </select>
                            <?php if (isset($errors['ip_type'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['ip_type'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6" id="staticIpField" style="display: none;">
                            <label for="ip_address" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="ip_address" name="ip_address" 
                                   value="<?= old('ip_address') ?>">
                            <?php if (isset($errors['ip_address'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['ip_address'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" 
                                  rows="3"><?= old('notes') ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= $errors['notes'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" 
                                onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ipType = document.getElementById('ip_type');
    const staticIpField = document.getElementById('staticIpField');
    const ontModel = document.getElementById('ont_model');
    const ontSerial = document.getElementById('ont_serial');

    // Toggle static IP field
    ipType.addEventListener('change', function() {
        staticIpField.style.display = this.value === 'static' ? 'block' : 'none';
        document.getElementById('ip_address').required = this.value === 'static';
    });

    // Toggle ONT serial requirement
    ontModel.addEventListener('change', function() {
        ontSerial.required = !!this.value;
    });

    // Initialize fields
    ipType.dispatchEvent(new Event('change'));
    ontModel.dispatchEvent(new Event('change'));

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
