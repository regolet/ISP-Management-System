<?php
$title = 'Add Employee - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add Employee</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/employees" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Employees
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/employees" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- Personal Information -->
                    <h5 class="mb-4">Personal Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= old('first_name') ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['first_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= old('last_name') ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['last_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= old('email') ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= old('phone') ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['phone'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="3" required><?= old('address') ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['address'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <h5 class="mb-4">Employment Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="position" class="form-label">Position *</label>
                            <input type="text" class="form-control" id="position" name="position" 
                                   value="<?= old('position') ?>" required>
                            <?php if (isset($errors['position'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['position'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="department" class="form-label">Department *</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $key => $name): ?>
                                    <option value="<?= $key ?>" 
                                            <?= old('department') === $key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['department'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['department'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="hire_date" class="form-label">Hire Date *</label>
                            <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                   value="<?= old('hire_date', date('Y-m-d')) ?>" required>
                            <?php if (isset($errors['hire_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['hire_date'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="daily_rate" class="form-label">Daily Rate *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="daily_rate" name="daily_rate" 
                                       step="0.01" min="0" value="<?= old('daily_rate') ?>" required>
                            </div>
                            <?php if (isset($errors['daily_rate'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['daily_rate'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Government IDs -->
                    <h5 class="mb-4">Government IDs</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="sss_number" class="form-label">SSS Number *</label>
                            <input type="text" class="form-control" id="sss_number" name="sss_number" 
                                   value="<?= old('sss_number') ?>" required>
                            <?php if (isset($errors['sss_number'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['sss_number'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="philhealth_number" class="form-label">PhilHealth Number *</label>
                            <input type="text" class="form-control" id="philhealth_number" name="philhealth_number" 
                                   value="<?= old('philhealth_number') ?>" required>
                            <?php if (isset($errors['philhealth_number'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['philhealth_number'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="pagibig_number" class="form-label">Pag-IBIG Number *</label>
                            <input type="text" class="form-control" id="pagibig_number" name="pagibig_number" 
                                   value="<?= old('pagibig_number') ?>" required>
                            <?php if (isset($errors['pagibig_number'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['pagibig_number'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="tin_number" class="form-label">TIN Number *</label>
                            <input type="text" class="form-control" id="tin_number" name="tin_number" 
                                   value="<?= old('tin_number') ?>" required>
                            <?php if (isset($errors['tin_number'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['tin_number'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <h5 class="mb-4">Emergency Contact</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="emergency_contact_name" class="form-label">Contact Name *</label>
                            <input type="text" class="form-control" id="emergency_contact_name" 
                                   name="emergency_contact_name" value="<?= old('emergency_contact_name') ?>" required>
                            <?php if (isset($errors['emergency_contact_name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['emergency_contact_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_phone" class="form-label">Contact Phone *</label>
                            <input type="tel" class="form-control" id="emergency_contact_phone" 
                                   name="emergency_contact_phone" value="<?= old('emergency_contact_phone') ?>" required>
                            <?php if (isset($errors['emergency_contact_phone'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $errors['emergency_contact_phone'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User Account -->
                    <h5 class="mb-4">User Account</h5>
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="create_account" 
                                   name="create_account" <?= old('create_account') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="create_account">
                                Create user account for employee
                            </label>
                        </div>
                        <small class="text-muted">
                            If checked, a user account will be created using the employee's email address
                        </small>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" 
                                onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Employee</button>
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
});
</script>
