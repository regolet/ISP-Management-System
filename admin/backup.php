<?php
require_once '../config.php';
check_auth();

$page_title = 'Database Backup';
$_SESSION['active_menu'] = 'backup';

// Get backup logs
$logs_query = "SELECT bl.*, u.username 
               FROM backup_logs bl
               LEFT JOIN users u ON bl.created_by = u.id
               ORDER BY bl.created_at DESC";
$logs = $conn->query($logs_query);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Database Backup</h1>
        <form method="POST" action="backup_create.php" style="display: inline;">
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bx bx-download"></i>
                <span>Create Backup</span>
            </button>
        </form>
    </div>

    <!-- Backup Logs -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs->num_rows > 0): ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['filename']); ?></td>
                                    <td><?php echo format_bytes($log['size']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($log['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $log['status'] === 'success' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($log['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="backup_download.php?file=<?php echo urlencode($log['filename']); ?>" 
                                               class="btn btn-sm btn-info" title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="confirmRestore('<?php echo $log['filename']; ?>')"
                                                    title="Restore">
                                                <i class="bx bx-reset"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete('<?php echo $log['filename']; ?>')"
                                                    title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bx bx-info-circle fs-4 text-muted"></i>
                                    <p class="text-muted mb-0">No backup logs found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="backup_restore.php">
                <input type="hidden" name="filename" id="restoreFilename">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Restore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        Warning: This will overwrite the current database!
                    </div>
                    <p>Are you sure you want to restore the database from this backup?</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning d-flex align-items-center gap-2">
                        <i class="bx bx-reset"></i>
                        <span>Restore Database</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="backup_delete.php">
                <input type="hidden" name="filename" id="deleteFilename">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bx bx-error-circle me-2"></i>
                        Warning: This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this backup file?</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger d-flex align-items-center gap-2">
                        <i class="bx bx-trash"></i>
                        <span>Delete Backup</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmRestore(filename) {
    document.getElementById('restoreFilename').value = filename;
    new bootstrap.Modal(document.getElementById('restoreModal')).show();
}

function confirmDelete(filename) {
    document.getElementById('deleteFilename').value = filename;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php
// Helper function to format bytes
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>

<?php include 'footer.php'; ?>
