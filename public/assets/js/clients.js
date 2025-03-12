/**
 * JavaScript functions for client management
 */

// View client details
function viewClient(id) {
    fetch(`/api/clients.php?id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken,
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const client = data.client;
            // Redirect to view page
            window.location.href = `/forms/clients/view.php?id=${client.id}`;
        } else {
            alert('Failed to load client details: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load client details.');
    });
}

// Edit client details
function editClient(id) {
    // Redirect to edit page
    window.location.href = `/forms/clients/edit.php?id=${id}`;
}

// Delete client function has been replaced with a server-side delete.php page

// Save client (create or update)
function saveClient(formId, isEdit = false) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const clientId = formData.get('id');
    const method = isEdit ? 'PUT' : 'POST';
    const url = `/api/clients.php${isEdit ? `?id=${clientId}` : ''}`;

    // Convert form data to JSON object
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken,
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Client saved successfully!');
            // Redirect to clients list
            window.location.href = '/clients.php';
        } else {
            alert('Failed to save client: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save client.');
    });

    return false; // Prevent form submission
}

// Clear search form
function clearSearch() {
    document.getElementById('search').value = '';
    document.getElementById('status').value = '';
    document.getElementById('filterForm').submit();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for client form if it exists
    const clientForm = document.getElementById('clientForm');
    if (clientForm) {
        clientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const isEdit = clientForm.getAttribute('data-edit') === 'true';
            saveClient('clientForm', isEdit);
        });
    }
});