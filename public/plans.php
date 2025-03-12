<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

require_once dirname(__DIR__) . '/views/layouts/sidebar.php';

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

// Fetch all plans from the database
function get_all_plans() {
    global $db;
    $query = "SELECT * FROM plans";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$plans = get_all_plans();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans Management - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('plans'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Plans Management</h1>
                <a href="/forms/plans/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Plan
                </a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php echo $_SESSION['message']; ?>
                    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <!-- Plans Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Speed (Mbps)</th>
                                    <th>Price</th>
                                    <th>Billing Cycle</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['description']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['speed_mbps']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['price']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['billing_cycle']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['is_active'] ? 'Active' : 'Inactive'); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/forms/plans/edit.php?id=<?php echo $plan['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/forms/plans/delete.php?id=<?php echo $plan['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
</body>
</html>