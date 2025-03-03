<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Staff Portal - ISP Management System' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/favicon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/css/staff.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if (isset($styles)): ?>
        <?= $styles ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <?php require_once __DIR__ . '/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['warning']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <!-- Page Content -->
        <?= $content ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?= date('Y') ?> ISP Management System. All rights reserved.
                    </span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Version 1.0.0 | 
                        <a href="/staff/help" class="text-muted text-decoration-none">Help</a> | 
                        <a href="/staff/support" class="text-muted text-decoration-none">Support</a>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Common Staff Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }, 5000);
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Check for new notifications
        function checkNotifications() {
            fetch('/staff/notifications/check')
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline';
                        }
                    }
                });
        }

        // Check notifications every minute
        setInterval(checkNotifications, 60000);

        // Handle session timeout
        let sessionTimeout;
        
        function resetSessionTimeout() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(showSessionWarning, <?= (session_get_cookie_params()['lifetime'] - 300) * 1000 ?>);
        }

        function showSessionWarning() {
            const modal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
            modal.show();

            // Auto logout after 5 minutes if no action
            setTimeout(() => {
                window.location.href = '/logout';
            }, 300000);
        }

        // Reset timeout on user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetSessionTimeout, false);
        });

        // Initial session timeout setup
        resetSessionTimeout();
    });
    </script>

    <!-- Session Warning Modal -->
    <div class="modal fade" id="sessionWarningModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Expiring</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Your session is about to expire. You will be logged out in 5 minutes.</p>
                    <p>Would you like to continue your session?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Continue Working
                    </button>
                    <a href="/logout" class="btn btn-primary">
                        Logout Now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page-specific Scripts -->
    <?php if (isset($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
</body>
</html>
