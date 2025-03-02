<?php
$title = 'OLT Management - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>OLT Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/network/olts/create" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add New OLT
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/network/olts" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                       placeholder="Name or IP Address">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="maintenance" <?= ($_GET['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="sort" class="form-label">Sort By</label>
                <select class="form-select" id="sort" name="sort">
                    <option value="name" <?= ($_GET['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Name</option>
                    <option value="location" <?= ($_GET['sort'] ?? '') === 'location' ? 'selected' : '' ?>>Location</option>
                    <option value="uptime" <?= ($_GET['sort'] ?? '') === 'uptime' ? 'selected' : '' ?>>Uptime</option>
                    <option value="onus" <?= ($_GET['sort'] ?? '') === 'onus' ? 'selected' : '' ?>>Connected ONUs</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- OLTs Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($olts)): ?>
            <div class="alert alert-info">
                No OLTs found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            <th>PON Ports</th>
                            <th>Connected ONUs</th>
                            <th>Uplink</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($olts as $olt): ?>
                            <?php $stats = $olt['stats']; ?>
                            <tr>
                                <td>
                                    <a href="/admin/network/olts/<?= $olt['id'] ?>">
                                        <?= htmlspecialchars($olt['name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="text-monospace"><?= htmlspecialchars($olt['ip_address']) ?></span>
                                    <?php if ($olt['management_vlan']): ?>
                                        <span class="badge bg-secondary">VLAN <?= $olt['management_vlan'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($olt['location']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?= $stats['total_ports'] ?> Total
                                        </div>
                                        <div class="progress flex-grow-1" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= ($stats['total_onus'] / ($olt['total_pon_ports'] * 64)) * 100 ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?= $stats['active_onus'] ?> Active
                                        </div>
                                        <div class="progress flex-grow-1" style="height: 5px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= ($stats['active_onus'] / $stats['total_onus']) * 100 ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $stats['total_onus'] - $stats['active_onus'] ?> Inactive
                                    </small>
                                </td>
                                <td>
                                    <?= formatBandwidth($olt['uplink_capacity']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($olt['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($olt['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/network/olts/<?= $olt['id'] ?>" class="btn btn-info" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="/admin/network/olts/<?= $olt['id'] ?>/edit" class="btn btn-primary" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-warning check-connectivity" 
                                                data-id="<?= $olt['id'] ?>" title="Check Connectivity">
                                            <i class="fa fa-network-wired"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger delete-olt" 
                                                data-id="<?= $olt['id'] ?>" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this OLT? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    Warning: Deleting an OLT will affect all connected ONUs and customer services.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusBadgeClass($status) {
    return match ($status) {
        'active' => 'success',
        'inactive' => 'danger',
        'maintenance' => 'warning',
        default => 'secondary'
    };
}

function formatBandwidth($bw) {
    if ($bw >= 1000) {
        return ($bw / 1000) . ' Gbps';
    }
    return $bw . ' Mbps';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    let oltId = null;

    // Handle delete button clicks
    document.querySelectorAll('.delete-olt').forEach(button => {
        button.addEventListener('click', function() {
            oltId = this.dataset.id;
            deleteModal.show();
        });
    });

    // Handle delete confirmation
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (oltId) {
            fetch(`/admin/network/olts/${oltId}`, {
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
                    alert(data.error || 'Failed to delete OLT');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the OLT');
            })
            .finally(() => {
                deleteModal.hide();
            });
        }
    });

    // Handle connectivity checks
    document.querySelectorAll('.check-connectivity').forEach(button => {
        button.addEventListener('click', function() {
            const oltId = this.dataset.id;
            const icon = this.querySelector('i');
            
            icon.className = 'fa fa-spinner fa-spin';
            this.disabled = true;

            fetch(`/admin/network/olts/${oltId}/check-connectivity`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Connection successful');
                        icon.className = 'fa fa-network-wired';
                    } else {
                        alert('Connection failed: ' + data.error);
                        icon.className = 'fa fa-exclamation-triangle';
                    }
                })
                .catch(error => {
                    alert('Error checking connectivity');
                    icon.className = 'fa fa-exclamation-triangle';
                })
                .finally(() => {
                    this.disabled = false;
                });
        });
    });
});
</script>
