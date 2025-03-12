<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../app/Controllers/AuthController.php';

require_once '../../../views/layouts/sidebar.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: ../../login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $speed_mbps = $_POST['speed_mbps'];
    $price = $_POST['price'];
    $billing_cycle = $_POST['billing_cycle'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate input
    if (empty($name) || empty($speed_mbps) || empty($price) || empty($billing_cycle)) {
        $error = "All fields are required.";
    } else {
        // Insert the new plan into the database
        function add_plan($name, $description, $speed_mbps, $price, $billing_cycle, $is_active) {
            global $db;
            $query = "INSERT INTO plans (name, description, speed_mbps, price, billing_cycle, is_active) VALUES (:name, :description, :speed_mbps, :price, :billing_cycle, :is_active)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':speed_mbps', $speed_mbps);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':billing_cycle', $billing_cycle);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            return $stmt->execute();
        }
        $result = add_plan($name, $description, $speed_mbps, $price, $billing_cycle, $is_active);

        if ($result) {
            // Redirect to the plans page
            header('Location: ../../plans.php');
            exit();
        } else {
            $error = "Error adding plan.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Plan - ISP Management System</title>

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
                <h1 class="h3 mb-0">Add New Plan</h1>
            </div>

            <!-- Add Plan Form -->
            <div class="card">
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="speed_mbps" class="form-label">Speed (Mbps)</label>
                            <input type="number" class="form-control" id="speed_mbps" name="speed_mbps" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="billing_cycle" class="form-label">Billing Cycle</label>
                            <select class="form-select" id="billing_cycle" name="billing_cycle" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annually">Annually</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Plan</button>
                        <a href="/plans.php" class="btn btn-secondary">Cancel</a>
                    </form>
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