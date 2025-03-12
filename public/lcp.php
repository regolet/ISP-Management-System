<?php require_once __DIR__ . '/../includes/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCP Management - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">

    <style>
        .lcp-status {
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
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Render Sidebar -->
    <?php require_once __DIR__ . '/../views/layouts/sidebar.php'; renderSidebar('lcp'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">LCP Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lcpModal">
                    <i class="fas fa-plus me-2"></i>Add New LCP
                </button>
            </div>

            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <input type="text" class="form-control" id="search" name="search"
                                      placeholder="Search LCP devices..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                <?php if (!empty($search)): ?>
                                    <span class="clear-search" onclick="clearSearch()">×</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="maintenance" <?php echo ($status ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="offline" <?php echo ($status ?? '') === 'offline' ? 'selected' : ''; ?>>Offline</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="sort" onchange="this.form.submit()">
                                <option value="name" <?php echo ($sort ?? '') === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="location" <?php echo ($sort ?? '') === 'location' ? 'selected' : ''; ?>>Sort by Location</option>
                                <option value="total_ports" <?php echo ($sort ?? '') === 'total_ports' ? 'selected' : ''; ?>>Sort by Ports</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- LCP Devices Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Model</th>
                                    <th>Location</th>
                                    <th>Parent OLT</th>
                                    <th>Status</th>
                                    <th>Ports</th>
                                    <th>Installation Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once __DIR__ . '/../app/Controllers/LcpController.php';
                                $lcpController = new \App\Controllers\LcpController($db); // Assuming $db is available globally or initialized elsewhere
                                $lcpData = $lcpController->getLcpDevices($_GET);
                                foreach ($lcpData['data'] as $lcp):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lcp['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lcp['model']); ?></td>
                                        <td><?php echo htmlspecialchars($lcp['location']); ?></td>
                                        <td><?php echo htmlspecialchars($lcp['parent_olt_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="lcp-status status-<?php echo $lcp['status']; ?>"></span>
                                            <?php echo ucfirst($lcp['status']); ?>
                                        </td>
                                        <td>
                                            <?php echo $lcp['used_ports']; ?> / <?php echo $lcp['total_ports']; ?>
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: <?php echo ($lcp['total_ports'] > 0) ? ($lcp['used_ports'] / $lcp['total_ports'] * 100) : 0; ?>%;"
                                                    aria-valuenow="<?php echo $lcp['used_ports']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $lcp['total_ports']; ?>">
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($lcp['installation_date'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="viewLcp(<?php echo $lcp['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick="editLcp(<?php echo $lcp['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteLcp(<?php echo $lcp['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($lcpData['data'])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No LCP devices found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($lcpData['total_pages'] > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $lcpData['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === ($lcpData['page'] ?? 1) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&sort=<?php echo urlencode($sort ?? 'name'); ?>&order=<?php echo urlencode($order ?? 'asc'); ?>">
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

    <!-- Add/Edit LCP Modal -->
    <div class="modal fade" id="lcpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New LCP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="lcpForm">
                        <input type="hidden" id="lcpId" name="id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude">
                            </div>
                            <div class-md-6>
                                <label class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude">
                            </div>
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
                            <div class="col-md-6">
                                <label class="form-label">Parent OLT</label>
                                <select class="form-select" id="parentOlt" name="parent_olt_id" onchange="loadOltPorts(this.value)">
                                    <option value="">None</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Port</label>
                                <select class="form-select" id="parentPort" name="parent_port_id" disabled>
                                    <option value="">Select OLT first</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Installation Date</label>
                                <input type="date" class="form-control" id="installationDate" name="installation_date">
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
                    <button type="button" class="btn btn-primary" onclick="saveLcp()">Save Lcp</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View LCP Modal -->
    <div class="modal fade" id="viewLcpModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">LCP Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="lcpDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ports-tab" data-bs-toggle="tab" data-bs-target="#ports" type="button" role="tab">Ports</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">Clients</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">Maintenance</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">Logs</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="lcpTabContent">
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div id="lcpOverview"></div>
                        </div>
                        <div class="tab-pane fade" id="ports" role="tabpanel">
                            <div id="lcpPorts"></div>
                        </div>
                        <div class="tab-pane fade" id="clients" role="tabpanel">
                            <div id="lcpClients"></div>
                        </div>
                        <div class="tab-pane fade" id="maintenance" role="tabpanel">
                            <div id="lcpMaintenance"></div>
                        </div>
                        <div class="tab-pane fade" id="logs" role="tabpanel">
                            <div id="lcpLogs"></div>
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
                        <input type="hidden" id="lcpIdForPort" name="lcp_id">
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

    <!-- Add Maintenance Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="maintenanceModalTitle">Add Maintenance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="maintenanceForm"></form>
                        <input type="hidden" id="maintenanceId" name="id">
                        <input type="hidden" id="lcpIdForMaintenance" name="lcp_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Maintenance Type</label>
                                <select class="form-select" id="maintenanceType" name="maintenance_type" required>
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="inspection">Inspection</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="maintenanceStatus" name="status">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="startDate" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" id="endDate" name="end_date">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="maintenanceDescription" name="description" rows="3" required></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="maintenanceNotes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveMaintenance">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete LCP Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this LCP device? This action cannot be undone.</p>
                    <p class="text-warning">Note: LCP devices with active client connections cannot be deleted.</p>
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
    <script src="/assets/js/sidebar.js"></script>

    <!-- UPDATED: Use the correct path to the JavaScript file -->
    <script src="/js/lcp-view.js"></script>

    <!-- Keep the fallback script in case the external file still fails to load -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("LCP page initialized");

            // Check if viewLcp function was properly defined in the external JS
            if (typeof viewLcp !== 'function') {
                console.warn("External LCP view script didn't load properly. Using fallback implementation.");
                console.log("External LCP view script didn't load properly. Using fallback implementation.");

                // Fallback implementation
                window.viewLcp = function(id) {
                    console.log("Using fallback viewLcp function for ID:", id);

                    // Show loading spinner
                    document.getElementById('lcpOverview').innerHTML =
                        '<div class="text-center p-3"><div class="spinner-border text-primary"></div><p class="mt-2">Loading LCP details...</p></div>';

                    // Show modal
                    const viewLcpModal = new bootstrap.Modal(document.getElementById('viewLcpModal'));
                    viewLcpModal.show();

                    // Make a direct XHR request instead of using fetch for better error handling
                    const xhr = new XMLHttpRequest();
                    // Corrected API path in fallback implementation
                    xhr.open('GET', '../../api/internal/get_lcp.php?id=' + id);
                    xhr.responseType = 'json';

                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            if (xhr.response && xhr.response.success) {
                                // Create a basic display of the LCP data
                                const lcp = xhr.response.lcp;
                                let html = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Name</h5>
                                            <p>${lcp.name || 'N/A'}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Model</h5>
                                            <p>${lcp.model || 'N/A'}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Location</h5>
                                            <p>${lcp.location || 'N/A'}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Status</h5>
                                            <p>${lcp.status || 'N/A'}</p>
                                        </div>
                                    </div>`;
                                document.getElementById('lcpOverview').innerHTML = html;
                            } else {
                                document.getElementById('lcpOverview').innerHTML =
                                    `<div class="alert alert-danger">Error: ${xhr.response?.message || 'Unknown error'}</div>`;
                            }
                        } else {
                            document.getElementById('lcpOverview').innerHTML =
                                `<div class="alert alert-danger">Server error: ${xhr.status}</div>`;
                            }
                        }

                        xhr.onerror = function() {
                            document.getElementById('lcpOverview').innerHTML =
                                `<div class="alert alert-danger">Network error. Please check your connection.</div>`;
                        };

                        xhr.send();
                    };
                } else {
                    console.log("External LCP view script loaded successfully!");
                }
            });

        // Clear search function
        function clearSearch() {
            document.getElementById('search').value = '';
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>