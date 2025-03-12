/**
 * JavaScript functions for LCP management page
 */

// Global variables
let currentLcpId = null;

// View LCP details
function viewLcp(id) {
    currentLcpId = id;
    
    // Show loading spinner
    document.getElementById('lcpOverview').innerHTML = 
        '<div class="text-center p-3"><div class="spinner-border text-primary"></div><p class="mt-2">Loading LCP details...</p></div>';
    
    // Reset other tabs
    document.getElementById('lcpPorts').innerHTML = '';
    document.getElementById('lcpClients').innerHTML = '';
    document.getElementById('lcpMaintenance').innerHTML = '';
    document.getElementById('lcpLogs').innerHTML = '';
    
    // Reset loaded flags
    $("#lcpPorts").data('loaded', false);
    $("#lcpClients").data('loaded', false);
    $("#lcpMaintenance").data('loaded', false);
    $("#lcpLogs").data('loaded', false);
    
    // Show modal
    const viewLcpModal = new bootstrap.Modal(document.getElementById('viewLcpModal'));
    viewLcpModal.show();
    
    // Fetch LCP data
    fetch(`api/lcp.php?id=${id}`)
        .then(response => {
            // Check if response is ok before parsing JSON
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            // First check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // If not JSON, get the text and show it
                return response.text().then(text => {
                    throw new Error('Response is not JSON: ' + text.substring(0, 100) + '...');
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateLcpDetails(data.lcp);
            } else {
                throw new Error(data.message || 'Failed to load LCP details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('lcpOverview').innerHTML = 
                `<div class="alert alert-danger">Error loading LCP details: ${error.message}</div>`;
        });
}

// Populate LCP details
function populateLcpDetails(lcp) {
    const overview = document.getElementById('lcpOverview');
    overview.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Name</h5>
                <p>${lcp.name || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <h5>Model</h5>
                <p>${lcp.model || 'N/A'}</p>
            </div>
            <!-- Add other fields -->
        </div>
    `;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any needed elements
    
    // Set up event listeners for tabs
    const tabLinks = document.querySelectorAll('#lcpDetailTabs button');
    tabLinks.forEach(tab => {
        tab.addEventListener('click', function(e) {
            const tabId = e.target.id;
            
            if (tabId === 'ports-tab' && !$("#lcpPorts").data('loaded')) {
                loadLcpPorts(currentLcpId);
            } else if (tabId === 'clients-tab' && !$("#lcpClients").data('loaded')) {
                loadLcpClients(currentLcpId);
            } else if (tabId === 'maintenance-tab' && !$("#lcpMaintenance").data('loaded')) {
                loadLcpMaintenance(currentLcpId);
            } else if (tabId === 'logs-tab' && !$("#lcpLogs").data('loaded')) {
                loadLcpLogs(currentLcpId);
            }
        });
    });
});

// Additional functions for tab content loading
function loadLcpPorts(lcpId) {
    // Implementation here
}

function loadLcpClients(lcpId) {
    // Feature removed
    document.getElementById('lcpClients').innerHTML = '<div class="alert alert-info">This feature has been removed.</div>';
    $("#lcpClients").data('loaded', true);
}

function loadLcpMaintenance(lcpId) {
    // Implementation here
}

function loadLcpLogs(lcpId) {
    // Implementation here
}

// Helper functions
function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container');
    const toast = document.createElement('div');
    toast.className = `toast bg-${type} text-white`;
    toast.innerHTML = `
        <div class="toast-header bg-${type} text-white">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}
