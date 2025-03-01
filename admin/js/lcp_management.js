let currentLcpId = null;
let splitterTypes = {};
let availablePorts = {};

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadLCPList();
    loadSplitterTypes();
    setupEventListeners();
    setupFormValidation();
});

// Load LCP list
async function loadLCPList() {
    try {
        const response = await fetch('ftth_get_lcp_list.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load LCPs');
        }

        const tbody = document.getElementById('lcpTableBody');
        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No LCPs found</td></tr>';
            return;
        }

        data.data.forEach(lcp => {
            const status = calculateLcpStatus(lcp);
            const portUsage = (lcp.used_ports / lcp.total_ports) * 100;
            
            const row = `
                <tr>
                    <td>
                        <span class="lcp-status ${status.class}" title="${status.description}"></span>
                    </td>
                    <td>${escapeHtml(lcp.name)}</td>
                    <td>
                        ${lcp.mother_nap_type}: ${escapeHtml(lcp.mother_nap)}
                        <div class="port-usage">Port ${lcp.pon_port}</div>
                    </td>
                    <td>${lcp.pon_port}</td>
                    <td>
                        ${escapeHtml(lcp.splitter_type)}
                        <div class="splitter-info">${lcp.total_ports} ports / ${lcp.loss.toFixed(1)} dB</div>
                    </td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${getProgressBarClass(portUsage)}" 
                                 role="progressbar" 
                                 style="width: ${portUsage}%"
                                 title="${lcp.used_ports}/${lcp.total_ports} ports used">
                                ${portUsage.toFixed(1)}%
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="loss-value">${calculateTotalLoss(lcp).toFixed(1)} dB</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info me-1" 
                                    onclick="viewLcpDetails(${lcp.id})" title="View Details">
                                <i class="bx bx-show"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning me-1" 
                                    onclick="editLcp(${lcp.id})" title="Edit LCP">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="deleteLcp(${lcp.id}, '${escapeHtml(lcp.name)}')" title="Delete LCP">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        // Update filter options
        updateFilterOptions(data.data);

    } catch (error) {
        console.error('Error loading LCP list:', error);
        showAlert('Error loading LCP list: ' + error.message, 'danger');
    }
}

// Calculate LCP status
function calculateLcpStatus(lcp) {
    const portUsage = (lcp.used_ports / lcp.total_ports) * 100;
    if (portUsage >= 90) {
        return { class: 'warning', description: 'Port usage above 90%' };
    } else if (portUsage > 0) {
        return { class: 'active', description: 'Active' };
    }
    return { class: 'inactive', description: 'No ports in use' };
}

// Get progress bar class based on usage
function getProgressBarClass(usage) {
    if (usage >= 90) return 'bg-danger';
    if (usage >= 75) return 'bg-warning';
    return 'bg-success';
}

// Calculate total loss for an LCP
function calculateTotalLoss(lcp) {
    let totalLoss = parseFloat(lcp.splitter_loss || 0);
    if (lcp.meters_lcp) {
        totalLoss += (lcp.meters_lcp / 1000) * 0.35; // Convert meters to km, then 0.35 dB/km typical fiber loss
    }
    return totalLoss;
}

// Update filter options based on available data
function updateFilterOptions(lcps) {
    const splitterTypeFilter = document.getElementById('filterSplitterType');
    const splitterTypes = new Set(lcps.map(lcp => lcp.splitter_type));
    
    splitterTypeFilter.innerHTML = '<option value="">All Splitter Types</option>';
    splitterTypes.forEach(type => {
        if (type) {
            splitterTypeFilter.innerHTML += `
                <option value="${escapeHtml(type)}">${escapeHtml(type)}</option>
            `;
        }
    });
}

// Load splitter types
async function loadSplitterTypes() {
    try {
        const response = await fetch('ftth_get_splitter_types.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load splitter types');
        }

        const splitterSelect = document.getElementById('splitterType');
        const editSplitterSelect = document.getElementById('editSplitterType');
        const options = '<option value="">Select Splitter Type</option>' + 
            data.data.map(type => `
                <option value="${type.id}" 
                        data-ports="${type.ports}"
                        data-loss="${type.loss}">
                    ${type.name} (${type.loss.toFixed(1)} dB loss)
                </option>
            `).join('');
        
        splitterSelect.innerHTML = options;
        editSplitterSelect.innerHTML = options;

        // Store splitter types for later use
        data.data.forEach(type => {
            splitterTypes[type.id] = {
                ports: type.ports,
                loss: type.loss
            };
        });

    } catch (error) {
        console.error('Error loading splitter types:', error);
        showAlert('Error loading splitter types: ' + error.message, 'danger');
    }
}

// Load available OLTs
async function loadAvailableOLTs() {
    try {
        const response = await fetch('ftth_get_olt_list.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load OLTs');
        }

        const oltSelect = document.getElementById('oltSelect');
        const editOltSelect = document.getElementById('editOltSelect');
        const options = '<option value="">Select OLT</option>' + 
            data.data.map(olt => `
                <option value="${olt.id}" 
                        data-pon-type="${olt.pon_type}"
                        data-tx-power="${olt.tx_power}"
                        data-total-ports="${olt.number_of_pons}"
                        data-available-ports="${olt.available_ports}"
                        data-available-pon-ports='${JSON.stringify(olt.available_pon_ports)}'>
                    ${escapeHtml(olt.name)} (${olt.available_ports} ports available)
                </option>
            `).join('');

        oltSelect.innerHTML = options;
        editOltSelect.innerHTML = options;

        // Add change event listeners
        [oltSelect, editOltSelect].forEach(select => {
            select.addEventListener('change', function() {
                const selectedOlt = this.options[this.selectedIndex];
                if (selectedOlt.value) {
                    // Update PON port dropdown
                    const ponPortSelect = this.id === 'oltSelect' ? 
                        document.getElementById('addOltPonPort') : 
                        document.getElementById('editOltPonPort');
                    ponPortSelect.innerHTML = '<option value="">Select PON Port</option>';
                    
                    const availablePorts = JSON.parse(selectedOlt.dataset.availablePonPorts);
                    availablePorts.forEach(port => {
                        ponPortSelect.innerHTML += `
                            <option value="${port}">Port ${port}</option>
                        `;
                    });

                    // Update port configuration info
                    const prefix = this.id === 'oltSelect' ? '' : 'edit';
                    document.getElementById(prefix + 'TotalPorts').textContent = selectedOlt.dataset.totalPorts;
                    document.getElementById(prefix + 'AvailablePorts').textContent = selectedOlt.dataset.availablePorts;
                    
                    // Update power budget calculation if splitter is selected
                    updateTotalLoss(prefix);
                }
            });
        });

    } catch (error) {
        console.error('Error loading OLTs:', error);
        showAlert('Error loading OLTs: ' + error.message, 'danger');
    }
}

// Load available LCPs for parent selection
async function loadAvailableLCPs() {
    try {
        const response = await fetch('ftth_get_lcp_list.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load LCPs');
        }

        const lcpSelect = document.getElementById('parentLcpSelect');
        const editLcpSelect = document.getElementById('editParentLcpSelect');
        
        [lcpSelect, editLcpSelect].forEach(select => {
            select.innerHTML = '<option value="">Select Parent LCP</option>';
            
            data.data.forEach(lcp => {
                // Don't show LCP as potential parent if it's being edited
                if (currentLcpId && lcp.id === currentLcpId) return;
                
                const usedPorts = lcp.used_ports || 0;
                const availablePorts = lcp.total_ports - usedPorts;
                
                if (availablePorts > 0) {
                    select.innerHTML += `
                        <option value="${lcp.id}"
                                data-total-ports="${lcp.total_ports}"
                                data-used-ports="${usedPorts}"
                                data-available-ports="${availablePorts}">
                            ${escapeHtml(lcp.name)} (${availablePorts} ports available)
                        </option>
                    `;
                }
            });

            // Add change event listener
            select.addEventListener('change', function() {
                const selectedLcp = this.options[this.selectedIndex];
                if (selectedLcp.value) {
                    // Update PON port dropdown
                    const ponPortSelect = this.id === 'parentLcpSelect' ?
                        document.getElementById('addLcpPonPort') :
                        document.getElementById('editLcpPonPort');
                    ponPortSelect.innerHTML = '<option value="">Select Port</option>';
                    
                    const totalPorts = parseInt(selectedLcp.dataset.totalPorts);
                    
                    for (let i = 1; i <= totalPorts; i++) {
                        ponPortSelect.innerHTML += `
                            <option value="${i}">Port ${i}</option>
                        `;
                    }

                    // Update port configuration info
                    const prefix = this.id === 'parentLcpSelect' ? '' : 'edit';
                    document.getElementById(prefix + 'TotalPorts').textContent = selectedLcp.dataset.totalPorts;
                    document.getElementById(prefix + 'AvailablePorts').textContent = selectedLcp.dataset.availablePorts;
                }
            });
        });

    } catch (error) {
        console.error('Error loading LCPs:', error);
        showAlert('Error loading LCPs: ' + error.message, 'danger');
    }
}

// Update total loss calculation
function updateTotalLoss(prefix = '') {
    const oltSelect = document.getElementById(prefix + 'oltSelect');
    const splitterSelect = document.getElementById(prefix + 'splitterType');
    const selectedOlt = oltSelect.options[oltSelect.selectedIndex];
    const selectedSplitter = splitterSelect.options[splitterSelect.selectedIndex];

    if (selectedOlt && selectedOlt.value && selectedSplitter && selectedSplitter.value) {
        const txPower = parseFloat(selectedOlt.dataset.txPower);
        const splitterLoss = parseFloat(selectedSplitter.dataset.loss);
        const form = document.getElementById(prefix ? 'editLcpForm' : 'addLcpForm');
        const metersLcp = parseFloat(form.querySelector('[name="meters_lcp"]').value || 0);
        const fiberLoss = (metersLcp / 1000) * 0.35; // Convert meters to km, then 0.35 dB/km typical fiber loss
        const totalLoss = splitterLoss + fiberLoss;
        
        document.getElementById(prefix + 'TotalLoss').textContent = totalLoss.toFixed(1);
        
        // Calculate power budget
        const receiverSensitivity = -28; // dBm (typical value)
        const marginRequired = 3; // dB (safety margin)
        const availablePowerBudget = txPower - receiverSensitivity - totalLoss - marginRequired;
        
        // Update power budget status
        const powerBudgetStatus = document.getElementById(prefix + 'PowerBudgetStatus');
        if (availablePowerBudget >= 0) {
            powerBudgetStatus.className = 'text-success';
            powerBudgetStatus.textContent = `Power budget OK (${availablePowerBudget.toFixed(1)} dB margin)`;
        } else {
            powerBudgetStatus.className = 'text-danger';
            powerBudgetStatus.textContent = `Insufficient power budget (${Math.abs(availablePowerBudget).toFixed(1)} dB deficit)`;
        }
    }
}

// Setup event listeners
function setupEventListeners() {
    // Connection type handlers
    ['connectionType', 'editConnectionType'].forEach(id => {
        document.getElementById(id).addEventListener('change', function(e) {
            const prefix = id === 'connectionType' ? '' : 'edit';
            const oltFields = document.getElementById(prefix + 'oltConnectionFields');
            const lcpFields = document.getElementById(prefix + 'lcpConnectionFields');
            
            if (e.target.value === 'OLT') {
                oltFields.style.display = 'block';
                lcpFields.style.display = 'none';
                loadAvailableOLTs();
            } else if (e.target.value === 'LCP') {
                oltFields.style.display = 'none';
                lcpFields.style.display = 'block';
                loadAvailableLCPs();
            } else {
                oltFields.style.display = 'none';
                lcpFields.style.display = 'none';
            }
        });
    });

    // Splitter type and fiber length change handlers
    ['splitterType', 'editSplitterType'].forEach(id => {
        const element = document.getElementById(id);
        const prefix = id === 'splitterType' ? '' : 'edit';
        const form = document.getElementById(prefix ? 'editLcpForm' : 'addLcpForm');
        
        // Splitter type change
        element.addEventListener('change', function() {
            const selectedType = this.options[this.selectedIndex];
            if (selectedType.value) {
                const ports = selectedType.dataset.ports;
                const loss = selectedType.dataset.loss;
                
                document.getElementById(prefix + 'TotalPorts').textContent = ports;
                document.getElementById(prefix + 'SplitterLoss').textContent = parseFloat(loss).toFixed(1);
                
                updateTotalLoss(prefix);
            }
        });

        // Fiber length change
        const metersInput = form.querySelector('[name="meters_lcp"]');
        if (metersInput) {
            metersInput.addEventListener('input', () => updateTotalLoss(prefix));
        }
    });

    // Filter handlers
    document.getElementById('filterSplitterType').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterSearch').addEventListener('input', applyFilters);
}

// Apply filters to LCP list
function applyFilters() {
    const splitterType = document.getElementById('filterSplitterType').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();
    
    const rows = document.getElementById('lcpTableBody').getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        let show = true;
        
        // Check splitter type filter
        if (splitterType) {
            const rowSplitterType = row.cells[4]?.textContent.trim();
            show = show && rowSplitterType.includes(splitterType);
        }
        
        // Check status filter
        if (status) {
            const statusSpan = row.querySelector('.lcp-status');
            show = show && statusSpan?.classList.contains(status);
        }
        
        // Check search filter
        if (search) {
            const rowText = row.textContent.toLowerCase();
            show = show && rowText.includes(search);
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// Setup form validation
function setupFormValidation() {
    const forms = document.querySelectorAll('#addLcpForm, #editLcpForm');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset previous validation states
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });

            let isValid = true;
            const errors = {};

            // Validate LCP Name
            const nameInput = form.querySelector('[name="name"]');
            if (nameInput) {
                if (!nameInput.value.trim()) {
                    errors.name = 'LCP name is required';
                    isValid = false;
                } else if (!/^[a-zA-Z0-9-_]+$/.test(nameInput.value)) {
                    errors.name = 'LCP name can only contain letters, numbers, hyphens, and underscores';
                    isValid = false;
                }
            }

            // Validate Connection Type
            const connectionType = form.querySelector('[name="connection_type"]');
            if (connectionType) {
                if (!connectionType.value) {
                    errors.connection_type = 'Connection type is required';
                    isValid = false;
                } else {
                    // Validate OLT selection
                    if (connectionType.value === 'OLT') {
                        const oltSelect = form.querySelector('#oltSelect');
                        if (!oltSelect.value) {
                            errors.olt = 'OLT selection is required';
                            isValid = false;
                        }
                    }
                    // Validate Parent LCP selection
                    else if (connectionType.value === 'LCP') {
                        const lcpSelect = form.querySelector('#parentLcpSelect');
                        if (!lcpSelect.value) {
                            errors.parent_lcp = 'Parent LCP selection is required';
                            isValid = false;
                        }
                    }
                }
            }

            // Validate Splitter Type
            const splitterType = form.querySelector('[name="splitter_type"]');
            if (splitterType && !splitterType.value) {
                errors.splitter_type = 'Splitter type is required';
                isValid = false;
            }

            // Validate PON Port
            const ponPort = form.querySelector('[name="pon_port"]');
            if (ponPort) {
                if (!ponPort.value) {
                    errors.pon_port = 'PON port is required';
                    isValid = false;
                } else {
                    const portNum = parseInt(ponPort.value);
                    if (isNaN(portNum) || portNum < 1) {
                        errors.pon_port = 'Invalid PON port number';
                        isValid = false;
                    }
                }
            }

            // Display validation errors
            if (!isValid) {
                Object.keys(errors).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.textContent = errors[field];
                        }
                    }
                });
                return;
            }

            // If validation passes, proceed with form submission
            handleFormSubmit.call(form, e);
        });
    });
}

// Handle form submission after validation
async function handleFormSubmit(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const isEdit = form.id === 'editLcpForm';
    const url = isEdit ? 'ftth_edit_lcp.php' : 'ftth_add_lcp.php';

    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bx bx-loader bx-spin"></i> Processing...';

    try {
        // Add connection type to formData
        const connectionType = form.querySelector('[name="connection_type"]').value;
        formData.append('connection_type', connectionType);

        // Add the appropriate ID based on connection type
        if (connectionType === 'OLT') {
            formData.append('olt_id', form.querySelector('#oltSelect').value);
        } else if (connectionType === 'LCP') {
            formData.append('parent_lcp_id', form.querySelector('#parentLcpSelect').value);
        }

        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(`LCP ${isEdit ? 'updated' : 'added'} successfully`);
            
            // Close modal
            const modalId = isEdit ? 'editLcpModal' : 'addLcpModal';
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById(modalId));
            if (modalInstance) {
                modalInstance.hide();
            }

            // Reset form and reload data
            form.reset();
            loadLCPList();

            // If editing, reload the details view if it's open
            if (isEdit && currentLcpId) {
                viewLcpDetails(currentLcpId);
            }
        } else {
            // Handle specific error cases
            if (data.error === 'duplicate_name') {
                const nameInput = form.querySelector('[name="name"]');
                nameInput.classList.add('is-invalid');
                const feedback = nameInput.nextElementSibling;
                if (feedback) {
                    feedback.textContent = 'This LCP name already exists';
                }
            } else if (data.error === 'port_in_use') {
                const portInput = form.querySelector('[name="pon_port"]');
                portInput.classList.add('is-invalid');
                const feedback = portInput.nextElementSibling;
                if (feedback) {
                    feedback.textContent = 'This port is already in use';
                }
            } else {
                throw new Error(data.error || `Failed to ${isEdit ? 'update' : 'add'} LCP`);
            }
        }
    } catch (error) {
        console.error(`Error ${isEdit ? 'updating' : 'adding'} LCP:`, error);
        showAlert(error.message, 'danger');
    } finally {
        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

// View LCP details
async function viewLcpDetails(lcpId) {
    try {
        currentLcpId = lcpId;
        const response = await fetch(`ftth_get_lcp_details.php?id=${lcpId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load LCP details');
        }

        const lcp = data.data;
        
        // Populate basic information
        document.getElementById('viewLcpName').textContent = lcp.name;
        document.getElementById('viewLcpStatus').innerHTML = `
            <span class="badge ${lcp.port_usage >= 90 ? 'bg-danger' : 
                               lcp.port_usage > 0 ? 'bg-success' : 
                               'bg-secondary'}">
                ${lcp.port_usage >= 90 ? 'Critical' : 
                  lcp.port_usage > 0 ? 'Active' : 
                  'Inactive'}
            </span>`;
        document.getElementById('viewLcpConnection').textContent = 
            `${lcp.mother_nap_type}: ${lcp.mother_nap} (Port ${lcp.pon_port})`;
        
        // Populate technical details
        document.getElementById('viewLcpSplitterType').textContent = 
            `${lcp.splitter_name} (${lcp.total_ports} ports)`;
        document.getElementById('viewLcpTotalPorts').textContent = lcp.total_ports;
        document.getElementById('viewLcpUsedPorts').textContent = 
            `${lcp.used_ports} / ${lcp.total_ports} (${lcp.port_usage.toFixed(1)}%)`;
        
        // Calculate and display power budget
        const totalLoss = calculateTotalLoss(lcp);
        document.getElementById('viewLcpTotalLoss').innerHTML = `
            ${totalLoss.toFixed(1)} dB
            <small class="text-muted">
                (Splitter: ${lcp.splitter_loss} dB, 
                Fiber: ${((lcp.meters_lcp || 0) / 1000 * 0.35).toFixed(1)} dB)
            </small>`;
        
        // Populate NAP table
        const napTableBody = document.getElementById('viewLcpNapTable')
            .getElementsByTagName('tbody')[0];
        napTableBody.innerHTML = '';
        
        if (lcp.naps && lcp.naps.length > 0) {
            lcp.naps.forEach(nap => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(nap.name)}</td>
                    <td>${nap.port_no}</td>
                    <td>
                        <span class="badge ${
                            nap.client_count >= nap.port_count ? 'bg-danger' :
                            nap.client_count > 0 ? 'bg-success' : 'bg-secondary'
                        }">
                            ${nap.client_count}/${nap.port_count} clients
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info" 
                                onclick="viewNapDetails(${nap.id})">
                            <i class="bx bx-show"></i> View Clients
                        </button>
                    </td>
                `;
                napTableBody.appendChild(row);
            });
        } else {
            napTableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">No NAP boxes connected</td>
                </tr>`;
        }

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('viewLcpModal'));
        modal.show();

    } catch (error) {
        console.error('Error viewing LCP details:', error);
        showAlert('Error loading LCP details: ' + error.message, 'danger');
    }
}

