<?php
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/views/layouts/sidebar.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/ClientController.php';

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

// Initialize Client Controller
$clientController = new \App\Controllers\ClientController($db);

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Get clients with filters
$result = $clientController->getClients([
    'search' => $search,
    'status' => $status,
    'page' => $page,
    'per_page' => $per_page
]);

$clients = $result['clients'];
$pagination = $result['pagination'];

// Get client statistics
$stats = $clientController->getClientStats();

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
    <title>Clients - ISP Management System</title>

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
    <?php renderSidebar('clients'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Clients</h1>
                <a href="/forms/clients/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Client
                </a>
            </div>

            <!-- Flash Message -->
            <?php if (!empty($flashMessage)): ?>
                <div class="alert alert-<?php echo $flashMessageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flashMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50">Total Clients</h6>
                                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                                </div>
                                <i class="fas fa-users fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50">Active Clients</h6>
                                    <h2 class="mb-0"><?php echo $stats['by_status']['active'] ?? 0; ?></h2>
                                </div>
                                <i class="fas fa-user-check fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50">Inactive Clients</h6>
                                    <h2 class="mb-0"><?php echo $stats['by_status']['inactive'] ?? 0; ?></h2>
                                </div>
                                <i class="fas fa-user-clock fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50">Suspended Clients</h6>
                                    <h2 class="mb-0"><?php echo $stats['by_status']['suspended'] ?? 0; ?></h2>
                                </div>
                                <i class="fas fa-user-slash fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search clients..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo ($status === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times me-2"></i>Clear
                                </button>
                                <?php if ($auth->hasRole('admin')): ?>
                                <a href="/api/clients.php?action=export" class="btn btn-success ms-auto">
                                    <i class="fas fa-file-export me-2"></i>Export
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($clients)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No clients found. Try adjusting your search criteria or <a href="/forms/clients/add.php">add a new client</a>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Client #</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Connection Date</th>
                                        <th>Subscription</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['client_number'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($client['email'])): ?>
                                                    <div><i class="fas fa-envelope me-1 text-muted"></i> <?php echo htmlspecialchars($client['email']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($client['phone'])): ?>
                                                    <div><i class="fas fa-phone me-1 text-muted"></i> <?php echo htmlspecialchars($client['phone']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $location = [];
                                                if (!empty($client['city'])) $location[] = $client['city'];
                                                if (!empty($client['state'])) $location[] = $client['state'];
                                                echo !empty($location) ? htmlspecialchars(implode(', ', $location)) : 'N/A';
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = 'secondary';
                                                if ($client['status'] === 'active') $statusClass = 'success';
                                                if ($client['status'] === 'inactive') $statusClass = 'warning';
                                                if ($client['status'] === 'suspended') $statusClass = 'danger';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($client['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo !empty($client['connection_date']) ? date('M d, Y', strtotime($client['connection_date'])) : 'N/A'; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($client['subscription_plan'] ?? 'N/A'); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/forms/clients/view.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/forms/clients/edit.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($auth->hasRole('admin')): ?>
                                                        <a href="/forms/clients/delete.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['last_page'] > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($pagination['current_page'] <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $startPage = max(1, $pagination['current_page'] - 2);
                                    $endPage = min($pagination['last_page'], $pagination['current_page'] + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&status=' . urlencode($status) . '">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        echo '<li class="page-item ' . (($pagination['current_page'] == $i) ? 'active' : '') . '">
                                            <a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '">' . $i . '</a>
                                        </li>';
                                    }
                                    
                                    if ($endPage < $pagination['last_page']) {
                                        if ($endPage < $pagination['last_page'] - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $pagination['last_page'] . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '">' . $pagination['last_page'] . '</a></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?php echo ($pagination['current_page'] >= $pagination['last_page']) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
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
    <script src="/assets/js/clients.js"></script>
    
    <script>
        // Clear search
        function clearSearch() {
            document.getElementById('search').value = '';
            document.getElementById('status').value = '';
            document.getElementById('filterForm').submit();
        }
        
        // Delete client
        function deleteClient(id) {
            if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
                fetch(`/api/clients.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.APP_CONFIG.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Client deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the client.');
                });
            }
        }
    </script>
</body>
</html>