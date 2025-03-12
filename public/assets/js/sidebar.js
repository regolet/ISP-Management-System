/**
 * Sidebar functionality for ISP Management System
 * This script ensures consistent sidebar behavior across all pages
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if sidebar exists
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    // Ensure sidebar toggle button exists
    let toggleButton = document.getElementById('sidebarToggle');
    if (!toggleButton) {
        toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.id = 'sidebarToggle';
        toggleButton.className = 'btn btn-link d-md-none position-fixed';
        toggleButton.style = 'top: 1rem; left: 1rem; z-index: 1040;';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleButton);
    }
    
    // Remove any existing backdrop
    const existingBackdrops = document.querySelectorAll('.sidebar-backdrop, .modal-backdrop');
    existingBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Remove modal-open class from body if it exists
    document.body.classList.remove('modal-open');
    
    // Simple toggle function - just toggle the active class
    function toggleSidebar(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        sidebar.classList.toggle('active');
    }
    
    // Add click event ONLY to the toggle button
    toggleButton.addEventListener('click', toggleSidebar);
    
    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
        }
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        // Only do this on mobile
        if (window.innerWidth <= 768) {
            // Check if sidebar is active and click is outside sidebar
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                e.target !== toggleButton &&
                !toggleButton.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Export toggle function
    window.toggleSidebar = toggleSidebar;
});