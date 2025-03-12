// Function to view subscription details
window.viewSubscription = function(subscriptionId) {
    fetch(`api/subscriptions.php?id=${subscriptionId}`, {
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
            const subscription = data.subscription;
            // Populate the view subscription modal with the subscription data
            document.getElementById('viewSubscriptionModalTitle').innerText = `Subscription #${subscription.subscription_number}`;
            document.getElementById('viewSubscriptionModalBody').innerHTML = `
                <p>Client: ${subscription.first_name} ${subscription.last_name}</p>
                <p>Plan: ${subscription.plan_name} (${subscription.speed_mbps} Mbps)</p>
                <p>Status: ${subscription.status}</p>
                <p>Start Date: ${subscription.start_date}</p>
                <p>Billing Cycle: ${subscription.billing_cycle}</p>
            `;
            // Show the view subscription modal
            const viewSubscriptionModal = new bootstrap.Modal(document.getElementById('viewSubscriptionModal'));
            viewSubscriptionModal.show();
        } else {
            alert('Failed to load subscription details: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load subscription details.');
    });
};

// Function to edit subscription details
window.editSubscription = function(subscriptionId) {
    fetch(`api/subscriptions.php?id=${subscriptionId}`, {
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
            const subscription = data.subscription;
            // Populate the edit subscription modal with the subscription data
            document.getElementById('subscriptionId').value = subscription.id;
            document.getElementById('clientId').value = subscription.client_id;
            document.getElementById('planId').value = subscription.plan_id;
            document.getElementById('ipAddress').value = subscription.ip_address || '';
            
            // Format dates properly
            if (subscription.start_date) {
                let startDate = subscription.start_date;
                if (startDate.includes('T')) {
                    startDate = startDate.split('T')[0];
                } else if (startDate.includes(' ')) {
                    startDate = startDate.split(' ')[0];
                }
                document.getElementById('startDate').value = startDate;
            }
            
            if (subscription.end_date) {
                let endDate = subscription.end_date;
                if (endDate.includes('T')) {
                    endDate = endDate.split('T')[0];
                } else if (endDate.includes(' ')) {
                    endDate = endDate.split(' ')[0];
                }
                document.getElementById('endDate').value = endDate;
            } else {
                document.getElementById('endDate').value = '';
            }
            
            document.getElementById('billingCycle').value = subscription.billing_cycle;
            document.getElementById('status').value = subscription.status;

            // Update modal title
            document.querySelector('#subscriptionModal .modal-title').textContent = 'Edit Subscription';
            
            // Show the edit subscription modal
            const subscriptionModal = new bootstrap.Modal(document.getElementById('subscriptionModal'));
            subscriptionModal.show();
        } else {
            alert('Failed to load subscription details for editing: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load subscription details for editing.');
    });
};

// Function to save subscription
window.saveSubscription = function() {
    const form = document.getElementById('subscriptionForm');
    const formData = new FormData(form);
    const subscriptionId = formData.get('id');
    const method = subscriptionId ? 'PUT' : 'POST';
    const url = `api/subscriptions.php${subscriptionId ? `?id=${subscriptionId}` : ''}`;

    // Convert form data to JSON object
    const data = {};
    formData.forEach((value, key) => {
        // Handle empty strings as null for specific fields
        if (['ip_address', 'end_date'].includes(key) && value.trim() === '') {
            data[key] = null;
        } else {
            data[key] = value;
        }
    });

    console.log('Sending subscription data:', data);

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
            alert('Subscription saved successfully!');
            const subscriptionModal = bootstrap.Modal.getInstance(document.getElementById('subscriptionModal'));
            subscriptionModal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Failed to save subscription: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save subscription.');
    });
};

// Delete subscription function has been replaced with a server-side delete.php page

// Clear search
window.clearSearch = function() {
    document.getElementById('search').value = '';
    document.getElementById('filterForm').submit();
};

// Initialize modals when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Make sure Bootstrap is properly loaded
    if (typeof bootstrap !== 'undefined') {
        // Initialize all modals
        const subscriptionModalEl = document.getElementById('subscriptionModal');
        if (subscriptionModalEl) {
            window.subscriptionModal = new bootstrap.Modal(subscriptionModalEl);
        }
        
        const viewSubscriptionModalEl = document.getElementById('viewSubscriptionModal');
        if (viewSubscriptionModalEl) {
            window.viewSubscriptionModal = new bootstrap.Modal(viewSubscriptionModalEl);
        }
    } else {
        console.error('Bootstrap is not loaded properly');
    }
});