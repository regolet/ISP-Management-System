<![CDATA[/**
 * LCP View Handler - Manages viewing and interaction with LCP details
 */

// Store the current LCP ID being viewed
let currentLcpId = null;

/**
 * View LCP details - Main entry point
 * @param {number} id - LCP ID to view
 */
function viewLcp(id) {
    console.log("Viewing LCP with ID:", id);
    currentLcpId = id;
    
    // Clear all tabs and show loading spinner in overview
    document.getElementById('lcpOverview').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div><p class="mt-2">Loading LCP details...</p></div>';
    document.getElementById('lcpPorts').innerHTML = '';
    document.getElementById('lcpClients').innerHTML = '';
    document.getElementById('lcpMaintenance').innerHTML = '';
    document.getElementById('lcpLogs').innerHTML = '';
    
    // Reset loaded flags for tabs
    $("#lcpPorts").data('loaded', false);
    $("#lcpClients").data('loaded', false);
    $("#lcpMaintenance").data('loaded', false);
    $("#lcpLogs").data('loaded', false);
    
    // Show the modal first for better UX
    const viewLcpModal = new bootstrap.Modal(document.getElementById('viewLcpModal'));
    viewLcpModal.show();
    
    // Fetch LCP data with proper error handling
    fetchLcpData(id);
}

/**
 * Fetch LCP data from API with robust error handling
 * @param {number} id - LCP ID to fetch
 */
function fetchLcpData(id) {
    // Updated API URL to point to the root api directory
    const apiUrl = `/api/internal/get_lcp.php?id=${id}`; 
    console.log("Fetching LCP data from:", apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            // First check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // If not JSON, get the text and show it
                return response.text().then(text => {
                    console.error("Non-JSON response:", text.substring(0, 500));
                    throw new Error('Response is not JSON: ' + text.substring(0, 100) + '...');
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log("LCP data received:", data);
            
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format');
            }
            
            if (!data.success) {
                throw new Error(data.message || 'Unknown error occurred');
            }
            
            populateLcpDetails(data.lcp);
        })
        .catch(error => {
            console.error("Error fetching LCP data:", error);
            document.getElementById('lcpOverview').innerHTML = 
                `<div class="alert alert-danger">
                    <h4>Error Loading LCP Details</h4>
                    <p>${error.message}</p>
                    <p><button class="btn btn-sm btn-outline-danger" onclick="fetchLcpData(${id})">
                        <i class="fas fa-sync-alt"></i> Retry
                    </button></p>
                </div>`;
        });
}

/**
 * Populate LCP details
 */
function populateLcpDetails(lcp) {
    // Safe getter function for properties
    const get = (obj, path, defaultValue = 'N/A') => {
        const keys = path.split('.');
        let result = obj;
        for (const key of keys) {
            result = result && result[key] !== undefined ? result[key] : undefined;
        }
        return result !== undefined && result !== null && result !== '' ? result : defaultValue;
    };
    
    // Build HTML
    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Name</h5>
                    <p class="lead">${get(lcp, 'name')}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Model</h5>
                    <p class="lead">${get(lcp, 'model')}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Location</h5>
                    <p>${get(lcp, 'location')}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Status</h5>
                    <p><span class="badge bg-${getStatusColor(lcp.status)}">${get(lcp, 'status')}</span></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Total Ports</h5>
                    <p>${get(lcp, 'total_ports')}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Used Ports</h5>
                    <p>${get(lcp, 'used_ports')} / ${get(lcp, 'total_ports')}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Parent OLT</h5>
                    <p>${get(lcp, 'parent_olt_name')}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Installation Date</h5>
                    <p>${get(lcp, 'installation_date')}</p>
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-4">
                    <h5 class="text-muted mb-1">Notes</h5>
                    <p>${get(lcp, 'notes')}</p>
                </div>
            </div>
            <div class="col-md-12 mt-3">
                <a href="lcp-edit.php?id=${lcp.id}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit LCP
                </a>
            </div>
        </div>
    `;
    
    document.getElementById('lcpOverview').innerHTML = html;
}

/**
 * Get status color class
 */
function getStatusColor(status) {
    switch(status?.toLowerCase()) {
        case 'active': return 'success';
        case 'maintenance': return 'warning';
        case 'offline': return 'danger';
        default: return 'secondary';
    }
}

/**
 * Load LCP ports tab
 */
function loadLcpPorts(lcpId) {
    document.getElementById('lcpPorts').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div><p class="mt-2">Loading ports...</p></div>';
    
    fetch(`/api/internal/lcp.php?action=ports&id=${lcpId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load ports');
            }
            
            if (!data.ports || !Array.isArray(data.ports) || data.ports.length === 0) {
                document.getElementById('lcpPorts').innerHTML = '<div class="alert alert-info">No ports found for this LCP</div>';
                return;
            }
            
            // Build HTML for ports
            let html = '<div class="port-container">';
            data.ports.forEach(port => {
                const statusClass = getPortStatusClass(port.status);
                html += `
                    <div class="port-item ${statusClass}">
                        <strong>Port ${port.port_number}</strong>
                        ${port.client_name ? '<br><small>' + port.client_name + '</small>' : ''}
                        <br><small>${port.status}</small>
                    </div>`;
            });
            html += '</div>';
            
            // Add port table
            html += `
                <div class="table-responsive mt-4">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Port</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Signal</th>
                                <th>Client</th>
                            </tr>
                        </thead>
                        <tbody>`;
                        
            data.ports.forEach(port => {
                html += `
                    <tr>
                        <td>${port.port_number}</td>
                        <td>${port.port_type || 'Standard'}</td>
                        <td><span class="badge bg-${getStatusColorForPort(port.status)}">${port.status}</span></td>
                        <td>${port.signal_strength ? port.signal_strength + ' dBm' : 'N/A'}</td>
                        <td>${port.client_name || 'None'}</td>
                    </tr>`;
            });
            
            html += '</tbody></table></div>';
            
            document.getElementById('lcpPorts').innerHTML = html;
            $("#lcpPorts").data('loaded', true);
        })
        .catch(error => {
            console.error("Error loading ports:", error);
            document.getElementById('lcpPorts').innerHTML = `
                <div class="alert alert-danger">
                    Error loading port data: ${error.message}
                </div>`;
        });
}

