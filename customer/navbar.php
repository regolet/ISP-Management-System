<?php
require_once __DIR__ . '/../config.php';
?>

<nav class="sidebar bg-primary">
    <!-- Logo/Brand -->
    <div class="sidebar-header p-2">
        <h3 class="text-white mb-0">Customer Portal</h3>
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

        <!-- Subscription -->
        <li class="nav-item">
            <a class="nav-link <?php echo $_SESSION['active_menu'] == 'subscription' ? 'active' : ''; ?>" href="subscription.php">
                <i class='bx bx-broadcast'></i>
                <span>My Subscription</span>
            </a>
        </li>

        <!-- Billing -->
        <li class="nav-item">
            <a class="nav-link <?php echo $_SESSION['active_menu'] == 'billing' ? 'active' : ''; ?>" href="billing.php">
                <i class='bx bx-credit-card'></i>
                <span>Billing</span>
            </a>
        </li>

        <!-- Payments -->
        <li class="nav-item">
            <a class="nav-link <?php echo $_SESSION['active_menu'] == 'payments' ? 'active' : ''; ?>" href="payments.php">
                <i class='bx bx-money'></i>
                <span>Payments</span>
            </a>
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