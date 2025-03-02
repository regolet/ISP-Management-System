<?php
$title = 'Network Health Check - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Network Health Check</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" id="refreshHealth">
            <i class="fa fa-sync"></i> Refresh Status
        </button>
        <button type="button" class="btn btn-warning" id="runDiagnostics">
            <i class="fa fa-stethoscope"></i> Run Diagnostics
        </button>
    </div>
</div>

<!-- Health Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Network Status</h5>
                <h3 class="card-text">
                    <?php
                    $totalDevices = count($issues);
                    $healthyDevices = count(array_filter($issues, fn($i) => $i['severity'] === 'low'));
                    $healthPercentage = $totalDevices > 0 ? 
                        round(($healthyDevices / $totalDevices) * 100) : 100;
                    echo $healthPercentage . '%';
                    ?>
                </h3>
                <p class="mb-0">Overall Health</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">Critical Issues</h5>
                <h3 class="card-text">
                    <?= count(array_filter($issues, fn($i) => $i['severity'] === 'high')) ?>
                </h3>
                <p class="mb-0">Need Attention</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Warnings</h5>
                <h3 class="card-text">
                    <?= count(array_filter($issues, fn($i) => $i['severity'] === 'medium')) ?>
                </h3>
                <p class="mb-0">Monitor Status</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Monitored Devices</h5>
                <h3 class="card-text"><?= $totalDevices ?></h3>
                <p class="mb-0">Total Devices</p>
            </div>
        </div>
    </div>
</div>

<!-- Signal Quality Chart -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Signal Quality Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="signalQualityChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Issues by Device Type</h5>
            </div>
            <div class="card-body">
                <canvas id="issuesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Issues List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Network Issues</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Type</th>
                        <th>Issue</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($issues)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No issues found. Network is healthy.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($issues as $issue): ?>
                            <tr>
                                <td>
                                    <a href="/admin/network/<?= $issue['type'] ?>s/<?= $issue['device_id'] ?>">
                                        <?= htmlspecialchars($issue['device']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getDeviceTypeBadgeClass($issue['type']) ?>">
                                        <?= strtoupper($issue['type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($issue['issue']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getSeverityBadgeClass($issue['severity']) ?>">
                                        <?= ucfirst($issue['severity']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($issue['resolved']): ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info view-details" 
                                                data-issue='<?= json_encode($issue) ?>' title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <?php if (!$issue['resolved']): ?>
                                            <button type="button" class="btn btn-success resolve-issue" 
                                                    data-id="<?= $issue['id'] ?>" title="Mark as Resolved">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-warning diagnose-issue" 
                                                data-id="<?= $issue['id'] ?>" title="Run Diagnostics">
                                            <i class="fa fa-stethoscope"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Issue Details Modal -->
<div class="modal fade" id="issueDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Device Information</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Device</th>
                                <td id="deviceName"></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td id="deviceType"></td>
                            </tr>
                            <tr>
                                <th>Location</th>
                                <td id="deviceLocation"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Issue Information</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Severity</th>
                                <td id="issueSeverity"></td>
                            </tr>
                            <tr>
                                <th>Detected</th>
                                <td id="issueDetected"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="issueStatus"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Issue Description</h6>
                    <p id="issueDescription"></p>
                </div>
                <div class="mt-3">
                    <h6>Diagnostic Information</h6>
                    <pre id="diagnosticInfo" class="bg-light p-3"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="runSpecificDiagnostic">Run Diagnostic</button>
            </div>
        </div>
    </div>
</div>

<?php
function getDeviceTypeBadgeClass($type) {
    return match ($type) {
        'olt' => 'primary',
        'onu' => 'info',
        'nap' => 'warning',
        'lcp' => 'success',
        default => 'secondary'
    };
}

function getSeverityBadgeClass($severity) {
    return match ($severity) {
        'high' => 'danger',
        'medium' => 'warning',
        'low' => 'success',
        default => 'secondary'
    };
}
?>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Signal Quality Chart
    const signalData = {
        labels: ['Excellent', 'Good', 'Fair', 'Poor'],
        datasets: [{
            data: [
                <?= count(array_filter($issues, fn($i) => strpos($i['issue'], 'signal') !== false && $i['severity'] === 'low')) ?>,
                <?= count(array_filter($issues, fn($i) => strpos($i['issue'], 'signal') !== false && $i['severity'] === 'medium')) ?>,
                <?= count(array_filter($issues, fn($i) => strpos($i['issue'], 'signal') !== false && $i['severity'] === 'high')) ?>,
                <?= count(array_filter($issues, fn($i) => strpos($i['issue'], 'signal') !== false && $i['severity'] === 'critical')) ?>
            ],
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
        }]
    };

    new Chart(document.getElementById('signalQualityChart'), {
        type: 'pie',
        data: signalData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Issues by Device Type Chart
    const issuesData = {
        labels: ['OLT', 'ONU', 'NAP', 'LCP'],
        datasets: [{
            label: 'Critical',
            data: [
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'olt' && $i['severity'] === 'high')) ?>,
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'onu' && $i['severity'] === 'high')) ?>,
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'nap' && $i['severity'] === 'high')) ?>,
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'lcp' && $i['severity'] === 'high')) ?>
            ],
            backgroundColor: '#dc3545'
        }, {
            label: 'Warning',
            data: [
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'olt' && $i['severity'] === 'medium')) ?>,
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'onu' && $i['severity'] === 'medium')) ?>,
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'nap' && $i['severity'] === 'medium')) ?>,
                <?= count(array_filter($issues, fn($i) => $i['type'] === 'lcp' && $i['severity'] === 'medium')) ?>
            ],
            backgroundColor: '#ffc107'
        }]
    };

    new Chart(document.getElementById('issuesChart'), {
        type: 'bar',
        data: issuesData,
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true
                }
            }
        }
    });

    // Issue Details Modal Handler
    const issueDetailsModal = new bootstrap.Modal(document.getElementById('issueDetailsModal'));
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const issue = JSON.parse(this.dataset.issue);
            document.getElementById('deviceName').textContent = issue.device;
            document.getElementById('deviceType').textContent = issue.type.toUpperCase();
            document.getElementById('deviceLocation').textContent = issue.location || 'N/A';
            document.getElementById('issueSeverity').textContent = issue.severity;
            document.getElementById('issueDetected').textContent = new Date(issue.detected_at).toLocaleString();
            document.getElementById('issueStatus').textContent = issue.resolved ? 'Resolved' : 'Pending';
            document.getElementById('issueDescription').textContent = issue.issue;
            document.getElementById('diagnosticInfo').textContent = issue.diagnostic_info || 'No diagnostic information available';
            issueDetailsModal.show();
        });
    });

    // Resolve Issue Handler
    document.querySelectorAll('.resolve-issue').forEach(button => {
        button.addEventListener('click', function() {
            const issueId = this.dataset.id;
            if (confirm('Are you sure you want to mark this issue as resolved?')) {
                fetch(`/admin/network/issues/${issueId}/resolve`, {
                    method: 'POST',
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
                        alert(data.error || 'Failed to resolve issue');
                    }
                });
            }
        });
    });

    // Run Diagnostics Handler
    document.getElementById('runDiagnostics').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Running Diagnostics...';

        fetch('/admin/network/run-diagnostics', {
            method: 'POST',
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
                alert(data.error || 'Failed to run diagnostics');
                this.disabled = false;
                this.innerHTML = '<i class="fa fa-stethoscope"></i> Run Diagnostics';
            }
        });
    });

    // Refresh Status Handler
    document.getElementById('refreshHealth').addEventListener('click', function() {
        location.reload();
    });
});
</script>
