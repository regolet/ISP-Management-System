/**
 * JavaScript functions for LCP edit page
 */

// Load OLT ports when OLT selection changes
function loadOltPorts(oltId, selectedPortId = null) {
    const portSelect = document.getElementById('parentPort');
    
    // Clear current options
    portSelect.innerHTML = '<option value="">Loading...</option>';
    portSelect.disabled = true;
    
    if (!oltId) {
        portSelect.innerHTML = '<option value="">Select OLT first</option>';
        return;
    }
    
    // Fetch available ports from API
    fetch(`/api/lcp.php?action=available_olt_ports&olt_id=${oltId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log("OLT ports data:", data);
            
            if (data.success && data.ports) {
                portSelect.innerHTML = ''; // Clear existing options

                // Add "None" option
                const noneOption = document.createElement('option');
                noneOption.value = '';
                noneOption.textContent = 'None';
                portSelect.appendChild(noneOption);
                
                // Check if we have any ports
                if (data.ports.length === 0) {
                    const noPortsOption = document.createElement('option');
                    noPortsOption.value = ''; // or maybe some special value like '-1'
                    noPortsOption.textContent = 'No available ports';
                    portSelect.appendChild(noPortsOption);
                    portSelect.disabled = true;
                    return;
                }
                
                // Add each port as an option
                data.ports.forEach(port => {
                    const option = document.createElement('option');
                    option.value = port.id;
                    option.textContent = `Port ${port.port_number} (${port.port_type || 'Standard'})`;
                    
                    // If this is the selected port, mark it as selected
                    if (selectedPortId && port.id == selectedPortId) {
                        option.selected = true;
                    }
                    
                    portSelect.appendChild(option);
                });
                
                portSelect.disabled = false;
            } else {
                portSelect.innerHTML = '<option value="">Error loading ports</option>';
                console.error('Error loading OLT ports:', data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error loading OLT ports:', error);
            portSelect.innerHTML = '<option value="">Failed to load ports</option>';
            portSelect.disabled = true;
        });
}

// Initialize the page when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize OLT selection and port loading
    const oltSelect = document.getElementById('parentOlt');
    const portSelect = document.getElementById('parentPort');
    
    if (oltSelect && portSelect) {
        // Store the current selected port if any
        const currentPortId = portSelect.value;
        
        // Load ports if an OLT is selected
        if (oltSelect.value) {
            loadOltPorts(oltSelect.value, currentPortId);
        }
        
        // Set up event listener for OLT selection changes
        oltSelect.addEventListener('change', function() {
            console.log("Parent OLT dropdown changed!"); // ADDED LOG
            loadOltPorts(this.value);
        });
    }
    
    // Handle form submission
    const form = document.getElementById('lcpForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Add validation if needed
            console.log('Form submitted');
        });
    }
});

// Display error message
function showError(message) {
    const errorContainer = document.getElementById('errorContainer');
    if (errorContainer) {
        errorContainer.textContent = message;
        errorContainer.style.display = 'block';
        setTimeout(() => {
            errorContainer.style.display = 'none';
        }, 5000);
    } else {
        alert(message);
    }
}
