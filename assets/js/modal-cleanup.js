/**
 * Modal Cleanup JavaScript
 * This script removes any lingering modal backdrops and classes
 */

// Clean up when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Remove any lingering modal backdrops
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // Remove modal-open class from body if it exists
    document.body.classList.remove('modal-open');
    
    // Remove inline padding-right style that Bootstrap might have added
    document.body.style.paddingRight = '';
    
    // Remove any overflow: hidden that might have been added
    document.body.style.overflow = '';
});