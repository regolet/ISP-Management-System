<?php
$title = 'My Profile - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>My Profile</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fa fa-key"></i> Change Password
        </button>
    </div>
</div>

<div class="row">
    <!-- Personal Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Personal Information</h5>
                <button type="button" class="btn btn-sm btn-primary" id="editProfile">
                    <i class="fa fa-edit"></i> Edit
                </button>
            </div>
            <div class="card-body">
                <form id="profileForm" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($staff['first_name']) ?>" required disabled>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($staff['last_name']) ?>" required disabled>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($staff['email']) ?>" required disabled>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($staff['phone']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" 
                               value="<?= htmlspecialchars($staff['department']) ?>" disabled readonly>
                    </div>

                    <div class="mb-3">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" 
                               value="<?= htmlspecialchars($staff['position']) ?>" disabled readonly>
                    </div>

                    <div class="mb-3">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="text" class="form-control" id="hire_date" 
                               value="<?= date('M d, Y', strtotime($staff['hire_date'])) ?>" disabled readonly>
                    </div>

                    <div class="form-actions" style="display: none;">
                        <button type="button" class="btn btn-secondary" id="cancelEdit">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Emergency Contact</h5>
                <button type="button" class="btn btn-sm btn-primary" id="editEmergencyContact">
                    <i class="fa fa-edit"></i> Edit
                </button>
            </div>
            <div class="card-body">
                <form id="emergencyContactForm" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <div class="mb-3">
                        <label for="emergency_contact_name" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="emergency_contact_name" 
                               name="emergency_contact_name" 
                               value="<?= htmlspecialchars($staff['emergency_contact_name'] ?? '') ?>" 
                               disabled required>
                    </div>

                    <div class="mb-3">
                        <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="emergency_contact_phone" 
                               name="emergency_contact_phone" 
                               value="<?= htmlspecialchars($staff['emergency_contact_phone'] ?? '') ?>" 
                               disabled required>
                    </div>

                    <div class="mb-3">
                        <label for="emergency_contact_relation" class="form-label">Relationship</label>
                        <input type="text" class="form-control" id="emergency_contact_relation" 
                               name="emergency_contact_relation" 
                               value="<?= htmlspecialchars($staff['emergency_contact_relation'] ?? '') ?>" 
                               disabled required>
                    </div>

                    <div class="form-actions" style="display: none;">
                        <button type="button" class="btn btn-secondary" id="cancelEmergencyEdit">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="col-md-8">
        <!-- Employment Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Employment Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Employee ID</th>
                                <td><?= htmlspecialchars($staff['employee_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-<?= $staff['status'] === 'active' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($staff['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Work Schedule</th>
                                <td><?= htmlspecialchars($staff['work_schedule'] ?? 'Regular') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Supervisor</th>
                                <td><?= htmlspecialchars($staff['supervisor_name'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th>Team</th>
                                <td><?= htmlspecialchars($staff['team'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th>Location</th>
                                <td><?= htmlspecialchars($staff['work_location'] ?? 'Main Office') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($recentActivity ?? [] as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-<?= getActivityTypeClass($activity['type']) ?>"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($activity['description']) ?></h6>
                                    <small class="text-muted">
                                        <?= timeAgo($activity['created_at']) ?>
                                    </small>
                                </div>
                                <?php if (!empty($activity['details'])): ?>
                                    <p class="mb-0 text-muted">
                                        <?= htmlspecialchars($activity['details']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documents</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                        data-bs-target="#uploadDocumentModal">
                    <i class="fa fa-upload"></i> Upload Document
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Uploaded</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents ?? [] as $document): ?>
                                <tr>
                                    <td><?= htmlspecialchars($document['name']) ?></td>
                                    <td><?= htmlspecialchars($document['type']) ?></td>
                                    <td><?= date('M d, Y', strtotime($document['uploaded_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getDocumentStatusClass($document['status']) ?>">
                                            <?= ucfirst($document['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/uploads/documents/<?= $document['file'] ?>" 
                                               class="btn btn-info" target="_blank" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger delete-document" 
                                                    data-id="<?= $document['id'] ?>" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="passwordForm" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password" required minlength="8">
                        <div class="form-text">
                            Password must be at least 8 characters long and include uppercase, lowercase, 
                            number, and special character.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="changePassword">Change Password</button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="documentForm" class="needs-validation" novalidate>
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <div class="mb-3">
                        <label for="document_name" class="form-label">Document Name</label>
                        <input type="text" class="form-control" id="document_name" 
                               name="document_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="document_type" class="form-label">Document Type</label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="">Select Type</option>
                            <option value="identification">Identification</option>
                            <option value="certification">Certification</option>
                            <option value="contract">Contract</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="document_file" class="form-label">File</label>
                        <input type="file" class="form-control" id="document_file" 
                               name="document_file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="form-text">
                            Max size: 5MB. Accepted formats: PDF, DOC, DOCX, JPG, PNG
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadDocument">Upload</button>
            </div>
        </div>
    </div>
</div>

<?php
function getActivityTypeClass($type) {
    return match ($type) {
        'attendance' => 'success',
        'leave' => 'warning',
        'expense' => 'info',
        'document' => 'primary',
        default => 'secondary'
    };
}

function getDocumentStatusClass($status) {
    return match ($status) {
        'verified' => 'success',
        'pending' => 'warning',
        'rejected' => 'danger',
        default => 'secondary'
    };
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

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    padding: 10px;
    border-radius: 4px;
    background-color: #f8f9fa;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile Edit Handler
    const profileForm = document.getElementById('profileForm');
    const editProfile = document.getElementById('editProfile');
    const cancelEdit = document.getElementById('cancelEdit');
    const formInputs = profileForm.querySelectorAll('input:not([readonly])');
    const formActions = profileForm.querySelector('.form-actions');

    editProfile.addEventListener('click', function() {
        formInputs.forEach(input => input.disabled = false);
        formActions.style.display = 'block';
        this.style.display = 'none';
    });

    cancelEdit.addEventListener('click', function() {
        formInputs.forEach(input => input.disabled = true);
        formActions.style.display = 'none';
        editProfile.style.display = 'block';
        profileForm.reset();
    });

    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        const formData = new FormData(this);
        fetch('/staff/profile/update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update profile');
            }
        });
    });

    // Emergency Contact Edit Handler
    const emergencyForm = document.getElementById('emergencyContactForm');
    const editEmergency = document.getElementById('editEmergencyContact');
    const cancelEmergency = document.getElementById('cancelEmergencyEdit');
    const emergencyInputs = emergencyForm.querySelectorAll('input');
    const emergencyActions = emergencyForm.querySelector('.form-actions');

    editEmergency.addEventListener('click', function() {
        emergencyInputs.forEach(input => input.disabled = false);
        emergencyActions.style.display = 'block';
        this.style.display = 'none';
    });

    cancelEmergency.addEventListener('click', function() {
        emergencyInputs.forEach(input => input.disabled = true);
        emergencyActions.style.display = 'none';
        editEmergency.style.display = 'block';
        emergencyForm.reset();
    });

    emergencyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        const formData = new FormData(this);
        fetch('/staff/profile/update-emergency-contact', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update emergency contact');
            }
        });
    });

    // Password Change Handler
    document.getElementById('changePassword').addEventListener('click', function() {
        const form = document.getElementById('passwordForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        if (form.new_password.value !== form.confirm_password.value) {
            alert('New passwords do not match');
            return;
        }

        const formData = new FormData(form);
        fetch('/staff/profile/change-password', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password changed successfully');
                bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                form.reset();
            } else {
                alert(data.error || 'Failed to change password');
            }
        });
    });

    // Document Upload Handler
    document.getElementById('uploadDocument').addEventListener('click', function() {
        const form = document.getElementById('documentForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const formData = new FormData(form);
        fetch('/staff/profile/upload-document', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to upload document');
            }
        });
    });

    // Document Delete Handler
    document.querySelectorAll('.delete-document').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this document?')) {
                const documentId = this.dataset.id;
                
                fetch(`/staff/profile/documents/${documentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to delete document');
                    }
                });
            }
        });
    });
});
</script>
