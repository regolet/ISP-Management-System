<?php
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/app/DatabaseOptimizer.php';

// Only allow access in debug mode or by admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Initialize database optimizer
$optimizer = new \App\DatabaseOptimizer($db);

// Handle actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

switch ($action) {
    case 'optimize':
        try {
            $optimizer->optimizeTables();
            $optimizer->createIndexes();
            $message = "Database optimization completed successfully.";
        } catch (Exception $e) {
            $error = "Optimization failed: " . $e->getMessage();
        }
        break;
        
    case 'clear_cache':
        try {
            $optimizer->clearCache();
            $message = "Cache cleared successfully.";
        } catch (Exception $e) {
            $error = "Cache clearing failed: " . $e->getMessage();
        }
        break;
        
    case 'analyze':
        // Get performance statistics
        $queryStats = $optimizer->getQueryStats();
        $dbSize = filesize(dirname(__DIR__) . '/database/isp-management.sqlite');
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $analysis = [
            'query_stats' => $queryStats,
            'database_size' => $dbSize,
            'memory_usage' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
        break;
}

// Get current performance metrics
$currentStats = $optimizer->getQueryStats();
$dbSize = filesize(dirname(__DIR__) . '/database/isp-management.sqlite');
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Monitor - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <link href="/assets/css/optimized.css" rel="stylesheet">
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- Render Sidebar -->
    <?php require_once dirname(__DIR__) . '/views/layouts/sidebar.php'; renderSidebar('performance'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Performance Monitor</h1>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="location.href='?action=optimize'">
                        <i class="fas fa-database me-2"></i>Optimize Database
                    </button>
                    <button class="btn btn-warning" onclick="location.href='?action=clear_cache'">
                        <i class="fas fa-broom me-2"></i>Clear Cache
                    </button>
                    <button class="btn btn-info" onclick="location.href='?action=analyze'">
                        <i class="fas fa-chart-line me-2"></i>Analyze Performance
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Performance Metrics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-primary">
                        <h5>Total Queries</h5>
                        <h3><?php echo number_format($currentStats['total_queries']); ?></h3>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-warning">
                        <h5>Slow Queries</h5>
                        <h3><?php echo number_format($currentStats['slow_queries']); ?></h3>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-success">
                        <h5>Database Size</h5>
                        <h3><?php echo formatBytes($dbSize); ?></h3>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card stats-card-danger">
                        <h5>Memory Usage</h5>
                        <h3><?php echo formatBytes($memoryUsage); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Detailed Analysis -->
            <?php if (isset($analysis)): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Query Performance</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td>Total Queries:</td>
                                    <td><?php echo number_format($analysis['query_stats']['total_queries']); ?></td>
                                </tr>
                                <tr>
                                    <td>Slow Queries:</td>
                                    <td><?php echo number_format($analysis['query_stats']['slow_queries']); ?></td>
                                </tr>
                                <tr>
                                    <td>Average Query Time:</td>
                                    <td><?php echo number_format($analysis['query_stats']['average_time'] * 1000, 2); ?>ms</td>
                                </tr>
                                <tr>
                                    <td>Total Query Time:</td>
                                    <td><?php echo number_format($analysis['query_stats']['total_time'], 3); ?>s</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td>PHP Version:</td>
                                    <td><?php echo htmlspecialchars($analysis['php_version']); ?></td>
                                </tr>
                                <tr>
                                    <td>Server Software:</td>
                                    <td><?php echo htmlspecialchars($analysis['server_software']); ?></td>
                                </tr>
                                <tr>
                                    <td>Database Size:</td>
                                    <td><?php echo formatBytes($analysis['database_size']); ?></td>
                                </tr>
                                <tr>
                                    <td>Memory Usage:</td>
                                    <td><?php echo formatBytes($analysis['memory_usage']); ?></td>
                                </tr>
                                <tr>
                                    <td>Peak Memory:</td>
                                    <td><?php echo formatBytes($analysis['peak_memory']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Performance Recommendations -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Database Optimizations</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Use indexes on frequently queried columns</li>
                                <li><i class="fas fa-check text-success me-2"></i>Optimize table structure and relationships</li>
                                <li><i class="fas fa-check text-success me-2"></i>Implement query caching for repeated queries</li>
                                <li><i class="fas fa-check text-success me-2"></i>Use prepared statements to prevent SQL injection</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Application Optimizations</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Minify and compress CSS/JS files</li>
                                <li><i class="fas fa-check text-success me-2"></i>Use CDN for external libraries</li>
                                <li><i class="fas fa-check text-success me-2"></i>Implement browser caching</li>
                                <li><i class="fas fa-check text-success me-2"></i>Optimize images and use WebP format</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tips -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Tips</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Quick Wins</h6>
                        <ul class="mb-0">
                            <li>Enable GZIP compression for text files</li>
                            <li>Set appropriate cache headers for static assets</li>
                            <li>Use lazy loading for images and heavy content</li>
                            <li>Implement pagination for large datasets</li>
                            <li>Consider using Redis or Memcached for session storage</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/optimized.js"></script>
    <script src="/assets/js/sidebar.js"></script>

    <script>
        // Auto-refresh performance metrics every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>