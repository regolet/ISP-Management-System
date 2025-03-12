<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/OltController.php';
require_once dirname(__DIR__) . '/views/layouts/sidebar.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize OLT Controller
$oltController = new \App\Controllers\OltController($db);

// Get page parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'ASC';

// Get OLT data
$oltData = $oltController->getOltDevices([
    'page' => $page,
    'per_page' => 10,
    'search' => $search,
    'status' => $status,
    'sort' => $sort,
    'order' => $order
]);

// Get OLT statistics
$stats = $oltController->getOltStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLT Management - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">

    <style>
        .olt-status {
            width: 10px;
            height: 10px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-active { background-color: #28a745; }
        .status-maintenance { background-color: #ffc107; }
        .status-offline { background-color: #dc3545; }
        .search-box {
            position: relative;
        }
        .search-box .clear-search {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .toast {
            min-width: 300px;
        }
        .port-container {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 5px;
            margin-bottom: 15px;
        }
        .port-item {
            text-align: center;
            padding: 5px;
            border-radius: 4px;
            font-size: 0.8em;
            cursor: pointer;
        }
        .port-active {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .port-inactive {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .port-fault {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .port-reserved {
            background-color: #e2e3e5;
            border: 1px solid #d6d8db;
        }
        .stats-card {
            border-left: 4px solid;
            border-radius: 4px;
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
        }
        .stats-card-primary { border-left-color: #4e73df; }
        .stats-card-success { border-left-color: #1cc88a; }
        .stats-card-warning { border-left-color: #f6c23e; }
        .stats-card-danger { border-left-color: #e74a3b; }
        .stats-card h5 {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        .stats-card h3 {
            font-size: 1.5rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>
    <!-- Render Sidebar -->
    <?php renderSidebar('olt'); /* Using the function from helpers.php */ ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">OLT Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#oltModal">
                    <i class="fas fa-plus me-2"></i>Add New OLT
                </button>
            </div>

            <!-- Statistics Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-primary">
                        <h5>Total OLT Devices</h5>
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-success">
                        <h5>Active OLTs</h5>
                        <h3><?php echo $stats['active'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-warning">
                        <h5>Maintenance</h5>
                        <h3><?php echo $stats['maintenance'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-danger">
                        <h5>Offline OLTs</h5>
                        <h3><?php echo $stats['offline'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search OLT devices..." value="<?php echo htmlspecialchars($search); ?>">
                                <?php if (!empty($search)): ?>
                                    <span class="clear-search" onclick="clearSearch()">×</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="offline" <?php echo $status === 'offline' ? 'selected' : ''; ?>>Offline</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="sort" onchange="this.form.submit()">
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="ip_address" <?php echo $sort === 'ip_address' ? 'selected' : ''; ?>>Sort by IP</option>
                                <option value="last_sync" <?php echo $sort === 'last_sync' ? 'selected' : ''; ?>>Sort by Last Sync</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- OLT Devices Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Model</th>
                                    <th>IP Address</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Ports</th>
                                    <th>Last Sync</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($oltData['data'] as $olt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($olt['name']); ?></td>
                                        <td><?php echo htmlspecialchars($olt['model']); ?></td>
                                        <td><?php echo htmlspecialchars($olt['ip_address']); ?></td>
                                        <td><?php echo htmlspecialchars($olt['location'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="olt-status status-<?php echo $olt['status']; ?>"></span>
                                            <?php echo ucfirst($olt['status']); ?>
                                        </td>
                                        <td>
                                            <?php echo $olt['used_ports']; ?> / <?php echo $olt['total_ports']; ?>
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo ($olt['total_ports'] > 0) ? ($olt['used_ports'] / $olt['total_ports'] * 100) : 0; ?>%;" 
                                                     aria-valuenow="<?php echo $olt['used_ports']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="<?php echo $olt['total_ports']; ?>">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($olt['last_sync']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($olt['last_sync'])); ?>
                                            <?php else: ?>
                                                Never
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewOlt(<?php echo $olt['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick="editOlt(<?php echo $olt['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="syncOlt(<?php echo $olt['id']; ?>)">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteOlt(<?php echo $olt['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($oltData['data'])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No OLT devices found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($oltData['total_pages'] > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $oltData['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit OLT Modal -->
    <div class="modal fade" id="oltModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New OLT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="oltForm">
                        <input type="hidden" id="oltId" name="id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IP Address</label>
                                <input type="text" class="form-control" id="ipAddress" name="ip_address" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location">
                            </div>
                            <!-- Username and Password fields removed -->
                            <div class="col-md-6">
                                <label class="form-label">Total Ports</label>
                                <input type="number" class="form-control" id="totalPorts" name="total_ports" required min="1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Firmware Version</label>
                                <input type="text" class="form-control" id="firmwareVersion" name="firmware_version">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveOlt()">Save OLT</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View OLT Modal -->
    <div class="modal fade" id="viewOltModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">OLT Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="oltDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ports-tab" data-bs-toggle="tab" data-bs-target="#ports" type="button" role="tab">Ports</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">Logs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="diagnostics-tab" data-bs-toggle="tab" data-bs-target="#diagnostics" type="button" role="tab">Diagnostics</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="oltTabContent">
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div id="oltOverview"></div>
                        </div>
                        <div class="tab-pane fade" id="ports" role="tabpanel">
                            <div id="oltPorts"></div>
                        </div>
                        <div class="tab-pane fade" id="logs" role="tabpanel">
                            <div id="oltLogs"></div>
                        </div>
                        <div class="tab-pane fade" id="diagnostics" role="tabpanel">
                            <div id="oltDiagnostics"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Port Modal -->
    <div class="modal fade" id="portModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Port</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="portForm">
                        <input type="hidden" id="portId" name="id">
                        <input type="hidden" id="oltIdForPort" name="olt_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Port Number</label>
                                <input type="text" class="form-control" id="portNumber" name="port_number" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Port Type</label>
                                <input type="text" class="form-control" id="portType" name="port_type" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="portStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="fault">Fault</option>
                                    <option value="reserved">Reserved</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Signal Strength (dBm)</label>
                                <input type="number" class="form-control" id="signalStrength" name="signal_strength" step="0.01">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Client Subscription</label>
                                <select class="form-select" id="clientSubscription" name="client_subscription_id">
                                    <option value="">None</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="portDescription" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePort()">Save Port</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete OLT Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this OLT device? This action cannot be undone.</p>
                    <p class="text-warning">Note: OLT devices with active client connections cannot be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/sidebar.js"></script>

    <script>
        // Initialize global variables
        let deleteOltId = null;
        const oltModal = new bootstrap.Modal(document.getElementById('oltModal'));
        const viewOltModal = new bootstrap.Modal(document.getElementById('viewOltModal'));
        const portModal = new bootstrap.Modal(document.getElementById('portModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        let currentOltId = null;
        let portChart = null;
        
        // Helper function to show toast notifications
        function showToast(type, message) {
            const toastContainer = document.querySelector('.toast-container');
            const toastElement = document.createElement('div');
            toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
            toastElement.setAttribute('role', 'alert');
            toastElement.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            toastContainer.appendChild(toastElement);
            const bsToast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            // Remove toast after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }

        // Clear search
        function clearSearch() {
            document.getElementById('search').value = '';
            document.getElementById('filterForm').submit();
        }

        // View OLT details
        function viewOlt(id) {
            currentOltId = id;
            
            // Show loading spinner in each tab
            document.getElementById('oltOverview').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading OLT details...</p></div>';
            document.getElementById('oltPorts').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading port data...</p></div>';
            document.getElementById('oltLogs').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading logs...</p></div>';
            document.getElementById('oltDiagnostics').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading diagnostics...</p></div>';
            
            // Show modal first for better UX
            viewOltModal.show();
            
            // Fetch OLT data
            fetch(`api/olt.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        populateOltDetails(data.olt);
                        
                        // Now fetch port utilization data for charts
                        return fetch(`api/olt.php?action=port_utilization&id=${id}`);
                    } else {
                        throw new Error(data.message || 'Failed to load OLT details');
                    }
                })
                .then(response => response.json())
                .then(utilizationData => {
                    if (utilizationData.success) {
                        createPortUtilizationChart(utilizationData.utilization);
                    }
                    
                    // Now fetch diagnostic data
                    return fetch(`api/olt.php?action=diagnostics&id=${id}`);
                })
                .then(response => response.json())
                .then(diagnosticsData => {
                    if (diagnosticsData.success) {
                        populateDiagnostics(diagnosticsData.diagnostics);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('danger', 'Failed to load OLT details: ' + error.message);
                });
        }

        // Populate OLT details
        function populateOltDetails(data) {
            const olt = data.olt;
            const ports = data.ports;
            const logs = data.logs;
            
            // Overview tab
            let overviewHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Device Information</h6>
                        <table class="table">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td>${olt.name}</td>
                            </tr>
                            <tr>
                                <td><strong>Model:</strong></td>
                                <td>${olt.model}</td>
                            </tr>
                            <tr>
                                <td><strong>IP Address:</strong></td>
                                <td>${olt.ip_address}</td>
                            </tr>
                            <tr>
                                <td><strong>Location:</strong></td>
                                <td>${olt.location || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="olt-status status-${olt.status}"></span> ${olt.status.charAt(0).toUpperCase() + olt.status.slice(1)}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Technical Information</h6>
                        <table class="table">
                            <tr>
                                <td><strong>Firmware:</strong></td>
                                <td>${olt.firmware_version || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Ports:</strong></td>
                                <td>${olt.total_ports}</td>
                            </tr>
                            <tr>
                                <td><strong>Used Ports:</strong></td>
                                <td>${olt.used_ports}</td>
                            </tr>
                            <tr>
                                <td><strong>Uptime:</strong></td>
                                <td>${olt.uptime || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td><strong>Last Sync:</strong></td>
                                <td>${olt.last_sync ? formatDateTime(olt.last_sync) : 'Never'}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6 class="fw-bold">Notes</h6>
                        <p>${olt.notes || 'No notes available'}</p>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Port Utilization</h6>
                        <div style="height: 200px;">
                            <canvas id="portUtilizationChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Port Status</h6>
                        <div style="height: 200px;">
                            <canvas id="portStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('oltOverview').innerHTML = overviewHtml;
            
            // Ports tab
            let portsHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Port Configuration</h6>
                    <div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" onclick="filterPorts('all')">All</button>
                            <button type="button" class="btn btn-outline-success" onclick="filterPorts('active')">Active</button>
                            <button type="button" class="btn btn-outline-danger" onclick="filterPorts('fault')">Fault</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterPorts('unused')">Unused</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="portsTable">
                        <thead>
                            <tr>
                                <th>Port</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Signal</th>
                                <th>Client</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (ports && ports.length > 0) {
                ports.forEach(port => {
                    const clientInfo = port.client_subscription_id ? 
                        `${port.first_name} ${port.last_name} (${port.subscription_number})` : 'Not assigned';
                    
                    portsHtml += `
                        <tr data-status="${port.status}" data-client="${port.client_subscription_id ? 'assigned' : 'unassigned'}">
                            <td>${port.port_number}</td>
                            <td>${port.port_type}</td>
                            <td><span class="olt-status status-${port.status}"></span> ${port.status.charAt(0).toUpperCase() + port.status.slice(1)}</td>
                            <td>${port.signal_strength || 'N/A'}</td>
                            <td>${clientInfo}</td>
                            <td>${port.description || ''}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editPort(${port.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                portsHtml += '<tr><td colspan="7" class="text-center">No ports configured</td></tr>';
            }
            
            portsHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('oltPorts').innerHTML = portsHtml;
            
            // Logs tab
            let logsHtml = `
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Type</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (logs && logs.length > 0) {
                logs.forEach(log => {
                    let typeClass = '';
                    switch (log.log_type) {
                        case 'info': typeClass = 'text-info'; break;
                        case 'warning': typeClass = 'text-warning'; break;
                        case 'error': typeClass = 'text-danger'; break;
                        case 'status_change': typeClass = 'text-primary'; break;
                        case 'port_change': typeClass = 'text-secondary'; break;
                    }
                    
                    logsHtml += `
                        <tr>
                            <td>${formatDateTime(log.created_at)}</td>
                            <td><span class="${typeClass}">${log.log_type.replace('_', ' ')}</span></td>
                            <td>${log.message}</td>
                        </tr>
                    `;
                });
            } else {
                logsHtml += '<tr><td colspan="3" class="text-center">No logs found</td></tr>';
            }
            
            logsHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('oltLogs').innerHTML = logsHtml;
            
            // Diagnostics tab will be populated separately
            document.getElementById('oltDiagnostics').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading diagnostics...</p></div>';
        }

        // Create port utilization chart
        function createPortUtilizationChart(utilization) {
            if (portChart) {
                portChart.destroy();
            }
            
            // Create canvases with fixed height to prevent infinite expansion
            const ctx = document.getElementById('portUtilizationChart').getContext('2d');
            const statusCtx = document.getElementById('portStatusChart').getContext('2d');
            
            // Prepare data
            const data = {
                labels: ['Active', 'Inactive', 'Fault', 'Reserved'],
                datasets: [{
                    data: [
                        utilization.active || 0,
                        utilization.inactive || 0,
                        utilization.fault || 0,
                        utilization.reserved || 0
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',  // Green for active
                        'rgba(108, 117, 125, 0.7)', // Grey for inactive
                        'rgba(220, 53, 69, 0.7)',   // Red for fault
                        'rgba(255, 193, 7, 0.7)'    // Yellow for reserved
                    ],
                    borderColor: [
                        'rgb(40, 167, 69)',
                        'rgb(108, 117, 125)',
                        'rgb(220, 53, 69)',
                        'rgb(255, 193, 7)'
                    ],
                    borderWidth: 1
                }]
            };
            
            // Common chart options for fixed size and proper display
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5, // Fixed aspect ratio
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            };
            
            portChart = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: chartOptions
            });
            
            // Create the status chart
            const statusData = {
                labels: ['Used', 'Available'],
                datasets: [{
                    data: [
                        utilization.active || 0,
                        ((utilization.inactive || 0) + (utilization.reserved || 0))
                    ],
                    backgroundColor: [
                        'rgba(0, 123, 255, 0.7)',  // Blue for used
                        'rgba(108, 117, 125, 0.7)' // Grey for available
                    ],
                    borderColor: [
                        'rgb(0, 123, 255)',
                        'rgb(108, 117, 125)'
                    ],
                    borderWidth: 1
                }]
            };
            
            new Chart(statusCtx, {
                type: 'doughnut',
                data: statusData,
                options: chartOptions
            });
        }

        // Populate diagnostics tab
        function populateDiagnostics(diagnostics) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header fw-bold">System Status</div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-md-6">CPU Usage:</div>
                                    <div class="col-md-6">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: ${diagnostics.cpu_usage};" aria-valuenow="${parseInt(diagnostics.cpu_usage)}" aria-valuemin="0" aria-valuemax="100">${diagnostics.cpu_usage}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">Memory Usage:</div>
                                    <div class="col-md-6">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: ${diagnostics.memory_usage};" aria-valuenow="${parseInt(diagnostics.memory_usage)}" aria-valuemin="0" aria-valuemax="100">${diagnostics.memory_usage}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">Temperature:</div>
                                    <div class="col-md-6">${diagnostics.temperature}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header fw-bold">Actions</div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" onclick="syncOlt(${currentOltId})">
                                        <i class="fas fa-sync-alt me-2"></i>Sync with Device
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="runDiagnostic(${currentOltId})">
                                        <i class="fas fa-stethoscope me-2"></i>Run Diagnostic Test
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="rebootOlt(${currentOltId})">
                                        <i class="fas fa-power-off me-2"></i>Reboot Device
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header fw-bold">Interface Status</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Interface</th>
                                        <th>Status</th>
                                        <th>RX Packets</th>
                                        <th>TX Packets</th>
                                        <th>Errors</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            if (diagnostics.interfaces && diagnostics.interfaces.length > 0) {
                diagnostics.interfaces.forEach(iface => {
                    const statusClass = iface.status === 'up' ? 'text-success' : 'text-danger';
                    html += `
                        <tr>
                            <td>${iface.name}</td>
                            <td><span class="${statusClass}">${iface.status.toUpperCase()}</span></td>
                            <td>${iface.rx_packets.toLocaleString()}</td>
                            <td>${iface.tx_packets.toLocaleString()}</td>
                            <td>${iface.errors}</td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="5" class="text-center">No interface data available</td></tr>';
            }
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('oltDiagnostics').innerHTML = html;
        }

        // Filter ports
        function filterPorts(filter) {
            const table = document.getElementById('portsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const status = rows[i].getAttribute('data-status');
                const client = rows[i].getAttribute('data-client');
                
                switch (filter) {
                    case 'active':
                        rows[i].style.display = status === 'active' ? '' : 'none';
                        break;
                    case 'fault':
                        rows[i].style.display = status === 'fault' ? '' : 'none';
                        break;
                    case 'unused':
                        rows[i].style.display = client === 'unassigned' ? '' : 'none';
                        break;
                    default:
                        rows[i].style.display = '';
                }
            }
        }

        // Edit OLT
        function editOlt(id) {
            fetch(`api/olt.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const olt = data.olt.olt;
                        
                        // Populate form
                        document.getElementById('oltId').value = olt.id;
                        document.getElementById('name').value = olt.name;
                        document.getElementById('model').value = olt.model;
                        document.getElementById('ipAddress').value = olt.ip_address;
                        document.getElementById('location').value = olt.location || '';
                        document.getElementById('totalPorts').value = olt.total_ports;
                        document.getElementById('status').value = olt.status;
                        document.getElementById('firmwareVersion').value = olt.firmware_version || '';
                        document.getElementById('notes').value = olt.notes || '';
                        
                        // Update modal title
                        document.querySelector('#oltModal .modal-title').textContent = 'Edit OLT Device';
                        
                        // Show modal
                        oltModal.show();
                    } else {
                        throw new Error(data.message || 'Failed to load OLT details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('danger', 'Failed to load OLT details: ' + error.message);
                });
        }

        // Save OLT - Updated function with better error handling
        function saveOlt() {
            const id = document.getElementById('oltId').value;
            const method = id ? 'PUT' : 'POST';
            const url = 'api/olt.php' + (id ? `?id=${id}` : '');
            
            // Get form data
            const formData = new FormData(document.getElementById('oltForm'));
            const data = {};
            
            // Convert FormData to object
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Validate required fields
            if (!data.name) {
                showToast('danger', 'Name is required');
                return;
            }
            
            if (!data.model) {
                showToast('danger', 'Model is required');
                return;
            }
            
            if (!data.ip_address) {
                showToast('danger', 'IP Address is required');
                return;
            }
            
            if (!data.total_ports || isNaN(data.total_ports) || data.total_ports < 1) {
                showToast('danger', 'Valid total ports number is required');
                return;
            }
            
            console.log('Saving OLT with data:', data); // Debug: log the data being sent
            
            // Show loading state
            const saveButton = document.querySelector('#oltModal button.btn-primary');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveButton.disabled = true;
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status); // Debug: log response status
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(result => {
                console.log('Server response:', result); // Debug: log the server response
                
                if (result.success) {
                    showToast('success', method === 'PUT' ? 'OLT updated successfully' : 'OLT created successfully');
                    oltModal.hide();
                    
                    // Refresh the page after a brief delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Failed to save OLT');
                }
            })
            .catch(error => {
                console.error('Error saving OLT:', error);
                showToast('danger', error.message);
                
                // Reset button state
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            });
        }

        // Edit port
        function editPort(id) {
            fetch(`api/olt.php?action=get_port&id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const port = data.port;
                        
                        // Populate form
                        document.getElementById('portId').value = port.id;
                        document.getElementById('oltIdForPort').value = port.olt_id;
                        document.getElementById('portNumber').value = port.port_number;
                        document.getElementById('portType').value = port.port_type;
                        document.getElementById('portStatus').value = port.status;
                        document.getElementById('signalStrength').value = port.signal_strength || '';
                        document.getElementById('portDescription').value = port.description || '';
                        
                        // Get active subscriptions for dropdown
                        getActiveSubscriptions().then(subscriptions => {
                            const select = document.getElementById('clientSubscription');
                            select.innerHTML = '<option value="">None</option>';
                            
                            subscriptions.forEach(sub => {
                                const option = document.createElement('option');
                                option.value = sub.id;
                                option.text = `${sub.first_name} ${sub.last_name} - ${sub.subscription_number}`;
                                option.selected = sub.id == port.client_subscription_id;
                                select.appendChild(option);
                            });
                        });
                        
                        // Show modal
                        portModal.show();
                    } else {
                        throw new Error(data.message || 'Failed to load port details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('danger', 'Failed to load port details: ' + error.message);
                });
        }

        // Save port
        function savePort() {
            const id = document.getElementById('portId').value;
            const url = `api/olt.php?action=update_port&id=${id}`;
            
            // Get form data
            const formData = new FormData(document.getElementById('portForm'));
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Show loading state
            const saveButton = document.querySelector('#portModal button.btn-primary');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveButton.disabled = true;
            
            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'Port updated successfully');
                    portModal.hide();
                    
                    // Refresh the page after a brief delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to save port');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', error.message);
                
                // Reset button state
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            });
        }

        // Get active subscriptions
        function getActiveSubscriptions() {
            return fetch('api/subscriptions.php?action=active')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        return data.subscriptions;
                    } else {
                        throw new Error(data.message || 'Failed to load subscriptions');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('danger', 'Failed to load subscriptions: ' + error.message);
                    return [];
                });
        }

        // Sync OLT
        function syncOlt(id) {
            fetch(`api/olt.php?action=sync&id=${id}`, {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'OLT synced successfully');
                    
                    // Refresh the page after a brief delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to sync OLT');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Failed to sync OLT: ' + error.message);
            });
        }

        // Run diagnostic
        function runDiagnostic(id) {
            fetch(`api/olt.php?action=diagnostic&id=${id}`, {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'Diagnostic test completed successfully');
                    
                    // Refresh the page after a brief delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to run diagnostic test');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Failed to run diagnostic test: ' + error.message);
            });
        }

        // Reboot OLT
        function rebootOlt(id) {
            fetch(`api/olt.php?action=reboot&id=${id}`, {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'OLT rebooted successfully');
                    
                    // Refresh the page after a brief delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to reboot OLT');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Failed to reboot OLT: ' + error.message);
            });
        }

        // Delete OLT
        function deleteOlt(id) {
            deleteOltId = id;
            deleteModal.show();
        }

        // Confirm delete
        function confirmDelete() {
            fetch(`api/olt.php?id=${deleteOltId}`, {
                method: 'DELETE'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'OLT deleted successfully');
                    deleteModal.hide();
                    
                    // Refresh the page after a brief delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to delete OLT');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Failed to delete OLT: ' + error.message);
            });
        }

        // Format date and time
        function formatDateTime(dateTime) {
            const date = new Date(dateTime);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        // Add resize handler for charts when modal is shown
        document.getElementById('viewOltModal').addEventListener('shown.bs.modal', function () {
            // If charts exist, resize them to fit current container
            if (portChart) {
                portChart.resize();
            }
        });
    </script>
</body>
</html>