<?php
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/views/layouts/sidebar.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/SettingsController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Settings Controller
$settingsController = new \App\Controllers\SettingsController();

// Set the active page
$activePage = 'settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = "Invalid CSRF token.";
        $_SESSION['flash_message_type'] = 'danger';
        header("Location: /settings.php");
        exit();
    }

    // Sanitize the input data
    $companyName = htmlspecialchars($_POST['companyName']);
    $companyAddress = htmlspecialchars($_POST['companyAddress']);
    $companyPhone = htmlspecialchars($_POST['companyPhone']);
    $companyEmail = htmlspecialchars($_POST['companyEmail']);

    // Create an array with the data
    $data = [
        'companyName' => $companyName,
        'companyAddress' => $companyAddress,
        'companyPhone' => $companyPhone,
        'companyEmail' => $companyEmail
    ];

    // Update the company profile
    if ($settingsController->updateCompanyProfile($data)) {
        $_SESSION['flash_message'] = "Company profile updated successfully.";
        $_SESSION['flash_message_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = "Failed to update company profile.";
        $_SESSION['flash_message_type'] = 'danger';
    }
    header("Location: /settings.php");
    exit();
}

// Get the company profile
$companyProfile = $settingsController->getCompanyProfile();

// Check for flash messages
$flashMessage = '';
$flashMessageType = '';

if (isset($_SESSION['flash_message'])) {
    $flashMessage = $_SESSION['flash_message'];
    $flashMessageType = $_SESSION['flash_message_type'] ?? 'success';
    
    // Clear flash message
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Immediate backdrop cleanup -->
    <script src="/assets/js/backdrop-cleanup.js"></script>
    
    <!-- Render Sidebar -->
    <?php renderSidebar($activePage); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Settings</h1>
            </div>

            <!-- Flash Message -->
            <?php if (!empty($flashMessage)): ?>
                <div class="alert alert-<?php echo $flashMessageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flashMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <div class="card">
                <div class="card-body">
                    <form action="settings.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="companyName" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="companyName" name="companyName" value="<?php echo htmlspecialchars($companyProfile['company_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="companyAddress" class="form-label">Company Address</label>
                            <input type="text" class="form-control" id="companyAddress" name="companyAddress" value="<?php echo htmlspecialchars($companyProfile['address'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="companyPhone" class="form-label">Company Phone</label>
                            <input type="text" class="form-control" id="companyPhone" name="companyPhone" value="<?php echo htmlspecialchars($companyProfile['phone_number'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="companyEmail" class="form-label">Company Email</label>
                            <input type="email" class="form-control" id="companyEmail" name="companyEmail" value="<?php echo htmlspecialchars($companyProfile['email_address'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Initialize global configuration -->
    <script>
        // Initialize global configuration
        window.APP_CONFIG = {
            baseUrl: <?php echo json_encode(rtrim((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/')); ?>,
            csrfToken: <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>,
            userRole: <?php echo json_encode($_SESSION['role'] ?? ''); ?>
        };
    </script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
</body>
</html>