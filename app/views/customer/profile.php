<?php
$title = 'My Profile - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>My Profile</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/customer/dashboard" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Profile Information</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fa fa-edit"></i> Edit Profile
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-4">
                        <img src="<?= $customer['profile_image'] ? '/uploads/profiles/' . $customer['profile_image'] : '/img/default-profile.png' ?>" 
                             alt="Profile" class="img-fluid rounded-circle mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                            <i class="fa fa-camera"></i> Change Photo
                        </button>
                    </div>
                    <div class="col-md-9">
                        <table class="table">
                            <tr>
                                <th width="30%">Account Number</th>
                                <td><?= htmlspecialchars($customer['account_number']) ?></td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td>
                                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?= htmlspecialchars($customer['email']) ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?= htmlspecialchars($customer['phone']) ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?= nl2br(htmlspecialchars($customer['address'])) ?></td>
                            </tr>
                            <tr>
                                <th>Member Since</th>
                                <td><?= date('F d, Y', strtotime($customer['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Service Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="30%">Current Plan</th>
                        <td>
                            <?= htmlspecialchars($subscription['plan_name']) ?>
                            <small class="text-muted d-block">
                                <?= formatBandwidth($subscription['bandwidth']) ?> |
                                <?= formatCurrency($subscription['monthly_fee']) ?>/month
                            </small>
                        </td>
                    </tr>
                    <tr>
                        <th>Installation Address</th>
                        <td><?= nl2br(htmlspecialchars($subscription['installation_address'])) ?></td>
                    </tr>
                    <tr>
                        <th>Installation Date</th>
                        <td><?= date('F d, Y', strtotime($subscription['installation_date'])) ?></td>
                    </tr>
                    <tr>
                        <th>Contract Period</th>
                        <td>
                            <?= $subscription['contract_period'] ?> months
                            <small class="text-muted d-block">
                                Expires: <?= date('F d, Y', strtotime($subscription['contract_end_date'])) ?>
                            </small>
                        </td>
                    </tr>
                    <tr>
                        <th>Equipment</th>
                        <td>
                            <div>Router: <?= htmlspecialchars($subscription['router_model']) ?></div>
                            <div>Serial: <?= htmlspecialchars($subscription['router_serial']) ?></div>
                            <?php if ($subscription['ont_serial']): ?>
                                <div>ONT Serial: <?= htmlspecialchars($subscription['ont_serial']) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Security Settings</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fa fa-key"></i> Change Password
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#twoFactorModal">
                            <i class="fa fa-shield-alt"></i> Two-Factor Authentication
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Sidebar -->
    <div class="col-md-4">
        <!-- Account Status -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fa fa-circle text-<?= $customer['status'] === 'active' ? 'success' : 'danger' ?> me-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">Account Status</h6>
                        <small class="text-muted">
                            <?= ucfirst($customer['status']) ?> since 
                            <?= date('M d, Y', strtotime($customer['status_changed_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($activity['description']) ?></h6>
                                <small class="text-muted">
                                    <?= timeAgo($activity['created_at']) ?>
                                </small>
                            </div>
                            <?php if (!empty($activity['details'])): ?>
                                <p class="mb-1 text-muted small">
                                    <?= htmlspecialchars($activity['details']) ?>
                                </p>
                            <?php endif; ?>
                            <small class="text-muted">
                                <i class="fa fa-<?= $activity['icon'] ?>"></i>
                                <?= htmlspecialchars($activity['location']) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/customer/profile/update" class="needs-validation" novalidate>
                <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($customer['first_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($customer['last_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($customer['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($customer['phone']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($customer['address']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/customer/profile/upload-photo" enctype="multipart/form-data">
                <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">Change Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="photo" class="form-label">Select Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                        <div class="form-text">
                            Maximum file size: 5MB. Supported formats: JPG, PNG
                        </div>
                    </div>
                    <div id="imagePreview" class="text-center d-none">
                        <img src="" alt="Preview" class="img-fluid rounded mb-2" style="max-height: 200px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/customer/profile/change-password" class="needs-validation" novalidate>
                <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               required minlength="8">
                        <div class="form-text">
                            Minimum 8 characters, must include uppercase, lowercase, number, and special character
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Two-Factor Authentication Modal -->
<div class="modal fade" id="twoFactorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Two-Factor Authentication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($customer['2fa_enabled']): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> Two-factor authentication is enabled
                    </div>
                    <p>Your account is protected with an additional layer of security.</p>
                    <form method="POST" action="/customer/profile/disable-2fa">
                        <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-shield-alt"></i> Disable 2FA
                        </button>
                    </form>
                <?php else: ?>
                    <p>Enable two-factor authentication to add an extra layer of security to your account.</p>
                    <a href="/customer/profile/setup-2fa" class="btn btn-primary">
                        <i class="fa fa-shield-alt"></i> Enable 2FA
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatBandwidth($speed) {
    if ($speed >= 1000) {
        return ($speed / 1000) . ' Gbps';
    }
    return $speed . ' Mbps';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image Preview
    document.getElementById('photo').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.querySelector('img').src = e.target.result;
                preview.classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
        }
    });

    // Password Validation
    const passwordForm = document.querySelector('#changePasswordModal form');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    passwordForm.addEventListener('submit', function(e) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match');
            return;
        }

        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(newPassword.value)) {
            e.preventDefault();
            alert('Password must meet all requirements');
            return;
        }
    });
});
</script>
