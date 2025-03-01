<?php
require_once 'config.php';
check_auth('admin'); // Only admin can access settings
$_SESSION['active_menu'] = 'settings';

// Fetch current settings
$stmt = $conn->prepare("SELECT * FROM settings WHERE setting_key = 'company_profile' LIMIT 1");
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// If no settings exist, initialize empty array
if (!$settings) {
    $settings = [
        'company_name' => '',
        'company_email' => '',
        'company_phone' => '',
        'company_website' => '',
        'company_address' => '',
        'tax_rate' => '0',
        'currency' => 'PHP',
        'logo_path' => ''
    ];
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'navbar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Company Settings</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="settings_save.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name*</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($settings['company_name'] ?? '') ?: ''; ?>" required>
                                <div class="invalid-feedback">Company name is required</div>
                            </div>
                            <div class="col-md-6">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email"
                                       value="<?php echo htmlspecialchars($settings['company_email'] ?? '') ?: ''; ?>">
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_phone" class="form-label">Company Phone</label>
                                <input type="tel" class="form-control" id="company_phone" name="company_phone"
                                       value="<?php echo htmlspecialchars($settings['company_phone'] ?? '') ?: ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="company_website" class="form-label">Company Website</label>
                                <input type="url" class="form-control" id="company_website" name="company_website"
                                       value="<?php echo htmlspecialchars($settings['company_website'] ?? '') ?: ''; ?>">
                                <div class="invalid-feedback">Please enter a valid URL</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="company_address" class="form-label">Company Address</label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($settings['company_address'] ?? '') ?: ''; ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                       value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0') ?: '0'; ?>"
                                       step="0.01" min="0" max="100">
                                <div class="invalid-feedback">Please enter a valid tax rate between 0 and 100</div>
                            </div>
                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="PHP" <?php echo ($settings['currency'] ?? '') === 'PHP' ? 'selected' : ''; ?>>PHP</option>
                                    <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                    <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label">Company Logo</label>
                            <?php if (!empty($settings['logo_path'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" 
                                         alt="Company Logo" style="max-height: 100px;" class="mb-2">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <div class="form-text">Recommended size: 200x200 pixels. Max file size: 2MB</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Preview image before upload
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) { // 2MB
            alert('File size must be less than 2MB');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                if (this.width > 500 || this.height > 500) {
                    alert('Image dimensions should not exceed 500x500 pixels');
                    document.getElementById('logo').value = '';
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'footer.php'; ?>
