<![CDATA[<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/LcpController.php';
require_once dirname(__DIR__) . '/views/layouts/sidebar.php';
// Include the OltController
require_once dirname(__DIR__) . '/app/Controllers/OltController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize controllers
$lcpController = new \App\Controllers\LcpController($db);

// Check if editing an existing LCP
$isEditing = isset($_GET['id']) && is_numeric($_GET['id']);
$lcpId = $isEditing ? (int)$_GET['id'] : 0;

// Get LCP data if editing
$lcp = [];
if ($isEditing) {
    $response = $lcpController->getLcpById($lcpId);
    if ($response['success']) {
        $lcp = $response['lcp'];
    } else {
        // Redirect back to LCP list if LCP not found
        header("Location: lcp.php");
        exit();
    }
}

// Set page title based on operation
$pageTitle = $isEditing ? "Edit LCP: {$lcp['name']}" : "Add New LCP";

// Process form submission if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form processing code would go here
    // For now, we'll redirect back to the LCP list
    header("Location: lcp.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('lcp'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?php echo $pageTitle; ?></h1>
                <a href="lcp.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to LCP List
                </a>
            </div>

            <!-- Error Container -->
            <div id="errorContainer" class="alert alert-danger" style="display: none;"></div>

            <!-- LCP Form -->
            <div class="card">
                <div class="card-body">
                    <form id="lcpForm" method="POST" action="<?php echo $isEditing ? "api/lcp.php?id={$lcpId}&_method=PUT" : "api/lcp.php"; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($lcp['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" required
                                       value="<?php echo htmlspecialchars($lcp['model'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" required
                                       value="<?php echo htmlspecialchars($lcp['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude"
                                       value="<?php echo htmlspecialchars($lcp['latitude'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude"
                                       value="<?php echo htmlspecialchars($lcp['longitude'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Ports</label>
                                <input type="number" class="form-control" id="total_ports" name="total_ports" required min="1"
                                       value="<?php echo htmlspecialchars($lcp['total_ports'] ?? '8'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo ($lcp['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="maintenance" <?php echo ($lcp['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="offline" <?php echo ($lcp['status'] ?? '') === 'offline' ? 'selected' : ''; ?>>Offline</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent OLT</label>
                                <select class="form-select" id="parentOlt" name="parent_olt_id">
                                    <option value="">None</option>
                                    <?php
                                    // Get OLTs from API or directly from controller
                                    $olts = [];
                                    try {
                                        // Simplified for this example - in production, use proper API call
                                        $stmt = $db->query("SELECT id, name, ip_address FROM olt_devices ORDER BY name");
                                        $olts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    } catch (Exception $e) {
                                        // Handle error silently
                                        error_log("Error loading OLTs: " . $e->getMessage());
                                    }
                                    
                                    foreach ($olts as $olt) {
                                        $selected = ($lcp['parent_olt_id'] ?? '') == $olt['id'] ? 'selected' : '';
                                        echo "<option value=\"{$olt['id']}\" {$selected}>";
                                        echo htmlspecialchars($olt['name']) . ' ';
                                        if (!empty($olt['ip_address'])) {
                                            echo '(' . htmlspecialchars($olt['ip_address']) . ')';
                                        }
                                        echo "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Port</label>
                                <select class="form-select" id="parentPort" name="parent_port_id">
                                    <option value="">Select OLT first</option>
                                    <?php if ($isEditing && !empty($lcp['parent_port_id'])): ?>
                                        <option value="<?php echo $lcp['parent_port_id']; ?>" selected>
                                            Port <?php echo $lcp['parent_port_number'] ?? 'Unknown'; ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Installation Date</label>
                                <input type="date" class="form-control" id="installation_date" name="installation_date"
                                       value="<?php echo htmlspecialchars($lcp['installation_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($lcp['notes'] ?? ''); ?></textarea>
                            </div>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">Save LCP</button>
                                <a href="lcp.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/lcp-edit.js"></script>
</body>
</html>
]]>
