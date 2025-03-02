<?php
$title = $code . ' ' . $message ?? 'Error';
ob_start();
?>

<i class='bx bx-error-alt error-icon'></i>
<div class="error-code"><?php echo $code ?? '???'; ?></div>
<div class="error-message"><?php echo $message ?? 'Unknown Error'; ?></div>
<div class="error-description">
    <?php echo $description ?? 'An unexpected error occurred.'; ?><br>
    Please try again later or contact support if the problem persists.
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

<?php if (isset($error) && $app_config['env'] === 'development'): ?>
    <div class="mt-4 text-start bg-white text-dark p-3 rounded" style="max-width: 800px; margin: 2rem auto;">
        <h5 class="mb-3">Error Details:</h5>
        <pre class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($error); ?></pre>
        <?php if (isset($trace)): ?>
            <h5 class="mt-4 mb-3">Stack Trace:</h5>
            <pre class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($trace); ?></pre>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
