<?php
function renderSidebar($activePage = 'dashboard') {
?>
    <style>
        /* Sidebar Styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            display: flex;
            flex-direction: column;
        }

        /* Mobile Sidebar */
        @media (max-width: 768) {
            #sidebar {
                margin-left: -250px;
                box-shadow: none;
            }

            #sidebar.active {
                margin-left: 0;
                box-shadow: 3px 0 10px rgba(0,0,0,0.2);
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1025;
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
            }

            .sidebar-backdrop.show {
                display: block;
                opacity: 1;
            }
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 1rem 1rem; /* Reduced padding */
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem; /* Reduced font size */
            color: white;
        }

        .sidebar-header i {
            color: #3498db;
            margin-right: 0.5rem;
        }

        /* Navigation Links */
        .nav-link {
            color: rgba(255,255,255,.75);
            padding: 0.6rem 1rem; /* Reduced padding */
            transition: all 0.3s;
            border-radius: 0.25rem;
            margin: 0.1rem 0.5rem; /* Reduced margin */
            display: flex;
            align-items: center;
            font-size: 0.9rem; /* Reduced font size */
        }

        .nav-link:hover {
            color: rgba(255,255,255,1);
            background: rgba(255,255,255,.1);
            padding-left: 1.5rem;
        }

        .nav-link.active {
            color: white;
            background: rgba(255,255,255,.1);
        }

        .nav-link i {
            width: 1.2rem; /* Reduced width */
            text-align: center;
            margin-right: 0.5rem;
            font-size: 1rem; /* Reduced font size */
        }

        .nav-link .badge {
            margin-left: auto;
        }

        /* Navigation Container */
        .nav-container {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem 0; /* Reduced padding */
        }

        /* Navigation Section */
        .nav-section {
            margin-bottom: 0.5rem; /* Reduced margin */
        }

        .nav-section-title {
            color: rgba(255,255,255,0.5);
            font-size: 0.65rem; /* Reduced font size */
            text-transform: uppercase;
            padding: 0.3rem 1.5rem; /* Reduced padding */
            margin-bottom: 0.3rem; /* Reduced margin */
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
            margin-top: auto; /* Push footer to the bottom */
        }

        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            margin-right: 0.75rem;
        }

        .user-info {
            flex: 1;
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
        }

        /* Logout Link */
        .nav-link.text-danger {
            color: #dc3545 !important;
            margin-top: 0.5rem;
        }

        .nav-link.text-danger:hover {
            background: rgba(220, 53, 69, 0.1);
            color: #ff4757 !important;
        }
    </style>

    <!-- Sidebar -->
    <nav id="sidebar">
        <!-- Brand/logo -->
        <div class="sidebar-header">
            <h3>
                <i class="fas fa-network-wired"></i>
                <span>ISP Manager</span>
            </h3>
        </div>

        <!-- Navigation Links -->
        <div class="nav-container">
            <!-- Main Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>" 
                           href="/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Subscription Management -->
            <div class="nav-section">
                <div class="nav-section-title">Subscriptions</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'clients') ? 'active' : ''; ?>" 
                           href="/clients.php">
                            <i class="fas fa-users"></i>
                            <span>Clients</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'subscriptions') ? 'active' : ''; ?>" 
                           href="/subscriptions.php">
                            <i class="fas fa-project-diagram"></i>
                            <span>Subscriptions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'plans') ? 'active' : ''; ?>" 
                           href="/plans.php">
                            <i class="fas fa-boxes"></i>
                            <span>Plan Management</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Network Equipment -->
            <div class="nav-section">
                <div class="nav-section-title">Network</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'olt') ? 'active' : ''; ?>" 
                           href="/olt.php">
                            <i class="fas fa-server"></i>
                            <span>OLT Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'lcp') ? 'active' : ''; ?>" 
                           href="/lcp.php">
                            <i class="fas fa-boxes"></i>
                            <span>LCP Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'network-equipment') ? 'active' : ''; ?>" 
                           href="/network-equipment.php">
                            <i class="fas fa-hdd"></i>
                            <span>Network Equipment</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Billing & Payments -->
            <div class="nav-section">
                <div class="nav-section-title">Billing</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'billing') ? 'active' : ''; ?>" 
                           href="/billing.php">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Invoices</span>
                            <?php if (isset($pendingBills) && $pendingBills > 0): ?>
                                <span class="badge bg-warning rounded-pill ms-2"><?php echo $pendingBills; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'payments') ? 'active' : ''; ?>" 
                           href="/payments.php">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Payments</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Support -->
            <div class="nav-section">
                <div class="nav-section-title">Support</div>
                <ul class="nav flex-column">
                    
                </ul>
            </div>

            <!-- System -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'reports') ? 'active' : ''; ?>" 
                           href="/reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'users') ? 'active' : ''; ?>" 
                           href="/users.php">
                            <i class="fas fa-user-shield"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'settings') ? 'active' : ''; ?>" 
                           href="/settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage == 'database') ? 'active' : ''; ?>" 
                           href="/admin/check_database.php">
                            <i class="fas fa-database"></i>
                            <span>Database Tools</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Log Out</span>
                        </a>
                    </li>
                    
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Footer with User Profile -->
        
    </nav>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop"></div>

    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Get elements
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('sidebarToggle');
            const content = document.querySelector('.dashboard-container');
            
            if (!sidebar) return;

            // Create backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            backdrop.addEventListener('click', toggleSidebar);

            // Toggle function
            function toggleSidebar(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                sidebar.classList.toggle('active');
                
                if (sidebar.classList.contains('active')) {
                    document.body.appendChild(backdrop);
                    setTimeout(() => backdrop.classList.add('show'), 0);
                } else {
                    backdrop.classList.remove('show');
                    setTimeout(() => {
                        if (backdrop.parentNode) {
                            backdrop.remove();
                        }
                    }, 300);
                }
            }

            // Add click event to toggle button if it exists
            if (toggleButton) {
                toggleButton.addEventListener('click', toggleSidebar);
            }

            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    if (backdrop.parentNode) {
                        backdrop.remove();
                    }
                    if (content) {
                        content.style.paddingLeft = '250px';
                    }
                } else {
                    if (content) {
                        content.style.paddingLeft = '0';
                    }
                }
            });

            // Handle swipe gestures on mobile
            let touchStartX = 0;
            let touchEndX = 0;
            
            document.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });
            
            document.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, { passive: true });

            function handleSwipe() {
                const swipeThreshold = 100;
                const difference = touchEndX - touchStartX;
                
                if (Math.abs(difference) < swipeThreshold) return;
                
                if (difference > 0 && touchStartX < 50) {
                    // Swipe right from left edge - show sidebar
                    sidebar.classList.add('active');
                    document.body.appendChild(backdrop);
                    setTimeout(() => backdrop.classList.add('show'), 0);
                } else if (difference < 0 && sidebar.classList.contains('active')) {
                    // Swipe left - hide sidebar
                    sidebar.classList.remove('active');
                    backdrop.classList.remove('show');
                    setTimeout(() => {
                        if (backdrop.parentNode) {
                            backdrop.remove();
                        }
                    }, 300);
                }
            }

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && 
                        (toggleButton ? !toggleButton.contains(e.target) : true) && 
                        sidebar.classList.contains('active')) {
                        toggleSidebar();
                    }
                }
            });

            // Export toggle function
            window.toggleSidebar = toggleSidebar;
            
            // Add toggle button if it doesn't exist
            if (!toggleButton) {
                const mobileToggle = document.createElement('button');
                mobileToggle.type = 'button';
                mobileToggle.id = 'sidebarToggle';
                mobileToggle.className = 'btn btn-link d-md-none position-fixed';
                mobileToggle.style = 'top: 1rem; left: 1rem; z-index: 1040;';
                mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
                mobileToggle.addEventListener('click', toggleSidebar);
                document.body.appendChild(mobileToggle);
            }
        });
    </script>
    
<?php
}
?>
