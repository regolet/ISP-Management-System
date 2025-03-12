<?php
require_once '../../../app/init.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../../login.php');
    exit;
}

// Get the plan ID from the query string
$id = isset($_GET['id']) ? $_GET['id'] : null;

// If no ID is provided, redirect to the plans page
if (!$id) {
    header('Location: ../../plans.php');
    exit;
}

$error = null; // Initialize error variable

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Plan - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php include '../../../views/layouts/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="main-content p-4">
            <div class="container">
                <h1>Delete Plan</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">
                    <i class="fas fa-trash me-2"></i>Yes, Delete
                </button>
                <a href="../../plans.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>No, Cancel
                </a>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to delete this plan?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <a href="delete.php?id=<?php echo $id; ?>&confirm=yes" class="btn btn-danger">Delete</a>
                            </div>
                        </div>
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

<?php

// Handle plan deletion after JavaScript confirmation
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Delete the plan from the database
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $result = delete_plan($id);

    if ($result) {
        // Redirect to the plans page
        header('Location: ../../plans.php');
        exit;
    } else {
        $error = "Error deleting plan.";
    }
}

function delete_plan($id) {
    global $db;
    $query = "DELETE FROM plans WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

?>