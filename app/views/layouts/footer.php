<!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error']) || isset($_SESSION['info'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['success'])): ?>
                showToast('success', '<?php echo addslashes($_SESSION['success']); ?>');
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                showToast('error', '<?php echo addslashes($_SESSION['error']); ?>');
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['info'])): ?>
                showToast('info', '<?php echo addslashes($_SESSION['info']); ?>');
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>
        });

        function showToast(type, message) {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            const toast = createToast(type, message);
            toastContainer.appendChild(toast);
            
            // Show the toast
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1050;
            `;
            document.body.appendChild(container);
            return container;
        }

        function createToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.cssText = `
                min-width: 300px;
                margin-bottom: 10px;
                background: white;
                border-radius: 4px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
            `;

            const colors = {
                success: '#1cc88a',
                error: '#e74a3b',
                info: '#36b9cc'
            };

            const icons = {
                success: 'bx-check-circle',
                error: 'bx-x-circle',
                info: 'bx-info-circle'
            };

            toast.innerHTML = `
                <div class="d-flex align-items-center p-3" style="border-left: 4px solid ${colors[type]}">
                    <i class='bx ${icons[type]} me-2' style="color: ${colors[type]}; font-size: 1.5rem;"></i>
                    <div>${message}</div>
                    <button type="button" class="btn-close ms-auto" onclick="this.closest('.toast').remove()"></button>
                </div>
            `;

            return toast;
        }
    </script>
    <?php endif; ?>
</body>
</html>
