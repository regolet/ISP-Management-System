// Initialize Bootstrap tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Handle form validation
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('form.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format date
function formatDate(dateString, includeTime = false) {
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...(includeTime && {
            hour: '2-digit',
            minute: '2-digit'
        })
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Format number with commas
function formatNumber(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

// Handle file input display
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var label = e.target.nextElementSibling;
            label.textContent = fileName;
        });
    });
});

// Handle dynamic form fields
function addFormField(containerId, template) {
    const container = document.getElementById(containerId);
    const newRow = template.cloneNode(true);
    newRow.style.display = 'block';
    container.appendChild(newRow);
}

function removeFormField(button) {
    button.closest('.form-row').remove();
}

// Handle AJAX form submission
function submitFormAjax(form, successCallback) {
    const formData = new FormData(form);
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) successCallback(data);
        } else {
            showAlert('error', data.error || 'An error occurred');
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred while processing your request');
        console.error('Error:', error);
    });
}

// Show alert message
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class='bx bx-${type === 'success' ? 'check-circle' : 'error-circle'}'></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').insertBefore(
        alertDiv,
        document.querySelector('.container-fluid').firstChild
    );
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}
