<?php
$title = 'Network Management - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Network Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/network/olts/create" class="btn btn-primary">
                <i class="fa fa-plus"></i> Add OLT
            </a>
            <a href="/admin/network/map" class="btn btn-info">
                <i class="fa fa-map"></i> Network Map
            </a>
            <a href="/admin/network/health" class="btn btn-warning">
                <i class="fa fa-heartbeat"></i> Health Check
            </a>
        </div>
    </div>
</div>

<!-- Network Overview Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total OLTs</h5>
                <h3 class="card-text"><?= count($olts) ?></h3>
                <p class="mb-0">Active Network Nodes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Active ONUs</h5>
                <h3 class="card-text"><?= $activeONUs ?></h3>
                <p class="mb-0">of <?= $totalONUs ?> Total ONUs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Signal Quality</h5>
                <h3 class="card-text">
                    <?php
                    $goodSignals = 0;
                    $totalSignals = 0;
                    foreach ($oltStats as $stats) {
                        if (isset($stats['active_onus'])) {
                            $totalSignals += $stats['active_onus'];
                            // Assuming good signal is within acceptable range
                            if (isset($stats['avg_rx_power']) && $stats['avg_rx_power'] >= -28 && $stats['avg_rx_power'] <= -8) {
                                $goodSignals += $stats['active_onus'];
                            }
                        }
                    }
                    echo $totalSignals > 0 ? round(($goodSignals / $totalSignals) * 100) : 0;
                    ?>%
                </h3>
                <p class="mb-0">Good Signal Strength</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Port Utilization</h5>
                <h3 class="card-text">
                    <?php
                    $totalPorts = 0;
                    $usedPorts = 0;
                    foreach ($oltStats as $stats) {
                        if (isset($stats['total_ports'])) {
                            $totalPorts += $stats['total_ports'];
                            $usedPorts += $stats['total_onus'];
                        }
                    }
                    echo $totalPorts > 0 ? round(($usedPorts / $totalPorts) * 100) : 0;
                    ?>%
                </h3>
                <p class="mb-0">Ports In Use</p>
            </div>
        </div>
    </div>
</div>

<!-- OLT Status -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">OLT Status</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>OLT Name</th>
                        <th>Location</th>
                        <th>PON Ports</th>
                        <th>Connected ONUs</th>
                        <th>Signal Levels</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($olts as $olt): ?>
                        <tr>
                            <td>
                                <a href="/admin/network/olts/<?= $olt['id'] ?>">
                                    <?= htmlspecialchars($olt['name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($olt['location']) ?></td>
                            <td>
                                <?php
                                $stats = $oltStats[$olt['id']];
                                echo "{$stats['total_ports']} Total";
                                ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <?= $stats['active_onus'] ?> Active
                                    </div>
                                    <div class="progress flex-grow-1" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= ($stats['active_onus'] / $stats['total_onus']) * 100 ?>%">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $avgRxPower = $stats['avg_rx_power'];
                                $signalClass = '';
                                if ($avgRxPower >= -28 && $avgRxPower <= -8) {
                                    $signalClass = 'success';
                                } elseif ($avgRxPower > -8) {
                                    $signalClass = 'warning';
                                } else {
                                    $signalClass = 'danger';
                                }
                                ?>
                                <span class="badge bg-<?= $signalClass ?>">
                                    <?= number_format($avgRxPower, 2) ?> dBm
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $olt['status'] === 'active' ? 'success' : 'danger' ?>">
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
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                <h5 class="mb-0">Port Utilization by OLT</h5>
            </div>
            <div class="card-body">
                <canvas id="portUtilizationChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Alerts -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Network Alerts</h5>
        <a href="/admin/network/alerts" class="btn btn-sm btn-primary">View All Alerts</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Device</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alerts ?? [])): ?>
                        <tr>
                            <td colspan="5" class="text-center">No recent alerts</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($alert['created_at'])) ?></td>
                                <td><?= htmlspecialchars($alert['device_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getAlertTypeClass($alert['type']) ?>">
                                        <?= ucfirst(htmlspecialchars($alert['type'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($alert['message']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $alert['resolved'] ? 'success' : 'warning' ?>">
                                        <?= $alert['resolved'] ? 'Resolved' : 'Pending' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
function getAlertTypeClass($type) {
    return match ($type) {
        'critical' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'secondary'
    };
}
?>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Signal Quality Chart
    const signalData = <?= json_encode(array_map(function($stats) {
        return [
            'excellent' => count(array_filter($stats['rx_powers'] ?? [], fn($p) => $p >= -15 && $p <= -8)),
            'good' => count(array_filter($stats['rx_powers'] ?? [], fn($p) => $p >= -22 && $p < -15)),
            'fair' => count(array_filter($stats['rx_powers'] ?? [], fn($p) => $p >= -28 && $p < -22)),
            'poor' => count(array_filter($stats['rx_powers'] ?? [], fn($p) => $p < -28))
        ];
    }, $oltStats)) ?>;

    new Chart(document.getElementById('signalQualityChart'), {
        type: 'pie',
        data: {
            labels: ['Excellent', 'Good', 'Fair', 'Poor'],
            datasets: [{
                data: [
                    array_sum(array_column($signalData, 'excellent')),
                    array_sum(array_column($signalData, 'good')),
                    array_sum(array_column($signalData, 'fair')),
                    array_sum(array_column($signalData, 'poor'))
                ],
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Port Utilization Chart
    const portData = <?= json_encode(array_map(function($olt) use ($oltStats) {
        $stats = $oltStats[$olt['id']];
        return [
            'name' => $olt['name'],
            'used' => $stats['total_onus'],
            'total' => $stats['total_ports']
        ];
    }, $olts)) ?>;

    new Chart(document.getElementById('portUtilizationChart'), {
        type: 'bar',
        data: {
            labels: portData.map(d => d.name),
            datasets: [{
                label: 'Used Ports',
                data: portData.map(d => d.used),
                backgroundColor: '#007bff'
            }, {
                label: 'Total Ports',
                data: portData.map(d => d.total),
                backgroundColor: '#6c757d'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Connectivity Check
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
