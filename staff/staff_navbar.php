<?php
require_once '../config.php';
?>

<nav class="sidebar bg-primary">
    <!-- Logo/Brand -->
    <div class="sidebar-header p-2">
        <h3 class="text-white mb-0">Staff Portal</h3>
    </div>

    <!-- Navigation Menu -->
    <ul class="nav nav-pills flex-column d-flex p-2">
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link <?php echo $_SESSION['active_menu'] == 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                <i class='bx bx-home-alt'></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Attendance -->
        <li class="nav-item">
            <a class="nav-link submenu-toggle <?php echo in_array($_SESSION['active_menu'], ['attendance', 'time_actions']) ? 'active' : ''; ?>" 
               href="#attendanceSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false">
                <i class='bx bx-time'></i>
                <span>Attendance</span>
                <i class='bx bx-chevron-down ms-auto'></i>
            </a>
            <div class="collapse <?php echo in_array($_SESSION['active_menu'], ['attendance', 'time_actions']) ? 'show' : ''; ?>" id="attendanceSubmenu">
                <ul class="nav flex-column sub-menu ms-3 mt-1">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_SESSION['active_menu'] == 'attendance' ? 'active' : ''; ?>" href="attendance/view.php">
                            <i class='bx bx-calendar'></i>
                            <span>View Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_SESSION['active_menu'] == 'time_actions' ? 'active' : ''; ?>" href="attendance/time_actions.php">
                            <i class='bx bx-timer'></i>
                            <span>Time Actions</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Payroll -->
        <li class="nav-item">
            <a class="nav-link <?php echo $_SESSION['active_menu'] == 'payroll' ? 'active' : ''; ?>" href="payroll/history.php">
                <i class='bx bx-money'></i>
                <span>Payroll History</span>
            </a>
        </li>

        <!-- Expenses -->
        <li class="nav-item">
            <a class="nav-link submenu-toggle <?php echo in_array($_SESSION['active_menu'], ['expenses_list', 'expenses_add']) ? 'active' : ''; ?>" 
               href="#expensesSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false">
                <i class='bx bx-receipt'></i>
                <span>Expenses</span>
                <i class='bx bx-chevron-down ms-auto'></i>
            </a>
            <div class="collapse <?php echo in_array($_SESSION['active_menu'], ['expenses_list', 'expenses_add']) ? 'show' : ''; ?>" id="expensesSubmenu">
                <ul class="nav flex-column sub-menu ms-3 mt-1">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_SESSION['active_menu'] == 'expenses_list' ? 'active' : ''; ?>" href="expenses/list.php">
                            <i class='bx bx-list-ul'></i>
                            <span>List Expenses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_SESSION['active_menu'] == 'expenses_add' ? 'active' : ''; ?>" href="expenses/add.php">
                            <i class='bx bx-plus'></i>
                            <span>Add Expense</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Payments -->
        <li class="nav-item">
            <a class="nav-link submenu-toggle <?php echo in_array($_SESSION['active_menu'], ['payments_list', 'payments_add']) ? 'active' : ''; ?>" 
               href="#paymentsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false">
                <i class='bx bx-credit-card'></i>
                <span>Payments</span>
                <i class='bx bx-chevron-down ms-auto'></i>
            </a>
            <div class="collapse <?php echo in_array($_SESSION['active_menu'], ['payments_list', 'payments_add']) ? 'show' : ''; ?>" id="paymentsSubmenu">
                <ul class="nav flex-column sub-menu ms-3 mt-1">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_SESSION['active_menu'] == 'payments_list' ? 'active' : ''; ?>" href="payments/list.php">
                            <i class='bx bx-list-ul'></i>
                            <span>List Payments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_SESSION['active_menu'] == 'payments_add' ? 'active' : ''; ?>" href="payments/add.php">
                            <i class='bx bx-plus'></i>
                            <span>Add Payment</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Profile -->
        <li class="nav-item">
            <a class="nav-link <?php echo $_SESSION['active_menu'] == 'profile' ? 'active' : ''; ?>" href="profile.php">
                <i class='bx bx-user'></i>
                <span>Profile</span>
            </a>
        </li>

        <!-- Logout -->
        <li class="nav-item mt-auto">
            <a class="nav-link" href="../logout.php">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Mobile Menu Toggle -->
<button type="button" class="btn btn-primary d-md-none position-fixed" 
        style="bottom: 1.5rem; right: 1.5rem; width: 3rem; height: 3rem; border-radius: 50%; z-index: 1040; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"
        onclick="document.querySelector('.sidebar').classList.toggle('show')">
    <i class="bx bx-menu fs-4"></i>
</button>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    z-index: 1030;
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
}

.nav-link {
    color: rgba(255,255,255,0.8) !important;
    display: flex;
    align-items: center;
    padding: 0.35rem 0.75rem;  /* reduced padding for more compact look */
    font-size: 0.95rem;  /* slightly smaller font size */
}

.nav-link:hover {
    color: #fff !important;
    background: rgba(255,255,255,0.1);
}

.nav-link.active {
    color: #fff !important;
    background: rgba(255,255,255,0.2) !important;
}

.nav-link i {
    font-size: 1.2rem;
    margin-right: 0.5rem;
    width: 1.5rem;
    text-align: center;
}

.submenu-toggle {
    justify-content: space-between;
}

.submenu-toggle i:last-child {
    margin-right: 0;
    transition: transform 0.3s ease;
}

.submenu-toggle[aria-expanded="true"] i:last-child {
    transform: rotate(-180deg);
}

.sub-menu {
    margin-left: 1rem;
}

.sub-menu .nav-link {
    font-size: 0.9rem;
    padding: 0.3rem 0.75rem;  /* even more compact for submenu items */
}

.sub-menu .nav-link i {
    font-size: 1.1rem;
}

/* Make the main menu ul take available height */
.sidebar .nav {
    flex: 1;
    display: flex;
    flex-direction: column;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}
</style>