/**
 * Backdrop Cleanup Script
 * This script removes any lingering modal backdrops and sidebar backdrops
 */

// Run immediately when script is loaded
(function() {
    // Remove any existing backdrops
    const existingBackdrops = document.querySelectorAll('.sidebar-backdrop, .modal-backdrop');
    existingBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Remove modal-open class from body if it exists
    if (document.body.classList.contains('modal-open')) {
        document.body.classList.remove('modal-open');
    }
    
    // Remove inline padding-right style that Bootstrap might have added
    document.body.style.paddingRight = '';
    
    // Remove any overflow: hidden that might have been added
    document.body.style.overflow = '';
})();

// Also run when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Remove any existing backdrops
    const existingBackdrops = document.querySelectorAll('.sidebar-backdrop, .modal-backdrop');
    existingBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Remove modal-open class from body if it exists
    if (document.body.classList.contains('modal-open')) {
        document.body.classList.remove('modal-open');
    }
    
    // Remove inline padding-right style that Bootstrap might have added
    document.body.style.paddingRight = '';
    
    // Remove any overflow: hidden that might have been added
    document.body.style.overflow = '';
});