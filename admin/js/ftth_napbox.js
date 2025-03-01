// Function to load NAP box list
async function loadNAPBoxList() {
    const tbody = document.getElementById('napboxTableBody');
    if (!tbody) {
        console.error('NAP box table body element not found');
        return;
    }

    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';

    try {
        const response = await fetch('ftth_get_napbox_list.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load NAP boxes');
        }

        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No NAP boxes found</td></tr>';
            return;
        }
        
        data.data.forEach(napbox => {
            // Format connection info
            let connectionInfo = 'Not Connected';
            if (napbox.mother_nap_type === 'OLT' && napbox.mother_nap_name) {
                connectionInfo = napbox.mother_nap_name;
            } else if (napbox.mother_nap_type === 'LCP' && napbox.mother_nap_name) {
                connectionInfo = napbox.mother_nap_name;
            }

            const row = `
                <tr>
                    <td>${napbox.name || 'N/A'}</td>
                    <td>${connectionInfo}</td>
                    <td>${napbox.port_count || 0} ports</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info edit-napbox" 
                                    data-id="${napbox.id}"
                                    data-name="${napbox.name}"
                                    data-type="${napbox.mother_nap_type}"
                                    data-connection="${napbox.mother_nap || ''}"
                                    data-count="${napbox.port_count || 0}"
                                    title="Edit NAP Box">
                                <i class="bx bx-edit"></i>
                            </button>
                            <a href="ftth_delete_napbox.php?id=${napbox.id}" 
                               class="btn btn-sm btn-danger delete-napbox" 
                               title="Delete NAP Box"
                               onclick="return confirm('Are you sure you want to delete this NAP Box? This action cannot be undone.');">
                                <i class="bx bx-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        // Add event listeners to the edit buttons
        document.querySelectorAll('.edit-napbox').forEach(button => {
            button.addEventListener('click', function() {
                const data = this.dataset;
                populateNapboxEditModal(
                    data.id,
                    data.name,
                    data.type,
                    data.connection,
                    data.count
                );
                const modal = new bootstrap.Modal(document.getElementById('editNapboxModal'));
                modal.show();
            });
        });

    } catch (error) {
        console.error('Error loading NAP box list:', error);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">
            Error loading NAP box list: ${error.message}
        </td></tr>`;
    }
}

// Function to load connection options
async function loadConnectionOptions(isEdit = false) {
    const typeId = isEdit ? 'editConnectionType' : 'connectionType';
    const connectionId = isEdit ? 'editConnectionId' : 'connectionId';
    
    const typeSelect = document.getElementById(typeId);
    const select = document.getElementById(connectionId);
    
    if (!typeSelect || !select) {
        console.error('Required elements not found');
        return;
    }
    
    const type = typeSelect.value;
    select.innerHTML = '<option value="">Loading...</option>';

    try {
        let endpoint = '';
        switch(type) {
            case 'OLT':
                endpoint = 'ftth_get_olt_list.php';
                break;
            case 'LCP':
                endpoint = 'ftth_get_lcp_list.php';
                break;
            default:
                select.innerHTML = '<option value="">Select Type First</option>';
                return;
        }

        const response = await fetch(endpoint);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load options');
        }

        select.innerHTML = '<option value="">Select Connection</option>';
        data.data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });

    } catch (error) {
        console.error('Error loading options:', error);
        select.innerHTML = '<option value="">Error loading options</option>';
        alert('Error loading connection options. Please try again.');
    }
}

// Function to populate NAP box edit modal
async function populateNapboxEditModal(id, name, connectionType, connectionId, portCount) {
    try {
        const elements = {
            id: document.getElementById('editNapboxId'),
            name: document.getElementById('editNapboxName'),
            count: document.getElementById('editPortCount'),
            type: document.getElementById('editConnectionType'),
            connection: document.getElementById('editConnectionId')
        };

        // Set initial form values
        elements.id.value = id;
        elements.name.value = name;
        elements.count.value = portCount;
        elements.type.value = connectionType;

        // Load connection options
        await loadConnectionOptions(true);

        // Set connection value after options are loaded
        if (connectionId) {
            elements.connection.value = connectionId;
        }
    } catch (error) {
        console.error('Error populating NAP box edit modal:', error);
        alert(`Error loading form data: ${error.message}`);
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadNAPBoxList();
    
    // Add event listeners for connection type changes
    const connectionType = document.getElementById('connectionType');
    const editConnectionType = document.getElementById('editConnectionType');

    connectionType?.addEventListener('change', function() {
        loadConnectionOptions(false);
    });

    editConnectionType?.addEventListener('change', function() {
        loadConnectionOptions(true);
    });

    // Add form validation
    const addForm = document.getElementById('addNapboxForm');
    const editForm = document.getElementById('editNapboxForm');

    [addForm, editForm].forEach(form => {
        form?.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
