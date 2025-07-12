/**
 * Optimized JavaScript for ISP Management System
 * Consolidated common functionality for better performance
 */

// Global configuration
const APP_CONFIG = {
    apiBase: '/api',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    debug: false
};

// Utility functions
const Utils = {
    // Show toast notification
    showToast: function(type, message, duration = 5000) {
        const toastContainer = document.querySelector('.toast-container') || 
                             document.createElement('div');
        
        if (!document.querySelector('.toast-container')) {
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast show bg-${type} text-white`;
        toast.innerHTML = `
            <div class="toast-body">
                ${message}
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Auto remove after duration
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, duration);

        return toast;
    },

    // Clear search functionality
    clearSearch: function(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            input.form?.submit();
        }
    },

    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    // Format date
    formatDate: function(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Get status color class
    getStatusColor: function(status) {
        const statusMap = {
            'active': 'success',
            'maintenance': 'warning',
            'offline': 'danger',
            'pending': 'secondary'
        };
        return statusMap[status?.toLowerCase()] || 'secondary';
    },

    // Debounce function for search inputs
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Loading spinner
    showLoading: function(element, text = 'Loading...') {
        if (element) {
            element.innerHTML = `
                <div class="text-center p-3">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">${text}</p>
                </div>
            `;
        }
    },

    // Hide loading spinner
    hideLoading: function(element) {
        if (element && element.querySelector('.spinner-border')) {
            element.innerHTML = '';
        }
    }
};

// API helper functions
const API = {
    // Make API request with error handling
    request: async function(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (APP_CONFIG.csrfToken) {
            defaultOptions.headers['X-CSRF-TOKEN'] = APP_CONFIG.csrfToken;
        }

        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },

    // GET request
    get: function(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(fullUrl);
    },

    // POST request
    post: function(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    // PUT request
    put: function(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    // DELETE request
    delete: function(url) {
        return this.request(url, {
            method: 'DELETE'
        });
    }
};

// Form handling utilities
const FormHandler = {
    // Validate required fields
    validateRequired: function(form, fields) {
        const errors = [];
        fields.forEach(field => {
            const element = form.querySelector(`[name="${field}"]`);
            if (!element || !element.value.trim()) {
                errors.push(`${field.replace('_', ' ')} is required`);
            }
        });
        return errors;
    },

    // Submit form via AJAX
    submitAjax: async function(form, successCallback, errorCallback) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await API.post(form.action, data);
            if (successCallback) successCallback(response);
        } catch (error) {
            if (errorCallback) errorCallback(error);
            else Utils.showToast('danger', error.message);
        }
    },

    // Reset form
    reset: function(form) {
        form.reset();
        // Clear any Bootstrap validation classes
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
    }
};

// Table utilities
const TableHandler = {
    // Initialize sortable tables
    initSortable: function(tableSelector) {
        const tables = document.querySelectorAll(tableSelector);
        tables.forEach(table => {
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.sortTable(table, header.dataset.sort);
                });
            });
        });
    },

    // Sort table by column
    sortTable: function(table, column) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const currentOrder = table.dataset.sortOrder || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

        rows.sort((a, b) => {
            const aValue = a.querySelector(`td[data-${column}]`)?.dataset[column] || '';
            const bValue = b.querySelector(`td[data-${column}]`)?.dataset[column] || '';
            
            if (newOrder === 'asc') {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });

        // Clear and re-append rows
        rows.forEach(row => tbody.appendChild(row));
        table.dataset.sortOrder = newOrder;
    },

    // Update table with new data
    updateTable: function(tableSelector, data, rowTemplate) {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No data found</td></tr>';
            return;
        }

        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = rowTemplate(item);
            tbody.appendChild(row);
        });
    }
};

// Modal utilities
const ModalHandler = {
    // Show modal with content
    show: function(modalId, title, content) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const titleEl = modal.querySelector('.modal-title');
        const bodyEl = modal.querySelector('.modal-body');

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = content;

        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    },

    // Hide modal
    hide: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) bootstrapModal.hide();
        }
    },

    // Show confirmation dialog
    confirm: function(message, onConfirm, onCancel) {
        const modal = document.getElementById('confirmModal') || this.createConfirmModal();
        
        const bodyEl = modal.querySelector('.modal-body');
        bodyEl.textContent = message;

        const confirmBtn = modal.querySelector('.btn-confirm');
        const cancelBtn = modal.querySelector('.btn-cancel');

        // Remove existing listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

        // Add new listeners
        newConfirmBtn.addEventListener('click', () => {
            this.hide('confirmModal');
            if (onConfirm) onConfirm();
        });

        newCancelBtn.addEventListener('click', () => {
            this.hide('confirmModal');
            if (onCancel) onCancel();
        });

        this.show('confirmModal', 'Confirm Action', '');
    },

    // Create confirmation modal if it doesn't exist
    createConfirmModal: function() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'confirmModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-cancel">Cancel</button>
                        <button type="button" class="btn btn-primary btn-confirm">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }
};

// Initialize common functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search debouncing
    const searchInputs = document.querySelectorAll('input[type="search"], input[name="search"]');
    searchInputs.forEach(input => {
        const debouncedSearch = Utils.debounce(() => {
            if (input.form) input.form.submit();
        }, 500);
        input.addEventListener('input', debouncedSearch);
    });

    // Initialize sortable tables
    TableHandler.initSortable('table[data-sortable="true"]');

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide toasts after 5 seconds
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    });
});

// Export for use in other scripts
window.Utils = Utils;
window.API = API;
window.FormHandler = FormHandler;
window.TableHandler = TableHandler;
window.ModalHandler = ModalHandler;