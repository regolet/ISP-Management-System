// Customer Portal Management JavaScript

// Initialize tooltips and event listeners
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle search input
    $('#searchCustomer').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Handle status filter
    $('#statusFilter').on('change', function() {
        const value = $(this).val().toLowerCase();
        if (value === '') {
            $('table tbody tr').show();
        } else {
            $('table tbody tr').each(function() {
                const status = $(this).find('td:nth-child(5)').text().toLowerCase();
                $(this).toggle(status.includes(value));
            });
        }
    });

    // Handle plan filter
    $('#planFilter').on('change', function() {
        const value = $(this).val().toLowerCase();
        if (value === '') {
            $('table tbody tr').show();
        } else {
            $('table tbody tr').each(function() {
                const plan = $(this).find('td:nth-child(3)').text().toLowerCase();
                $(this).toggle(plan === value);
            });
        }
    });

    // Generate random password
    $('#generatePassword').on('click', function() {
        $.get('generate_password.php', function(password) {
            $('#portal-password').val(password);
        });
    });

    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordInput = $('#portal-password');
        const type = passwordInput.attr('type');
        passwordInput.attr('type', type === 'password' ? 'text' : 'password');
        $(this).find('i').toggleClass('bx-show bx-hide');
    });

    // Handle portal access form submission
    $('#portalAccessForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'update_portal_access.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    showAlert('success', 'Portal access updated successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error updating portal access');
                }
            },
            error: function() {
                showAlert('danger', 'Error updating portal access');
            }
        });
    });

    // Handle view all invoices click
    $('#view-all-invoices').on('click', function(e) {
        e.preventDefault();
        const customerId = $('#customer_id').val();
        window.location.href = `billing.php?customer_id=${customerId}`;
    });

    // Handle edit customer button click
    $('#edit-customer-btn').on('click', function(e) {
        e.preventDefault();
        const customerId = $('#customer_id').val();
        window.location.href = `customer_form.php?id=${customerId}`;
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize view customer buttons
    document.querySelectorAll('.view-customer-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const customerId = this.getAttribute('data-id');
            viewCustomerDetails(customerId);
        });
    });

    // Initialize portal access toggle buttons
    document.querySelectorAll('.toggle-portal-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const customerId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            togglePortalAccess(customerId, currentStatus);
        });
    });
});

function viewCustomerDetails(customerId) {
    fetch(`get_customer_details.php?id=${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const customer = data.customer;
                
                // Update modal content
                document.getElementById('view-customer-name').textContent = customer.name;
                
                // Update customer info tab
                const customerInfo = {
                    'customerName': customer.name,
                    'customerAddress': customer.address,
                    'customerContact': customer.contact || 'N/A',
                    'customerPlan': customer.plan_name,
                    'customerBalance': `₱${parseFloat(customer.balance).toFixed(2)}`,
                    'customerStatus': customer.status.toUpperCase(),
                    'customerDueDate': customer.due_date
                };
                
                // Update each field
                Object.keys(customerInfo).forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = customerInfo[id];
                    }
                });
                
                // Update status badge
                const statusElement = document.getElementById('customerStatus');
                if (statusElement) {
                    statusElement.className = `badge ${getStatusBadgeClass(customer.status)}`;
                }
                
                // Update billing history
                const billingList = document.getElementById('billingList');
                if (billingList) {
                    billingList.innerHTML = '';
                    if (data.billing && data.billing.length > 0) {
                        data.billing.forEach(bill => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item d-flex justify-content-between align-items-center';
                            li.innerHTML = `
                                <div>
                                    <strong>Invoice #${bill.invoiceid}</strong><br>
                                    <small class="text-muted">Due: ${bill.due_date}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge ${getBillingStatusBadgeClass(bill.status)}">${bill.status.toUpperCase()}</span><br>
                                    <strong>₱${parseFloat(bill.amount).toFixed(2)}</strong>
                                </div>
                            `;
                            billingList.appendChild(li);
                        });
                    } else {
                        billingList.innerHTML = '<li class="list-group-item text-center">No billing history available</li>';
                    }
                }
                
                // Update activity list
                const activityList = document.getElementById('activityList');
                if (activityList) {
                    activityList.innerHTML = '';
                    if (data.activity && data.activity.length > 0) {
                        data.activity.forEach(activity => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            li.innerHTML = `
                                <div class="d-flex justify-content-between">
                                    <strong>${activity.type}</strong>
                                    <small class="text-muted">${activity.created_at}</small>
                                </div>
                                <div>${activity.description}</div>
                            `;
                            activityList.appendChild(li);
                        });
                    } else {
                        activityList.innerHTML = '<li class="list-group-item text-center">No recent activity</li>';
                    }
                }
                
                // Show the modal
                const viewCustomerModal = document.getElementById('viewCustomerModal');
                if (viewCustomerModal) {
                    const modal = new bootstrap.Modal(viewCustomerModal);
                    modal.show();
                }
            } else {
                alert('Error loading customer details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading customer details. Please try again.');
        });
}

function togglePortalAccess(customerId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    
    fetch('toggle_portal_access.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `customer_id=${customerId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating portal access: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating portal access. Please try again.');
    });
}

function getStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'bg-success';
        case 'unpaid':
            return 'bg-warning text-dark';
        case 'overdue':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getBillingStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'bg-success';
        case 'pending':
            return 'bg-warning text-dark';
        case 'overdue':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// View customer details
function viewCustomer(id) {
    $.ajax({
        url: 'get_customer_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            // Update modal title
            $('#view-customer-name').text(data.customer.name);
            
            // Update customer info tab
            $('#view-name').text(data.customer.name);
            $('#view-address').text(data.customer.address);
            $('#view-contact').text(data.customer.contact);
            $('#view-balance').text('₱' + parseFloat(data.customer.balance).toFixed(2));
            $('#view-status').html(`<span class="badge bg-${getStatusBadgeClass(data.customer.status)}">${data.customer.status}</span>`);
            
            // Update plan info
            $('#view-plan').text(data.plan.name);
            $('#view-fee').text('₱' + parseFloat(data.plan.amount).toFixed(2));
            $('#view-due-date').text(data.customer.due_date);
            
            // Update billing records
            updateBillingRecords(data.billing || []);
            
            // Update activity timeline
            updateActivityTimeline(data.activity || []);
            
            // Update portal access form
            $('#customer_id').val(data.customer.id);
            if (data.user) {
                $('#portal-username').val(data.user.username);
                $('#portal-email').val(data.user.email);
                $('#portal-status').val(data.user.status);
            } else {
                $('#portal-username').val('');
                $('#portal-email').val('');
                $('#portal-status').val('active');
            }
            $('#portal-password').val('');
            
            // Show the modal
            $('#viewCustomerModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            showAlert('danger', 'Error fetching customer details: ' + error);
        }
    });
}

// Update billing records table
function updateBillingRecords(records) {
    const tbody = $('#billing-records');
    tbody.empty();
    
    if (records.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center">No billing records found</td>
            </tr>
        `);
        return;
    }
    
    records.forEach(record => {
        tbody.append(`
            <tr>
                <td>${record.invoiceid}</td>
                <td>${formatDate(record.created_at)}</td>
                <td>₱${parseFloat(record.amount).toFixed(2)}</td>
                <td>
                    <span class="badge bg-${getBillingStatusBadgeClass(record.status)}">
                        ${record.status}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="view_invoice.php?id=${record.id}" class="btn btn-info" title="View Invoice">
                            <i class="bx bx-show"></i>
                        </a>
                        <a href="print_invoice.php?id=${record.id}" class="btn btn-secondary" title="Print Invoice">
                            <i class="bx bx-printer"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `);
    });
}

// Update activity timeline
function updateActivityTimeline(activities) {
    const timeline = $('#activity-timeline');
    timeline.empty();
    
    if (activities.length === 0) {
        timeline.append(`
            <div class="text-center text-muted">
                No activity records found
            </div>
        `);
        return;
    }
    
    activities.forEach(activity => {
        timeline.append(`
            <div class="timeline-item">
                <div class="timeline-time text-muted">
                    ${formatDate(activity.created_at)}
                </div>
                <div class="timeline-content">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-info me-2">
                            <i class="bx bx-${getActivityIcon(activity.type)}"></i>
                            ${activity.type}
                        </span>
                    </div>
                    <p class="mb-0">${activity.description}</p>
                </div>
            </div>
        `);
    });
}

// Delete customer
function deleteCustomer(id) {
    if (confirm('Are you sure you want to delete this customer?')) {
        $.ajax({
            url: 'delete_customer.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    showAlert('success', 'Customer deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error deleting customer');
                }
            },
            error: function() {
                showAlert('danger', 'Error deleting customer');
            }
        });
    }
}

// Helper function to get status badge class
function getStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'success';
        case 'unpaid':
            return 'warning';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Helper function to get billing status badge class
function getBillingStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warning';
        case 'overdue':
            return 'danger';
        case 'cancelled':
            return 'secondary';
        default:
            return 'info';
    }
}

// Helper function to get activity icon
function getActivityIcon(type) {
    switch (type.toLowerCase()) {
        case 'login':
            return 'log-in';
        case 'payment':
            return 'credit-card';
        case 'update':
            return 'edit';
        case 'portal_access':
            return 'lock-alt';
        default:
            return 'info-circle';
    }
}

// Helper function to format date
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert').remove();
    
    // Add new alert
    $('.content-wrapper').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => $('.alert').alert('close'), 5000);
}
