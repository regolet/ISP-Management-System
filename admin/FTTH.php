<?php
require_once '../config.php';
check_auth();

$page_title = 'FTTH Management';
$_SESSION['active_menu'] = 'pon_management';
include 'header.php';
include 'navbar.php';

// Constants for power calculations
define('EPON_MAX_CLIENTS', 64);
define('GPON_MAX_CLIENTS', 128);
define('CONNECTOR_LOSS', [
    'LCP' => 0.3, // dB loss per connection
    'PLC' => 0.2,
    'FBT' => 0.4
]);
define('FIBER_LOSS_PER_KM', 0.35); // dB/km for 1310nm
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <h1 class="h2 mb-4">FTTH Management</h1>
    
    <!-- FTTH Management Content -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <ul class="nav nav-tabs" id="ftthTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="olt-tab" data-bs-toggle="tab" href="#olt" role="tab">OLT Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ftth_lcp.php">LCP Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ftth_napbox.php">NAP Box Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ftth_losschart.php">Loss Charts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="power-calc-tab" data-bs-toggle="tab" href="#power-calc" role="tab">Power Calculator</a>
                    </li>
                </ul>
                <a href="ftth_topology.php" class="btn btn-primary">
                    <i class="bx bx-network-chart"></i> View Network Topology
                </a>
            </div>

            <div class="tab-content" id="ftthTabContent">
                <!-- OLT Management Tab -->
                <div class="tab-pane fade show active" id="olt" role="tabpanel">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">OLT List</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOltModal">
                                        <i class="bx bx-plus"></i> Add New OLT
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>PON Type</th>
                                                    <th>Number of PONs</th>
                                                    <th>Tx Power (dBm)</th>
                                                    <th>Used Ports</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="oltTableBody">
                                                <!-- Will be populated via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Power Calculator Tab -->
                <?php include 'power_calculator.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add OLT Modal -->
<div class="modal fade" id="addOltModal" tabindex="-1" aria-labelledby="addOltModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOltModalLabel">Add New OLT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addOltForm" method="POST" action="ftth_add_olt.php">
                    <div class="mb-3">
                        <label for="oltName" class="form-label">OLT Name</label>
                        <input type="text" class="form-control" id="oltName" name="oltName" required>
                    </div>
                    <div class="mb-3">
                        <label for="ponType" class="form-label">PON Type</label>
                        <select class="form-select" id="ponType" name="ponType" required>
                            <option value="EPON">EPON</option>
                            <option value="GPON">GPON</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="numberOfPons" class="form-label">Number of PONs</label>
                        <input type="number" class="form-control" id="numberOfPons" name="numberOfPons" required>
                    </div>
                    <div class="mb-3">
                        <label for="txPower" class="form-label">Tx Power (dBm)</label>
                        <input type="number" step="0.1" class="form-control" id="txPower" name="txPower" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add OLT</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit OLT Modal -->
<div class="modal fade" id="editOltModal" tabindex="-1" aria-labelledby="editOltModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOltModalLabel">Edit OLT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editOltForm" method="POST" action="ftth_edit_olt.php">
                    <input type="hidden" id="editOltId" name="oltId">
                    <div class="mb-3">
                        <label for="editOltName" class="form-label">OLT Name</label>
                        <input type="text" class="form-control" id="editOltName" name="oltName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPonType" class="form-label">PON Type</label>
                        <select class="form-select" id="editPonType" name="ponType" required>
                            <option value="EPON">EPON</option>
                            <option value="GPON">GPON</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editNumberOfPons" class="form-label">Number of PONs</label>
                        <input type="number" class="form-control" id="editNumberOfPons" name="numberOfPons" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTxPower" class="form-label">Tx Power (dBm)</label>
                        <input type="number" step="0.1" class="form-control" id="editTxPower" name="txPower" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update OLT</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Function to load OLT list
function loadOLTList() {
    fetch('ftth_get_olt_list.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load OLTs');
            }

            const tbody = document.getElementById('oltTableBody');
            tbody.innerHTML = '';
            
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No OLTs found</td></tr>';
                return;
            }
            
            data.data.forEach(olt => {
                const maxClients = olt.pon_type === 'EPON' ? 64 : 128;
                const row = `
                    <tr>
                        <td>${olt.name}</td>
                        <td>${olt.pon_type}</td>
                        <td>${olt.number_of_pons}</td>
                        <td>${olt.tx_power}</td>
                        <td>
                            ${olt.used_pon_ports || 0}/${olt.number_of_pons} PONs<br>
                            ${olt.total_client_ports || 0}/${olt.number_of_pons * maxClients} Ports
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-info" title="Edit OLT" 
                                        onclick="populateEditModal(${olt.id}, '${olt.name}', '${olt.pon_type}', ${olt.number_of_pons}, ${olt.tx_power});"
                                        data-bs-toggle="modal" data-bs-target="#editOltModal">
                                    <i class="bx bx-edit"></i>
                                </button>
                                <a href="ftth_delete_olt.php?id=${olt.id}" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete OLT"
                                   onclick="return confirm('Are you sure you want to delete this OLT?');">
                                    <i class="bx bx-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        })
        .catch(error => {
            console.error('Error loading OLT list:', error);
            const tbody = document.getElementById('oltTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading OLT list</td></tr>';
        });
}

function populateEditModal(id, name, ponType, numberOfPons, txPower) {
    document.getElementById('editOltId').value = id;
    document.getElementById('editOltName').value = name;
    document.getElementById('editPonType').value = ponType;
    document.getElementById('editNumberOfPons').value = numberOfPons;
    document.getElementById('editTxPower').value = txPower;
}

// Load OLT list on page load
document.addEventListener('DOMContentLoaded', loadOLTList);
</script>

<?php include 'footer.php'; ?>
