<?php
// Display success message
if (isset($_SESSION['success'])) {
    echo '<div class="alert-container">';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert" data-alert-type="success">';
    echo htmlspecialchars($_SESSION['success']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
    unset($_SESSION['success']);
}

// Display error message
if (isset($_SESSION['error'])) {
    echo '<div class="alert-container">';
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert" data-alert-type="error">';
    echo htmlspecialchars($_SESSION['error']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
    unset($_SESSION['error']);
}

// Display warning message
if (isset($_SESSION['warning'])) {
    echo '<div class="alert-container">';
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" data-alert-type="warning">';
    echo htmlspecialchars($_SESSION['warning']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
    unset($_SESSION['warning']);
}

// Display info message
if (isset($_SESSION['info'])) {
    echo '<div class="alert-container">';
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert" data-alert-type="info">';
    echo htmlspecialchars($_SESSION['info']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
    unset($_SESSION['info']);
}
?>


<style>
.alert-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050; /* Ensure it's above other elements */
    max-width: 300px; /* Limit the width */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-container .alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.remove();
        }, 3000); // 1000 milliseconds = 1 second
    });
});
</script>
