<?php
require_once '../config.php';
check_auth();

$page_title = 'FTTH Loss Charts';
$_SESSION['active_menu'] = 'pon_management';
include 'header.php';
include 'navbar.php';
?>

<style>
.loss-chart-table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
}

.loss-chart-table th,
.loss-chart-table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.loss-chart-table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    cursor: pointer;
}

.loss-chart-table thead th:hover {
    background-color: #e9ecef;
}

.loss-chart-table tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.05);
}

.chart-container {
    margin-bottom: 2rem;
}

.chart-title {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
    color: #2c3e50;
    font-weight: 600;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.btn-group .btn i {
    font-size: 0.875rem;
}
</style>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">FTTH Loss Charts</h1>
        <a href="FTTH.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to FTTH Management
        </a>
    </div>
    
    <div class="row">
        <!-- FBT Loss Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="chart-title mb-0">FBT Loss Chart</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFbtModal">
                            <i class="bx bx-plus"></i> Add New
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table loss-chart-table" id="fbtTable">
                            <thead>
                                <tr>
                                    <th onclick="sortTable('fbtTable', 0)">Value (%)</th>
                                    <th onclick="sortTable('fbtTable', 1)">Loss (dB)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="fbtTableBody">
                                <!-- Will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PLC Loss Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="chart-title mb-0">PLC Loss Chart</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPlcModal">
                            <i class="bx bx-plus"></i> Add New
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table loss-chart-table" id="plcTable">
                            <thead>
                                <tr>
                                    <th onclick="sortTable('plcTable', 0)">Ports</th>
                                    <th onclick="sortTable('plcTable', 1)">Loss (dB)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="plcTableBody">
                                <!-- Will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add FBT Modal -->
<div class="modal fade" id="addFbtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New FBT Loss Value</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addFbtForm">
                    <input type="hidden" name="type" value="fbt">
                    <div class="mb-3">
                        <label for="addFbtValue" class="form-label">Value (%)</label>
                        <input type="number" class="form-control" id="addFbtValue" name="value" required step="0.01" min="0" max="100">
                        <small class="text-muted">Common values: 10% (10.5 dB), 20% (7.5 dB), 30% (5.5 dB), 40% (4.5 dB), 50% (3.5 dB)</small>
                    </div>
                    <div class="mb-3">
                        <label for="addFbtLoss" class="form-label">Loss (dB)</label>
                        <input type="number" class="form-control" id="addFbtLoss" name="loss" required step="0.01" min="0">
                        <small class="text-muted">Enter the loss value in decibels</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Value</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add PLC Modal -->
<div class="modal fade" id="addPlcModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New PLC Loss Value</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPlcForm">
                    <input type="hidden" name="type" value="plc">
                    <div class="mb-3">
                        <label for="addPlcPorts" class="form-label">Number of Ports</label>
                        <input type="number" class="form-control" id="addPlcPorts" name="ports" required min="1">
                        <small class="text-muted">
                            Common values:<br>
                            1:2 (2 ports) ≈ 3.0 dB<br>
                            1:4 (4 ports) ≈ 7.0 dB<br>
                            1:8 (8 ports) ≈ 10.5 dB<br>
                            1:16 (16 ports) ≈ 14.0 dB<br>
                            1:32 (32 ports) ≈ 17.5 dB<br>
                            1:64 (64 ports) ≈ 21.0 dB<br>
                            1:128 (128 ports) ≈ 25.0 dB
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="addPlcLoss" class="form-label">Loss (dB)</label>
                        <input type="number" class="form-control" id="addPlcLoss" name="loss" required step="0.01" min="0">
                        <small class="text-muted">Enter the loss value in decibels</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Value</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit FBT Modal -->
<div class="modal fade" id="editFbtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit FBT Loss Value</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editFbtForm" method="POST" action="ftth_edit_loss_chart.php">
                    <input type="hidden" name="type" value="fbt">
                    <input type="hidden" name="id" id="editFbtId">
                    <div class="mb-3">
                        <label for="editFbtValue" class="form-label">Value (%)</label>
                        <input type="number" class="form-control" id="editFbtValue" name="value" required step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="editFbtLoss" class="form-label">Loss (dB)</label>
                        <input type="number" class="form-control" id="editFbtLoss" name="loss" required step="0.01">
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit PLC Modal -->
<div class="modal fade" id="editPlcModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit PLC Loss Value</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPlcForm" method="POST" action="ftth_edit_loss_chart.php">
                    <input type="hidden" name="type" value="plc">
                    <input type="hidden" name="id" id="editPlcId">
                    <div class="mb-3">
                        <label for="editPlcPorts" class="form-label">Ports</label>
                        <input type="number" class="form-control" id="editPlcPorts" name="ports" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPlcLoss" class="form-label">Loss (dB)</label>
                        <input type="number" class="form-control" id="editPlcLoss" name="loss" required step="0.01">
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Function to show alerts
function showAlert(message, type = 'success') {
    const alertsContainer = document.querySelector('.alerts');
    if (alertsContainer) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertsContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }
}

