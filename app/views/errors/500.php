<?php
$title = '500 Internal Server Error';
ob_start();
?>

<i class='bx bx-error-circle error-icon'></i>
<div class="error-code">500</div>
<div class="error-message">Internal Server Error</div>
<div class="error-description">
    Something went wrong on our end.<br>
    Our team has been notified and we're working to fix the issue.
</div>
<div class="mt-4">
    <a href="/" class="btn btn-light me-2">
        <i class='bx bx-home-alt me-2'></i>
        Return Home
    </a>
    <button onclick="location.reload()" class="btn btn-outline-light">
        <i class='bx bx-refresh me-2'></i>
        Try Again
    </button>
</div>

<?php if (isset($error) && $app_config['env'] === 'development'): ?>
    <div class="mt-4 text-start bg-white text-dark p-3 rounded" style="max-width: 800px; margin: 2rem auto;">
        <h5 class="mb-3">Error Details:</h5>
        <pre class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($error); ?></pre>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
