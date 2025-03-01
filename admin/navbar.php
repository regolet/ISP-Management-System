<?php
require_once '../config.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <!-- Logo - Always visible -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="bx bx-wifi"></i>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="bx bx-menu"></i>
        </button>

        <!-- Collapsible Navigation -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav w-100">
                <li class="nav-item">
                    <a class="nav-link <?php echo $_SESSION["active_menu"] == "dashboard" ? "active" : ""; ?>" href="dashboard.php">
                        <i class="bx bx-home"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($_SESSION["active_menu"], ["plans", "customers", "subscriptions", "billing", "payments", "inventory"]) ? "active" : ""; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-wifi"></i>
                        <span class="nav-text">Internet Service</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "plans" ? "active" : ""; ?>" href="plans.php">
                            <i class="bx bx-package"></i> Plans</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "customers" ? "active" : ""; ?>" href="customers.php">
                            <i class="bx bx-user"></i> Customers</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "subscriptions" ? "active" : ""; ?>" href="subscriptions.php">
                            <i class="bx bx-broadcast"></i> Subscriptions</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "billing" ? "active" : ""; ?>" href="billing.php">
                            <i class="bx bx-credit-card"></i> Billing</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "payments" ? "active" : ""; ?>" href="payments.php">
                            <i class="bx bx-money"></i> Payments</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "inventory" ? "active" : ""; ?>" href="inventory.php">
                            <i class="bx bx-box"></i> Inventory</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "pon_management" ? "active" : ""; ?>" href="ftth_topology.php">
                            <i class="bx bx-network-chart"></i> PON Management</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($_SESSION["active_menu"], ["employees", "attendance", "leaves", "payroll", "deductions"]) ? "active" : ""; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-group"></i>
                        <span class="nav-text">HR Management</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "employees" ? "active" : ""; ?>" href="employees.php">
                            <i class="bx bx-user"></i> Employees</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "attendance" ? "active" : ""; ?>" href="attendance.php">
                            <i class="bx bx-time"></i> Attendance</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "leaves" ? "active" : ""; ?>" href="leaves.php">
                            <i class="bx bx-calendar"></i> Leave Management</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "payroll" ? "active" : ""; ?>" href="payroll.php">
                            <i class="bx bx-money"></i> Payroll</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "deductions" ? "active" : ""; ?>" href="deductions.php">
                            <i class="bx bx-calculator"></i> Deductions</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($_SESSION["active_menu"], ["reports", "sales_report", "collection_report", "attendance_report", "payroll_report"]) ? "active" : ""; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-file"></i>
                        <span class="nav-text">Reports</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "sales_report" ? "active" : ""; ?>" href="sales_report.php">
                            <i class="bx bx-bar-chart"></i> Sales Report</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "collection_report" ? "active" : ""; ?>" href="collection_report.php">
                            <i class="bx bx-money"></i> Collection Report</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "attendance_report" ? "active" : ""; ?>" href="attendance_report.php">
                            <i class="bx bx-time"></i> Attendance Report</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "payroll_report" ? "active" : ""; ?>" href="payroll_report.php">
                            <i class="bx bx-money"></i> Payroll Report</a></li>
                    </ul>
                </li>

                <?php if ($_SESSION["role"] === "admin"): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($_SESSION["active_menu"], ["users", "settings", "company", "backup", "roles", "audit_logs"]) ? "active" : ""; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-cog"></i>
                        <span class="nav-text">Administration</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "users" ? "active" : ""; ?>" href="users.php">
                            <i class="bx bx-user"></i> Users</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "roles" ? "active" : ""; ?>" href="roles.php">
                            <i class="bx bx-shield"></i> Roles</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "settings" ? "active" : ""; ?>" href="settings.php">
                            <i class="bx bx-cog"></i> Settings</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "backup" ? "active" : ""; ?>" href="backup.php">
                            <i class="bx bx-data"></i> Backup</a></li>
                        <li><a class="dropdown-item <?php echo $_SESSION["active_menu"] == "audit_logs" ? "active" : ""; ?>" href="audit_logs.php">
                            <i class="bx bx-history"></i> Audit Logs</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item ms-lg-auto">
                    <a class="nav-link" href="logout.php">
                        <i class="bx bx-log-out"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
/* Navbar styles */
.navbar {
    height: 60px;
    padding: 0 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    background: var(--bs-primary) !important;
}
.navbar-brand {
    font-size: 1.5rem;
    padding: 0;
    color: #fff !important;
}
.navbar-brand i {
    font-size: 1.8rem;
}
.navbar-toggler {
    border: none;
    padding: 0;
}
.navbar-toggler i {
    font-size: 1.8rem;
}
.navbar-nav .nav-link {
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.8) !important;
    transition: all 0.2s ease;
    position: relative;
}
.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.1);
}
.navbar-nav .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #fff;
}
.navbar-nav .nav-link i {
    font-size: 1.25rem;
}
.nav-text {
    font-size: 0.9rem;
}

/* Dropdown styles */
.dropdown-menu {
    margin-top: 0.5rem;
    border: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 0.5rem;
    background: #fff;
}
.dropdown-item {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--bs-gray-700);
    transition: all 0.2s ease;
}
.dropdown-item i {
    font-size: 1.1rem;
    width: 1.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-gray-600);
}
.dropdown-item:hover,
.dropdown-item.active {
    background-color: var(--bs-primary);
    color: white;
}
.dropdown-item:hover i,
.dropdown-item.active i {
    color: white;
}
.dropdown-toggle::after {
    margin-left: auto;
}

body {
    padding-top: 60px;
    background: #f4f6f9;
    min-height: 100vh;
}

/* Mobile Responsive */
@media (max-width: 991.98px) {
    .navbar-collapse {
        position: fixed;
        top: 60px;
        left: 0;
        padding: 1rem;
        width: 100%;
        height: calc(100vh - 60px);
        background-color: var(--bs-primary);
        z-index: 1000;
        overflow-y: auto;
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }
    .navbar-collapse.collapsing {
        transform: translateX(-100%);
    }
    .navbar-collapse.show {
        transform: translateX(0);
    }
    .dropdown-menu {
        background-color: rgba(255, 255, 255, 0.1);
        margin-top: 0;
        margin-left: 1rem;
        box-shadow: none;
    }
    .dropdown-item {
        color: rgba(255, 255, 255, 0.8);
    }
    .dropdown-item i {
        color: rgba(255, 255, 255, 0.8);
    }
    .dropdown-item:hover,
    .dropdown-item.active {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
    }
    .dropdown-item:hover i,
    .dropdown-item.active i {
        color: white;
    }
    .nav-text {
        display: inline-block !important;
    }
    
    /* Improve mobile dropdown animation */
    .dropdown-menu.show {
        animation: slideDown 0.2s ease-out;
    }
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}
</style>
