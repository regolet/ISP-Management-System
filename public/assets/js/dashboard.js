// Dashboard initialization
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    initializeSidebarToggle();
    setupAutoRefresh();
    setupEventListeners();
});

// Initialize Bootstrap tooltips
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize sidebar toggle functionality
function initializeSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            toggleBackdrop();
        });
    }
}

// Setup auto refresh for dashboard data
function setupAutoRefresh() {
    // Refresh dashboard every 5 minutes if page is visible
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            refreshDashboardData();
        }
    }, 300000); // 5 minutes
}

// Setup event listeners
function setupEventListeners() {
    // Print button
    const printButton = document.querySelector('.btn-print');
    if (printButton) {
        printButton.addEventListener('click', function() {
            window.print();
        });
    }

    // Refresh buttons
    document.querySelectorAll('.btn-refresh').forEach(button => {
        button.addEventListener('click', function() {
            const target = this.dataset.target;
            refreshSection(target);
        });
    });
}

// Refresh dashboard data
function refreshDashboardData() {
    showLoading();
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            updateDashboardContent(doc);
            hideLoading();
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
            hideLoading();
        });
}

// Refresh specific section
function refreshSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.classList.add('loading');
        setTimeout(() => {
            section.classList.remove('loading');
        }, 1000);
    }
}

// Toggle backdrop for mobile sidebar
function toggleBackdrop() {
    const backdrop = document.querySelector('.sidebar-backdrop');
    if (!backdrop) {
        const newBackdrop = document.createElement('div');
        newBackdrop.className = 'sidebar-backdrop';
        newBackdrop.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            this.remove();
        });
        document.body.appendChild(newBackdrop);
    } else {
        backdrop.remove();
    }
}

// Show loading state
function showLoading() {
    const loader = document.createElement('div');
    loader.className = 'dashboard-loader';
    document.body.appendChild(loader);
}

// Hide loading state
function hideLoading() {
    const loader = document.querySelector('.dashboard-loader');
    if (loader) {
        loader.remove();
    }
}

// Format numbers with commas
function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Update dashboard content
function updateDashboardContent(newDoc) {
    // Update summary cards
    document.querySelectorAll('.card-number').forEach((element, index) => {
        const newValue = newDoc.querySelectorAll('.card-number')[index]?.textContent;
        if (newValue) {
            element.textContent = newValue;
        }
    });

    // Update tables
    document.querySelectorAll('.table-responsive').forEach((element, index) => {
        const newTable = newDoc.querySelectorAll('.table-responsive')[index]?.innerHTML;
        if (newTable) {
            element.innerHTML = newTable;
        }
    });
}

// Export functions for use in other scripts
window.dashboardUtils = {
    formatNumber,
    formatCurrency,
    refreshSection,
    refreshDashboardData
};
