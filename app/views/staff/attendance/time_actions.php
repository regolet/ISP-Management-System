<?php
$title = 'Time Actions Log - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Time Actions Log</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/staff/attendance/time-action" class="btn btn-primary">
                <i class="fa fa-clock"></i> Time Action
            </a>
            <a href="/staff/attendance/view" class="btn btn-secondary">
                <i class="fa fa-calendar"></i> View Attendance
            </a>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/staff/attendance/time-actions" class="row g-3">
            <div class="col-md-3">
                <label for="action_type" class="form-label">Action Type</label>
                <select class="form-select" id="action_type" name="action_type">
                    <option value="">All Actions</option>
                    <option value="in" <?= ('in' == ($filters['action_type'] ?? '')) ? 'selected' : '' ?>>
                        Clock In
                    </option>
                    <option value="out" <?= ('out' == ($filters['action_type'] ?? '')) ? 'selected' : '' ?>>
                        Clock Out
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="success" <?= ('success' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                        Success
                    </option>
                    <option value="failed" <?= ('failed' == ($filters['status'] ?? '')) ? 'selected' : '' ?>>
                        Failed
                    </option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= $filters['date_from'] ?? '' ?>">
                    <span class="input-group-text">to</span>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= $filters['date_to'] ?? '' ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Actions Log -->
<div class="card">
    <div class="card-body">
        <?php if (empty($actions)): ?>
            <div class="alert alert-info">
                No time actions found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actions as $action): ?>
                            <tr>
                                <td><?= date('M d, Y h:i:s A', strtotime($action['created_at'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= getActionBadgeClass($action['action_type']) ?>">
                                        <?= ucfirst($action['action_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($action['status']) ?>">
                                        <?= ucfirst($action['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($action['latitude'] && $action['longitude']): ?>
                                        <a href="#" class="view-map" 
                                           data-lat="<?= $action['latitude'] ?>" 
                                           data-lng="<?= $action['longitude'] ?>"
                                           title="View on map">
                                            <i class="fa fa-map-marker-alt"></i>
                                            View Location
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($action['device_info']): ?>
                                        <span class="text-muted" title="<?= htmlspecialchars($action['device_info']) ?>">
                                            <?= htmlspecialchars(substr($action['device_info'], 0, 30)) ?>...
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($action['ip_address']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info view-details" 
                                            data-id="<?= $action['id'] ?>" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Details Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Action Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="actionDetails">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Map Modal -->
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Location Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="map" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<?php
function getActionBadgeClass($action) {
    return match ($action) {
        'in' => 'success',
        'out' => 'warning',
        default => 'secondary'
    };
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'success' => 'success',
        'failed' => 'danger',
        default => 'secondary'
    };
}
?>

<!-- Include Google Maps JavaScript API -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date Range Validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
    });

    dateTo.addEventListener('change', function() {
        dateFrom.max = this.value;
    });

    // View Details Handler
    const actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const actionId = this.dataset.id;
            
            fetch(`/staff/attendance/time-actions/${actionId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('actionDetails').innerHTML = `
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Timestamp</th>
                                <td>${new Date(data.created_at).toLocaleString()}</td>
                            </tr>
                            <tr>
                                <th>Action</th>
                                <td>
                                    <span class="badge bg-${getActionBadgeClass(data.action_type)}">
                                        ${data.action_type}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-${getStatusBadgeClass(data.status)}">
                                        ${data.status}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>IP Address</th>
                                <td>${data.ip_address}</td>
                            </tr>
                            <tr>
                                <th>Device Info</th>
                                <td>${data.device_info || '-'}</td>
                            </tr>
                            ${data.notes ? `
                                <tr>
                                    <th>Notes</th>
                                    <td>${data.notes}</td>
                                </tr>
                            ` : ''}
                            ${data.error_message ? `
                                <tr>
                                    <th>Error Message</th>
                                    <td class="text-danger">${data.error_message}</td>
                                </tr>
                            ` : ''}
                        </table>
                    `;
                    actionModal.show();
                });
        });
    });

    // Map Handler
    const mapModal = new bootstrap.Modal(document.getElementById('mapModal'));
    let map = null;

    document.querySelectorAll('.view-map').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const lat = parseFloat(this.dataset.lat);
            const lng = parseFloat(this.dataset.lng);
            
            mapModal.show();
            
            // Initialize map after modal is shown
            mapModal._element.addEventListener('shown.bs.modal', function() {
                if (!map) {
                    map = new google.maps.Map(document.getElementById('map'), {
                        zoom: 15,
                        center: { lat, lng }
                    });
                }

                // Add marker
                new google.maps.Marker({
                    position: { lat, lng },
                    map: map
                });

                // Center map
                map.setCenter({ lat, lng });
            });
        });
    });
});
</script>
