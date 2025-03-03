<?php
$title = 'Add New Customer - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Customer</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/customers" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Customers
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/customers/create" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <!-- Personal Information -->
                    <h5 class="mb-4">Personal Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                                   id="first_name" name="first_name" value="<?= old('first_name') ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['first_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                                   id="last_name" name="last_name" value="<?= old('last_name') ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['last_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?= old('email') ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?= old('phone') ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['phone']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" 
                                      id="address" name="address" rows="3" required><?= old('address') ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['address']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Service Information -->
                    <h5 class="mb-4">Service Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="plan_id" class="form-label">Service Plan *</label>
                            <select class="form-select <?= isset($errors['plan_id']) ? 'is-invalid' : '' ?>" 
                                    id="plan_id" name="plan_id" required>
                                <option value="">Select Plan</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?= $plan['id'] ?>" 
                                            <?= (old('plan_id') == $plan['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($plan['name']) ?> - 
                                        <?= formatBandwidth($plan['bandwidth']) ?> - 
                                        <?= formatCurrency($plan['monthly_fee']) ?>/mo
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['plan_id'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['plan_id']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="installation_date" class="form-label">Installation Date *</label>
                            <input type="date" class="form-control <?= isset($errors['installation_date']) ? 'is-invalid' : '' ?>" 
                                   id="installation_date" name="installation_date" 
                                   value="<?= old('installation_date', date('Y-m-d')) ?>" required>
                            <?php if (isset($errors['installation_date'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['installation_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="installation_address" class="form-label">Installation Address *</label>
                            <textarea class="form-control <?= isset($errors['installation_address']) ? 'is-invalid' : '' ?>" 
                                      id="installation_address" name="installation_address" rows="3" required><?= old('installation_address') ?></textarea>
                            <?php if (isset($errors['installation_address'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['installation_address']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="same_as_address">
                                <label class="form-check-label" for="same_as_address">
                                    Same as residential address
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="contract_period" class="form-label">Contract Period (Months) *</label>
                            <select class="form-select <?= isset($errors['contract_period']) ? 'is-invalid' : '' ?>" 
                                    id="contract_period" name="contract_period" required>
                                <option value="">Select Period</option>
                                <?php foreach ([12, 24, 36] as $months): ?>
                                    <option value="<?= $months ?>" 
                                            <?= (old('contract_period') == $months) ? 'selected' : '' ?>>
                                        <?= $months ?> Months
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['contract_period'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['contract_period']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="ip_type" class="form-label">IP Address Type *</label>
                            <select class="form-select <?= isset($errors['ip_type']) ? 'is-invalid' : '' ?>" 
                                    id="ip_type" name="ip_type" required>
                                <option value="dynamic" <?= (old('ip_type') == 'dynamic') ? 'selected' : '' ?>>
                                    Dynamic IP
                                </option>
                                <option value="static" <?= (old('ip_type') == 'static') ? 'selected' : '' ?>>
                                    Static IP
                                </option>
                            </select>
                            <?php if (isset($errors['ip_type'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['ip_type']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Equipment Information -->
                    <h5 class="mb-4">Equipment Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="router_model" class="form-label">Router Model *</label>
                            <select class="form-select <?= isset($errors['router_model']) ? 'is-invalid' : '' ?>" 
                                    id="router_model" name="router_model" required>
                                <option value="">Select Router</option>
                                <?php foreach ($routers as $router): ?>
                                    <option value="<?= $router['id'] ?>" 
                                            <?= (old('router_model') == $router['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($router['model']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['router_model'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['router_model']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="router_serial" class="form-label">Router Serial Number *</label>
                            <input type="text" class="form-control <?= isset($errors['router_serial']) ? 'is-invalid' : '' ?>" 
                                   id="router_serial" name="router_serial" value="<?= old('router_serial') ?>" required>
                            <?php if (isset($errors['router_serial'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['router_serial']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="ont_model" class="form-label">ONT Model</label>
                            <select class="form-select <?= isset($errors['ont_model']) ? 'is-invalid' : '' ?>" 
                                    id="ont_model" name="ont_model">
                                <option value="">Select ONT</option>
                                <?php foreach ($onts as $ont): ?>
                                    <option value="<?= $ont['id'] ?>" 
                                            <?= (old('ont_model') == $ont['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ont['model']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['ont_model'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['ont_model']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="ont_serial" class="form-label">ONT Serial Number</label>
                            <input type="text" class="form-control <?= isset($errors['ont_serial']) ? 'is-invalid' : '' ?>" 
                                   id="ont_serial" name="ont_serial" value="<?= old('ont_serial') ?>">
                            <?php if (isset($errors['ont_serial'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['ont_serial']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Account Settings -->
                    <h5 class="mb-4">Account Settings</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                   id="username" name="username" value="<?= old('username') ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['username']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                       id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="generatePassword">
                                    Generate
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="send_credentials" name="send_credentials" checked>
                                <label class="form-check-label" for="send_credentials">
                                    Send login credentials via email
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Additional Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" 
                                  id="notes" name="notes" rows="3"><?= old('notes') ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['notes']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" onclick="history.back()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Create Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
function formatBandwidth($speed) {
    if ($speed >= 1000) {
        return ($speed / 1000) . ' Gbps';
    }
    return $speed . ' Mbps';
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}
?>

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

    // Same as address checkbox
    const sameAsAddress = document.getElementById('same_as_address');
    const address = document.getElementById('address');
    const installationAddress = document.getElementById('installation_address');

    sameAsAddress.addEventListener('change', function() {
        if (this.checked) {
            installationAddress.value = address.value;
        } else {
            installationAddress.value = '';
        }
    });

    address.addEventListener('input', function() {
        if (sameAsAddress.checked) {
            installationAddress.value = this.value;
        }
    });

    // Password generator
    document.getElementById('generatePassword').addEventListener('click', function() {
        const length = 12;
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let password = '';
        
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        
        document.getElementById('password').value = password;
        document.getElementById('password').type = 'text';
        
        setTimeout(() => {
            document.getElementById('password').type = 'password';
        }, 3000);
    });

    // Username generator
    document.getElementById('email').addEventListener('blur', function() {
        const username = document.getElementById('username');
        if (!username.value) {
            username.value = this.value.split('@')[0].toLowerCase()
                .replace(/[^a-z0-9]/g, '');
        }
    });

    // ONT fields toggle
    const ontModel = document.getElementById('ont_model');
    const ontSerial = document.getElementById('ont_serial');

    ontModel.addEventListener('change', function() {
        ontSerial.required = !!this.value;
    });
});
</script>
