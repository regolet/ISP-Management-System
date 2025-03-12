<?php
require_once dirname(__DIR__, 2) . '/app/init.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/views/layouts/sidebar.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/AuthController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    header("Location: /dashboard.php?error=unauthorized");
    exit();
}

$output = '';
$messageType = 'info';

// Create database if requested
if (isset($_POST['create_database']) && $_POST['create_database'] === 'yes') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $output = 'Invalid CSRF token';
        $messageType = 'danger';
    } else {
        try {
            // Capture output
            ob_start();
            
            // Run the database creation script
            require_once dirname(__DIR__, 2) . '/database/create_db.php';
            
            $output = ob_get_clean();
            $messageType = 'success';
        } catch (Exception $e) {
            $output = 'Error creating database: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
// Run database check if requested
else if (isset($_POST['check_database']) && $_POST['check_database'] === 'yes') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $output = 'Invalid CSRF token';
        $messageType = 'danger';
    } else {
        try {
            // Capture output
            ob_start();
            
            // Get database connection
            $database = new Database();
            $db = $database->getConnection();

            echo "Database file path: " . $database->db_path . "\n";
            echo "Database file exists: " . (file_exists($database->db_path) ? "Yes" : "No") . "\n\n";

            // Check tables
            $tables = [
                'users',
                'clients',
                'plans',
                'client_subscriptions'
            ];

            foreach ($tables as $table) {
                echo "Checking table: {$table}\n";
                
                // Check if table exists
                $query = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
                $stmt = $db->prepare($query);
                $stmt->execute([$table]);
                $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tableExists) {
                    echo "  Table exists: Yes\n";
                    
                    // Get table columns
                    $columnsQuery = "PRAGMA table_info({$table})";
                    $columnsStmt = $db->prepare($columnsQuery);
                    $columnsStmt->execute();
                    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "  Columns:\n";
                    foreach ($columns as $column) {
                        echo "    - {$column['name']} ({$column['type']})\n";
                    }
                    
                    // Count rows
                    $countQuery = "SELECT COUNT(*) as count FROM {$table}";
                    $countStmt = $db->prepare($countQuery);
                    $countStmt->execute();
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "  Row count: {$count['count']}\n";
                    
                    // Show sample data if table has rows
                    if ($count['count'] > 0) {
                        $sampleQuery = "SELECT * FROM {$table} LIMIT 5";
                        $sampleStmt = $db->prepare($sampleQuery);
                        $sampleStmt->execute();
                        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo "  Sample data:\n";
                        foreach ($samples as $sample) {
                            echo "    - ";
                            $sampleData = [];
                            foreach ($sample as $key => $value) {
                                $sampleData[] = "{$key}: " . (is_null($value) ? "NULL" : $value);
                            }
                            echo implode(", ", $sampleData) . "\n";
                        }
                    }
                } else {
                    echo "  Table exists: No\n";
                }
                
                echo "\n";
            }

            echo "Database check completed.\n";
            
            $output = ob_get_clean();
            $messageType = 'info';
        } catch (Exception $e) {
            $output = 'Error checking database: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Database - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('database'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Check Database</h1>
                <a href="/dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <?php if (!empty($output)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Database Check Results</h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0"><?php echo htmlspecialchars($output); ?></pre>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Database Check Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Check Database Structure</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        This tool will check the database structure and display information about tables and their contents.
                    </p>
                    
                    <form method="POST" action="" class="mt-4">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <input type="hidden" name="check_database" value="yes">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database me-2"></i>Check Database
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Create Database Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create Database</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        If the database file doesn't exist, you can create it using this tool.
                    </p>
                    
                    <form method="POST" action="" class="mt-4">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <input type="hidden" name="create_database" value="yes">
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-plus-circle me-2"></i>Create Database
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Database Migrations Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Database Migrations</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        If you need to update the database structure, you can run the migrations.
                    </p>
                    
                    <a href="/admin/migrations.php" class="btn btn-primary">
                        <i class="fas fa-database me-2"></i>Run Migrations
                    </a>
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
            baseUrl: <?php echo json_encode(rtrim((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2), '/')); ?>,
            csrfToken: <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>,
            userRole: <?php echo json_encode($_SESSION['role'] ?? ''); ?>
        };
    </script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
</body>
</html>