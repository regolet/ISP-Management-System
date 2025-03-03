<?php
$title = 'Backup Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-data'></i> Backup Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" onclick="createBackup()">
            <i class='bx bx-plus'></i> Create Backup
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <!-- Settings Navigation -->
        <div class="card mb-4">
            <div class="list-group list-group-flush">
                <a href="/admin/settings/general" class="list-group-item list-group-item-action">
                    <i class='bx bx-cog'></i> General Settings
                </a>
                <a href="/admin/settings/roles" class="list-group-item list-group-item-action">
                    <i class='bx bx-shield'></i> Roles & Permissions
                </a>
                <a href="/admin/backup" class="list-group-item list-group-item-action active">
                    <i class='bx bx-data'></i> Backup Management
                </a>
                <a href="/admin/audit" class="list-group-item list-group-item-action">
                    <i class='bx bx-history'></i> Audit Logs
                </a>
            </div>
        </div>

        <!-- Backup Settings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Backup Settings</h5>
            </div>
            <div class="card-body">
                <form id="backupSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Maximum Backups</label>
                        <input type="number" class="form-control" name="max_backups" 
                               value="<?= $settings['max_backups'] ?? 10 ?>" min="1" max="50">
                        <div class="form-text">Number of backups to keep</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-save'></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <!-- Backup List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Created By</th>
                                <th>Size</th>
                                <th>Created At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($backups['backups'])): ?>
                                <?php foreach ($backups['backups'] as $backup): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($backup['filename']) ?></div>
                                        <?php if ($backup['notes']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($backup['notes']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($backup['username'] ?? 'System') ?></td>
                                    <td><?= formatBytes($backup['size']) ?></td>
                                    <td><?= formatDate($backup['created_at']) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="/admin/backup/<?= $backup['id'] ?>/download" 
                                               class="btn btn-sm btn-info" title="Download Backup">
                                                <i class='bx bx-download'></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="restoreBackup(<?= $backup['id'] ?>)" 
                                                    title="Restore Backup">
                                                <i class='bx bx-reset'></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteBackup(<?= $backup['id'] ?>)" 
                                                    title="Delete Backup">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class='bx bx-info-circle text-muted fs-1'></i>
                                        <p class="text-muted mb-0">No backups found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($backups['pages'] > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $backups['pages']; $i++): ?>
                            <li class="page-item <?= $current_page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createBackupForm">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Create backup
function createBackup() {
    const modal = new bootstrap.Modal(document.getElementById('createBackupModal'));
    const form = document.getElementById('createBackupForm');
    
    form.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        
        fetch('/admin/backup/create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to create backup');
            }
        });
    };
    
    modal.show();
}

// Restore backup
function restoreBackup(id) {
    if (confirm('Are you sure you want to restore this backup? This will overwrite current data.')) {
        fetch(`/admin/backup/${id}/restore`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to restore backup');
            }
        });
    }
}

// Delete backup
function deleteBackup(id) {
    if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        fetch(`/admin/backup/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to delete backup');
            }
        });
    }
}

// Update backup settings
document.getElementById('backupSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/admin/backup/settings', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update settings');
        }
    });
});

// Format file size
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
</script>