// Function to load loss chart data
async function loadLossCharts() {
    try {
        const response = await fetch('ftth_get_loss_charts.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load loss charts');
        }

        // Populate FBT table
        const fbtBody = document.getElementById('fbtTableBody');
        fbtBody.innerHTML = '';
        
        if (data.data.fbt && data.data.fbt.length > 0) {
            data.data.fbt.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${parseFloat(row.value).toFixed(2)}</td>
                    <td>${parseFloat(row.loss).toFixed(2)}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info me-1" onclick="editFbt(${row.id}, ${row.value}, ${row.loss})" title="Edit">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteLossValue('fbt', ${row.id}, '${row.value}%')" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                fbtBody.appendChild(tr);
            });
        } else {
            fbtBody.innerHTML = '<tr><td colspan="3" class="text-center">No FBT loss values found</td></tr>';
        }

        // Populate PLC table
        const plcBody = document.getElementById('plcTableBody');
        plcBody.innerHTML = '';
        
        if (data.data.plc && data.data.plc.length > 0) {
            data.data.plc.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.ports}</td>
                    <td>${parseFloat(row.loss).toFixed(2)}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info me-1" onclick="editPlc(${row.id}, ${row.ports}, ${row.loss})" title="Edit">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteLossValue('plc', ${row.id}, '${row.ports} ports')" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                plcBody.appendChild(tr);
            });
        } else {
            plcBody.innerHTML = '<tr><td colspan="3" class="text-center">No PLC loss values found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading loss charts:', error);
        showAlert('Error loading loss charts: ' + error.message, 'danger');
    }
}

// Function to edit FBT value
function editFbt(id, value, loss) {
    document.getElementById('editFbtId').value = id;
    document.getElementById('editFbtValue').value = parseFloat(value).toFixed(2);
    document.getElementById('editFbtLoss').value = parseFloat(loss).toFixed(2);
    new bootstrap.Modal(document.getElementById('editFbtModal')).show();
}

// Function to edit PLC value
function editPlc(id, ports, loss) {
    document.getElementById('editPlcId').value = id;
    document.getElementById('editPlcPorts').value = ports;
    document.getElementById('editPlcLoss').value = parseFloat(loss).toFixed(2);
    new bootstrap.Modal(document.getElementById('editPlcModal')).show();
}

// Function to sort table
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const th = table.querySelectorAll('th')[columnIndex];
    
    // Skip if no data or only "No values found" row
    if (rows.length <= 1 && rows[0].cells.length === 1) {
        return;
    }
    
    // Toggle sort direction
    const isAscending = !th.classList.contains('sort-asc');
    
    // Remove sort classes from all headers
    table.querySelectorAll('th').forEach(header => {
        header.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add sort class to current header
    th.classList.add(isAscending ? 'sort-asc' : 'sort-desc');

    // Sort rows
    rows.sort((a, b) => {
        const aValue = parseFloat(a.cells[columnIndex].textContent);
        const bValue = parseFloat(b.cells[columnIndex].textContent);
        return isAscending ? aValue - bValue : bValue - aValue;
    });

    // Reorder rows in the table
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

// Function to delete loss value
async function deleteLossValue(type, id, description) {
    if (!confirm(`Are you sure you want to delete the ${type.toUpperCase()} loss value for ${description}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('type', type);
        formData.append('id', id);

        const response = await fetch('ftth_delete_loss_value.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(`${type.toUpperCase()} loss value deleted successfully`);
            loadLossCharts();
        } else {
            throw new Error(data.error || `Failed to delete ${type.toUpperCase()} loss value`);
        }
    } catch (error) {
        console.error(`Error deleting ${type} loss value:`, error);
        showAlert(error.message, 'danger');
    }
}

// Handle form submissions
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadLossCharts();

    // Handle Add FBT form submission
    const addFbtForm = document.getElementById('addFbtForm');
    addFbtForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('ftth_add_loss_value.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('FBT loss value added successfully');
                bootstrap.Modal.getInstance(document.getElementById('addFbtModal')).hide();
                this.reset();
                loadLossCharts();
            } else {
                throw new Error(data.error || 'Failed to add FBT loss value');
            }
        } catch (error) {
            console.error('Error adding FBT loss value:', error);
            showAlert(error.message, 'danger');
        }
    });

    // Handle Add PLC form submission
    const addPlcForm = document.getElementById('addPlcForm');
    addPlcForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('ftth_add_loss_value.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('PLC loss value added successfully');
                bootstrap.Modal.getInstance(document.getElementById('addPlcModal')).hide();
                this.reset();
                loadLossCharts();
            } else {
                throw new Error(data.error || 'Failed to add PLC loss value');
            }
        } catch (error) {
            console.error('Error adding PLC loss value:', error);
            showAlert(error.message, 'danger');
        }
    });

    // Handle Edit FBT form submission
    const editFbtForm = document.getElementById('editFbtForm');
    editFbtForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('ftth_edit_loss_chart.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('FBT loss value updated successfully');
                bootstrap.Modal.getInstance(document.getElementById('editFbtModal')).hide();
                loadLossCharts();
            } else {
                throw new Error(data.error || 'Failed to update FBT loss value');
            }
        } catch (error) {
            console.error('Error updating FBT loss value:', error);
            showAlert(error.message, 'danger');
        }
    });

    // Handle PLC form submission
    const editPlcForm = document.getElementById('editPlcForm');
    editPlcForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('ftth_edit_loss_chart.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('PLC loss value updated successfully');
                bootstrap.Modal.getInstance(document.getElementById('editPlcModal')).hide();
                loadLossCharts();
            } else {
                throw new Error(data.error || 'Failed to update PLC loss value');
            }
        } catch (error) {
            console.error('Error updating PLC loss value:', error);
            showAlert(error.message, 'danger');
        }
    });
});
</script>

<?php include 'footer.php'; ?>
