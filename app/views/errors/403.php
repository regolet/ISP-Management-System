<?php
$title = '403 Forbidden';
ob_start();
?>

<i class='bx bx-block error-icon'></i>
<div class="error-code">403</div>
<div class="error-message">Access Forbidden</div>
<div class="error-description">
    Sorry, you don't have permission to access this page.<br>
    Please contact your administrator if you believe this is a mistake.
</div>
<a href="/" class="btn btn-light">
    <i class='bx bx-home-alt me-2'></i>
    Return Home
</a>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
