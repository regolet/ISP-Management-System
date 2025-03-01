<?php
require_once '../config.php';
check_auth();

$page_title = "LCP Management";
$_SESSION["active_menu"] = "pon_management";
?>

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>LCP Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLcpModal">
            <i class="bx bx-plus"></i> Add New LCP
        </button>
    </div>

    <!-- Alerts container -->
    <div class="alerts mb-4"></div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="filterSplitterType" class="form-label">Splitter Type</label>
                    <select id="filterSplitterType" class="form-select">
                        <option value="">All Splitter Types</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="warning">Warning</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterSearch" class="form-label">Search</label>
                    <input type="text" id="filterSearch" class="form-control" placeholder="Search LCPs...">
                </div>
            </div>
        </div>
    </div>

    <!-- LCP Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Name</th>
                        <th>Connection</th>
                        <th>Port</th>
                        <th>Splitter Type</th>
                        <th>Port Usage</th>
                        <th>Total Loss</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody id="lcpTableBody">
                    <tr>
                        <td colspan="8" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add LCP Modal -->
<div class="modal fade" id="addLcpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New LCP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLcpForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">LCP Name</label>
                            <input type="text" name="name" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Connection Type</label>
                            <select name="connection_type" id="connectionType" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="OLT">OLT</option>
                                <option value="LCP">LCP</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <!-- OLT Connection Fields -->
                        <div id="oltConnectionFields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select OLT</label>
                                    <select id="oltSelect" class="form-select">
                                        <option value="">Select OLT</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PON Port</label>
                                    <select name="pon_port" id="addOltPonPort" class="form-select">
                                        <option value="">Select Port</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- LCP Connection Fields -->
                        <div id="lcpConnectionFields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select Parent LCP</label>
                                    <select id="parentLcpSelect" class="form-select">
                                        <option value="">Select Parent LCP</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PON Port</label>
                                    <select name="pon_port" id="addLcpPonPort" class="form-select">
                                        <option value="">Select Port</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Splitter Type</label>
                            <select name="splitter_type" id="splitterType" class="form-select" required>
                                <option value="">Select Splitter Type</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fiber Length (meters)</label>
                            <input type="number" name="meters_lcp" class="form-control" value="0" min="0" step="1">
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Port Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Total Ports</label>
                                <div><span id="totalPorts">-</span></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Available Ports</label>
                                <div><span id="availablePorts">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Power Budget</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Splitter Loss</label>
                                <div><span id="splitterLoss">-</span> dB</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Total Loss</label>
                                <div><span id="totalLoss">-</span> dB</div>
                            </div>
                            <div class="col-12">
                                <div id="powerBudgetStatus"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add LCP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit LCP Modal -->
<div class="modal fade" id="editLcpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit LCP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editLcpForm">
                <input type="hidden" name="id">
                <div class="modal-body">
                    <!-- Same form fields as Add LCP Modal -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">LCP Name</label>
                            <input type="text" name="name" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Connection Type</label>
                            <select name="connection_type" id="editConnectionType" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="OLT">OLT</option>
                                <option value="LCP">LCP</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <!-- OLT Connection Fields -->
                        <div id="editOltConnectionFields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select OLT</label>
                                    <select id="editOltSelect" class="form-select">
                                        <option value="">Select OLT</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PON Port</label>
                                    <select name="pon_port" id="editOltPonPort" class="form-select">
                                        <option value="">Select Port</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- LCP Connection Fields -->
                        <div id="editLcpConnectionFields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select Parent LCP</label>
                                    <select id="editParentLcpSelect" class="form-select">
                                        <option value="">Select Parent LCP</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PON Port</label>
                                    <select name="pon_port" id="editLcpPonPort" class="form-select">
                                        <option value="">Select Port</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Splitter Type</label>
                            <select name="splitter_type" id="editSplitterType" class="form-select" required>
                                <option value="">Select Splitter Type</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fiber Length (meters)</label>
                            <input type="number" name="meters_lcp" class="form-control" value="0" min="0" step="1">
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Port Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Total Ports</label>
                                <div><span id="editTotalPorts">-</span></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Available Ports</label>
                                <div><span id="editAvailablePorts">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Power Budget</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Splitter Loss</label>
                                <div><span id="editSplitterLoss">-</span> dB</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Total Loss</label>
                                <div><span id="editTotalLoss">-</span> dB</div>
                            </div>
                            <div class="col-12">
                                <div id="editPowerBudgetStatus"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View LCP Details Modal -->
<div class="modal fade" id="viewLcpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">LCP Details: <span id="viewLcpName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Status:</td>
                                <td><span id="viewLcpStatus"></span></td>
                            </tr>
                            <tr>
                                <td>Connection:</td>
                                <td id="viewLcpConnection"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Technical Details</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Splitter Type:</td>
                                <td id="viewLcpSplitterType"></td>
                            </tr>
                            <tr>
                                <td>Total Ports:</td>
                                <td id="viewLcpTotalPorts"></td>
                            </tr>
                            <tr>
                                <td>Used Ports:</td>
                                <td id="viewLcpUsedPorts"></td>
                            </tr>
                            <tr>
                                <td>Total Loss:</td>
                                <td id="viewLcpTotalLoss"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h6>Connected NAPs</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="viewLcpNapTable">
                        <thead>
                            <tr>
                                <th>NAP Name</th>
                                <th>Port</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.lcp-status {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.lcp-status.warning { background-color: var(--bs-warning); }
.lcp-status.active { background-color: var(--bs-success); }
.lcp-status.inactive { background-color: var(--bs-secondary); }

.port-usage {
    font-size: 0.85em;
    color: var(--bs-gray-600);
}

.splitter-info {
    font-size: 0.85em;
    color: var(--bs-gray-600);
}

.loss-value {
    font-weight: 500;
}

.progress {
    height: 20px;
    font-size: 0.85em;
    font-weight: 500;
}
</style>

<script src="js/lcp_management.js"></script>

</body>
</html>
