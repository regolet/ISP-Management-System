<?php
require_once '../config.php';
check_auth();

$page_title = 'Add New Employee';
$_SESSION['active_menu'] = 'employees';

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Add New Employee</h1>
            <a href="employees.php" class="btn btn-secondary">
                <i class="bx bx-arrow-back"></i> Back to List
            </a>
        </div>

        <!-- Employee Form -->
        <div class="card">
            <div class="card-body">
                <form action="employee_save.php" method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                            <div class="invalid-feedback">Please enter first name</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required>
                            <div class="invalid-feedback">Please enter last name</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="position" required>
                            <div class="invalid-feedback">Please enter position</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" name="department" required>
                                <option value="">Select Department</option>
                                <option value="IT">IT</option>
                                <option value="HR">HR</option>
                                <option value="Finance">Finance</option>
                                <option value="Operations">Operations</option>
                                <option value="Sales">Sales</option>
                            </select>
                            <div class="invalid-feedback">Please select department</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Basic Salary <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="basic_salary" step="0.01" min="0" required>
                                <div class="invalid-feedback">Please enter basic salary</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Daily Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="daily_rate" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="hire_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                            <div class="invalid-feedback">Please select hire date</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SSS Number</label>
                            <input type="text" class="form-control" name="sss_no">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">PhilHealth Number</label>
                            <input type="text" class="form-control" name="philhealth_no">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pag-IBIG Number</label>
                            <input type="text" class="form-control" name="pagibig_no">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">TIN Number</label>
                            <input type="text" class="form-control" name="tin_no">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" class="form-control" name="bank_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bank Account Number</label>
                            <input type="text" class="form-control" name="bank_account_no">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="allowance" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Employee</button>
                        <button type="reset" class="btn btn-secondary">Reset Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include 'footer.php'; ?>

