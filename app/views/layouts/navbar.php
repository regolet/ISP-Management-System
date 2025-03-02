<?php
// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ISP Management System' ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">
                <i class='bx bx-network-chart'></i> ISP Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard">
                            <i class='bx bx-home'></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/customers">
                            <i class='bx bx-user'></i> Customers
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-money'></i> Billing
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/billing">Invoices</a></li>
                            <li><a class="dropdown-item" href="/admin/payments">Payments</a></li>
                            <li><a class="dropdown-item" href="/admin/plans">Service Plans</a></li>
                            <li><a class="dropdown-item" href="/admin/subscriptions">Subscriptions</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-network-chart'></i> Network
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/network/dashboard">Overview</a></li>
                            <li><a class="dropdown-item" href="/admin/network/olt">OLT Management</a></li>
                            <li><a class="dropdown-item" href="/admin/network/map">Network Map</a></li>
                            <li><a class="dropdown-item" href="/admin/network/health">Health Monitor</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-package'></i> Assets
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/assets">Asset Management</a></li>
                            <li><a class="dropdown-item" href="/admin/inventory">Inventory</a></li>
                            <li><a class="dropdown-item" href="/admin/assets/report">Asset Reports</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-group'></i> HR
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/employees">Employees</a></li>
                            <li><a class="dropdown-item" href="/admin/attendance">Attendance</a></li>
                            <li><a class="dropdown-item" href="/admin/leaves">Leave Management</a></li>
                            <li><a class="dropdown-item" href="/admin/payroll">Payroll</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-cog'></i> System
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/settings/general">General Settings</a></li>
                            <li><a class="dropdown-item" href="/admin/settings/roles">Roles & Permissions</a></li>
                            <li><a class="dropdown-item" href="/admin/backup">Backup Management</a></li>
                            <li><a class="dropdown-item" href="/admin/audit">Audit Logs</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown">
                            <i class='bx bx-user-circle'></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/admin/profile">
                                <i class='bx bx-user'></i> Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">
                                <i class='bx bx-log-out'></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class='bx bx-check-circle'></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class='bx bx-error-circle'></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?= $content ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© <?= date('Y') ?> ISP Management System. All rights reserved.</span>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="/js/admin.js"></script>
</body>
</html>
