<?php
$title = htmlspecialchars($olt['name']) . ' - OLT Details';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><?= htmlspecialchars($olt['name']) ?></h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/network/olts/<?= $olt['id'] ?>/edit" class="btn btn-primary">
                <i class="fa fa-edit"></i> Edit OLT
            </a>
            <button type="button" class="btn btn-warning check-connectivity">
                <i class="fa fa-network-wired"></i> Check Connectivity
            </button>
            <a href="/admin/network/olts" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to OLTs
            </a>
        </div>
    </div>
</div>

<!-- Status Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">PON Ports</h5>
                <h3 class="card-text"><?= $stats['total_ports'] ?></h3>
                <p class="mb-0">Total Ports</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Active ONUs</h5>
                <h3 class="card-text"><?= $stats['active_onus'] ?></h3>
                <p class="mb-0">of <?= $stats['total_onus'] ?> Total ONUs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Signal Level</h5>
                <h3 class="card-text"><?= number_format($stats['avg_rx_power'], 2) ?> dBm</h3>
                <p class="mb-0">Average RX Power</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-<?= $olt['status'] === 'active' ? 'success' : 'warning' ?> text-white">
            <div class="card-body">
                <h5 class="card-title">Status</h5>
                <h3 class="card-text"><?= ucfirst(htmlspecialchars($olt['status'])) ?></h3>
                <p class="mb-0">Current State</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- OLT Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">OLT Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Model</th>
                        <td><?= htmlspecialchars($olt['model']) ?></td>
                    </tr>
                    <tr>
                        <th>Serial Number</th>
                        <td><?= htmlspecialchars($olt['serial_number']) ?></td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td>
                            <?= htmlspecialchars($olt['ip_address']) ?>
                            <?php if ($olt['management_vlan']): ?>
                                <span class="badge bg-secondary">VLAN <?= $olt['management_vlan'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td><?= htmlspecialchars($olt['location']) ?></td>
                    </tr>
                    <tr>
                        <th>Uplink Capacity</th>
                        <td><?= formatBandwidth($olt['uplink_capacity']) ?></td>
                    </tr>
                    <tr>
                        <th>Firmware Version</th>
                        <td><?= htmlspecialchars($olt['firmware_version']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Signal Statistics -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Signal Statistics</h5>
            </div>
            <div class="card-body">
                <canvas id="signalChart"></canvas>
            </div>
        </div>
    </div>

    <!-- PON Ports -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">PON Ports</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPortModal">
                    <i class="fa fa-plus"></i> Add Port
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Port</th>
                                <th>Connected ONUs</th>
                                <th>Signal Levels</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ponPorts as $port): ?>
                                <tr>
                                    <td><?= $port['port_number'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <?= $port['connected_onus'] ?> / 64
                                            </div>
                                            <div class="progress flex-grow-1" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?= ($port['connected_onus'] / 64) * 100 ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($port['onu_serials'])): ?>
                                            <button type="button" class="btn btn-sm btn-info view-signals" 
                                                    data-port="<?= $port['port_number'] ?>"
                                                    data-onus="<?= htmlspecialchars($port['onu_serials']) ?>">
                                                <i class="fa fa-chart-line"></i> View Signals
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">No ONUs</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($port['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($port['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/admin/network/olts/<?= $olt['id'] ?>/ports/<?= $port['port_number'] ?>" 
                                               class="btn btn-info" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-warning configure-port" 
                                                    data-port="<?= $port['port_number'] ?>" title="Configure">
                                                <i class="fa fa-cog"></i>
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

<!-- Connected ONUs -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Connected ONUs</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Serial Number</th>
                        <th>Customer</th>
                        <th>PON Port</th>
                        <th>RX Power</th>
                        <th>TX Power</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connectedONUs as $onu): ?>
                        <tr>
                            <td><?= htmlspecialchars($onu['serial_number']) ?></td>
                            <td>
                                <?php if ($onu['customer_id']): ?>
                                    <a href="/admin/customers/<?= $onu['customer_id'] ?>">
                                        <?= htmlspecialchars($onu['customer_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $onu['port_number'] ?></td>
                            <td>
                                <span class="badge bg-<?= getSignalBadgeClass($onu['rx_power']) ?>">
                                    <?= number_format($onu['rx_power'], 2) ?> dBm
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= getSignalBadgeClass($onu['tx_power']) ?>">
                                    <?= number_format($onu['tx_power'], 2) ?> dBm
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeClass($onu['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($onu['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/admin/network/onus/<?= $onu['id'] ?>" class="btn btn-info" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-warning configure-onu" 
                                            data-id="<?= $onu['id'] ?>" title="Configure">
                                        <i class="fa fa-cog"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger reboot-onu" 
                                            data-id="<?= $onu['id'] ?>" title="Reboot">
                                        <i class="fa fa-power-off"></i>
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

<!-- Add Port Modal -->
<div class="modal fade" id="addPortModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add PON Port</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPortForm">
                    <div class="mb-3">
                        <label for="portNumber" class="form-label">Port Number *</label>
                        <input type="number" class="form-control" id="portNumber" required min="1">
                    </div>
                    <div class="mb-3">
                        <label for="portStatus" class="form-label">Status</label>
                        <select class="form-select" id="portStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="portNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="portNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePort">Add Port</button>
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

function getSignalBadgeClass($power) {
    if ($power >= -15 && $power <= -8) return 'success';
    if ($power >= -28 && $power < -15) return 'warning';
    return 'danger';
}

function formatBandwidth($bw) {
    if ($bw >= 1000) {
        return ($bw / 1000) . ' Gbps';
    }
    return $bw . ' Mbps';
}
?>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Signal Distribution Chart
    const signalData = <?= json_encode([
        'labels' => array_map(fn($onu) => $onu['serial_number'], $connectedONUs),
        'rx_power' => array_map(fn($onu) => $onu['rx_power'], $connectedONUs),
        'tx_power' => array_map(fn($onu) => $onu['tx_power'], $connectedONUs)
    ]) ?>;

    new Chart(document.getElementById('signalChart'), {
        type: 'line',
        data: {
            labels: signalData.labels,
            datasets: [{
                label: 'RX Power (dBm)',
                data: signalData.rx_power,
                borderColor: '#17a2b8',
                fill: false
            }, {
                label: 'TX Power (dBm)',
                data: signalData.tx_power,
                borderColor: '#28a745',
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });

    // Add Port Handler
    document.getElementById('savePort').addEventListener('click', function() {
        const form = document.getElementById('addPortForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            port_number: document.getElementById('portNumber').value,
            status: document.getElementById('portStatus').value,
            notes: document.getElementById('portNotes').value
        };

        fetch(`/admin/network/olts/${<?= $olt['id'] ?>}/ports`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to add port');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the port');
        });
    });

    // Connectivity Check Handler
    document.querySelector('.check-connectivity').addEventListener('click', function() {
        const button = this;
        const icon = button.querySelector('i');
        
        icon.className = 'fa fa-spinner fa-spin';
        button.disabled = true;

        fetch(`/admin/network/olts/${<?= $olt['id'] ?>}/check-connectivity`)
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
                button.disabled = false;
            });
    });

    // ONU Reboot Handler
    document.querySelectorAll('.reboot-onu').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to reboot this ONU?')) {
                const onuId = this.dataset.id;
                const icon = this.querySelector('i');
                
                icon.className = 'fa fa-spinner fa-spin';
                this.disabled = true;

                fetch(`/admin/network/onus/${onuId}/reboot`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('ONU reboot initiated');
                        setTimeout(() => location.reload(), 5000);
                    } else {
                        alert('Failed to reboot ONU: ' + data.error);
                        icon.className = 'fa fa-power-off';
                    }
                })
                .catch(error => {
                    alert('Error rebooting ONU');
                    icon.className = 'fa fa-power-off';
                })
                .finally(() => {
                    this.disabled = false;
                });
            }
        });
    });
});
</script>