// Edit LCP
async function editLcp(lcpId) {
    try {
        currentLcpId = lcpId;
        const response = await fetch(`ftth_get_lcp_details.php?id=${lcpId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load LCP details');
        }

        const lcp = data.data;
        const form = document.getElementById('editLcpForm');
        
        // Populate form fields
        form.querySelector('[name="id"]').value = lcp.id;
        form.querySelector('[name="name"]').value = lcp.name;
        form.querySelector('[name="connection_type"]').value = lcp.mother_nap_type;
        
        // Trigger connection type change to load appropriate fields
        const event = new Event('change');
        form.querySelector('[name="connection_type"]').dispatchEvent(event);
        
        // Wait for dropdowns to be populated
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Set values after dropdowns are loaded
        if (lcp.mother_nap_type === 'OLT') {
            form.querySelector('#editOltSelect').value = lcp.mother_nap_id;
            form.querySelector('#editOltSelect').dispatchEvent(new Event('change'));
        } else {
            form.querySelector('#editParentLcpSelect').value = lcp.mother_nap_id;
            form.querySelector('#editParentLcpSelect').dispatchEvent(new Event('change'));
        }
        
        form.querySelector('#editSplitterType').value = lcp.splitter_type;
        form.querySelector('#editSplitterType').dispatchEvent(new Event('change'));
        
        form.querySelector('[name="pon_port"]').value = lcp.pon_port;
        form.querySelector('[name="meters_lcp"]').value = lcp.meters_lcp || 0;
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('editLcpModal'));
        modal.show();

    } catch (error) {
        console.error('Error loading LCP for editing:', error);
        showAlert('Error loading LCP details: ' + error.message, 'danger');
    }
}

// Delete LCP
async function deleteLcp(lcpId, lcpName) {
    if (!confirm(`Are you sure you want to delete LCP "${lcpName}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch('ftth_delete_lcp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: lcpId })
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to delete LCP');
        }

        showAlert(`LCP "${lcpName}" has been deleted successfully.`, 'success');
        loadLCPList();
    } catch (error) {
        console.error('Error deleting LCP:', error);
        showAlert('Error deleting LCP: ' + error.message, 'danger');
    }
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Show alert message
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
        
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }
}
