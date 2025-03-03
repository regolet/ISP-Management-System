<?php
$title = '404 Not Found';
ob_start();
?>

<i class='bx bx-search-alt error-icon'></i>
<div class="error-code">404</div>
<div class="error-message">Page Not Found</div>
<div class="error-description">
    The page you're looking for doesn't exist or has been moved.<br>
    Please check the URL and try again.
</div>
<div class="mt-4">
    <a href="/" class="btn btn-light me-2">
        <i class='bx bx-home-alt me-2'></i>
        Return Home
    </a>
    <button onclick="history.back()" class="btn btn-outline-light">
        <i class='bx bx-arrow-back me-2'></i>
        Go Back
    </button>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