/**
 * Get port status class
 */
function getPortStatusClass(status) {
    switch(status?.toLowerCase()) {
        case 'active': return 'port-active';
        case 'fault': return 'port-fault';
        case 'reserved': return 'port-reserved';
        default: return 'port-inactive';
    }
}

/**
 * Get port status color
 */
function getStatusColorForPort(status) {
    switch(status?.toLowerCase()) {
        case 'active': return 'success';
        case 'fault': return 'danger';
        case 'reserved': return 'warning';
        default: return 'secondary';
    }
}

// Simple stubs for other tab functions
function loadLcpClients(lcpId) {
    document.getElementById('lcpClients').innerHTML = '<div class="alert alert-info">This feature has been removed.</div>';
    $("#lcpClients").data('loaded', true);
}

function loadLcpMaintenance(lcpId) {
    document.getElementById('lcpMaintenance').innerHTML = '<div class="alert alert-info">Maintenance information loading is not yet implemented.</div>';
    $("#lcpMaintenance").data('loaded', true);
}

function loadLcpLogs(lcpId) {
    document.getElementById('lcpLogs').innerHTML = '<div class="alert alert-info">Logs loading is not yet implemented.</div>';
    $("#lcpLogs").data('loaded', true);
}

// Helper functions for LCP CRUD operations
function editLcp(id) {
    window.location.href = `lcp-edit.php?id=${id}`;
}

function deleteLcp(id) {
    const confirmDelete = confirm("Are you sure you want to delete this LCP?");
    if (confirmDelete) {
        fetch(`/api/lcp.php?id=${id}&_method=DELETE`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("LCP deleted successfully");
                window.location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => {
            alert("Error deleting LCP: " + error);
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log("LCP view script loaded successfully!");
    
    // Initialize tab event listeners
    const tabLinks = document.querySelectorAll('#lcpDetailTabs button');
    tabLinks.forEach(tab => {
        tab.addEventListener('click', function(e) {
            const tabId = e.target.id;
            
            if (tabId === 'ports-tab' && !$("#lcpPorts").data('loaded') && currentLcpId) {
                loadLcpPorts(currentLcpId);
            } else if (tabId === 'clients-tab' && !$("#lcpClients").data('loaded') && currentLcpId) {
                loadLcpClients(currentLcpId);
            } else if (tabId === 'maintenance-tab' && !$("#lcpMaintenance").data('loaded') && currentLcpId) {
                loadLcpMaintenance(currentLcpId);
            } else if (tabId === 'logs-tab' && !$("#lcpLogs").data('loaded') && currentLcpId) {
                loadLcpLogs(currentLcpId);
            }
        });
    });
});
]]>