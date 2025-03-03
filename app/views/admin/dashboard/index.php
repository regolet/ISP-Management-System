<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
            color: white;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1rem;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        
        .sidebar-menu li {
            padding: 0.5rem 1.5rem;
        }
        
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .topbar {
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 70px;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
        }
        
        .topbar-search {
            position: relative;
            width: 300px;
        }
        
        .topbar-search input {
            padding-left: 2.5rem;
            border-radius: 20px;
        }
        
        .topbar-search i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .dashboard-content {
            margin-top: 90px;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h6 {
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class='bx bx-network-chart'></i>
            ISP Management
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="/admin/dashboard" class="active">
                    <i class='bx bxs-dashboard'></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="/admin/customers">
                    <i class='bx bxs-user-detail'></i>
                    Customers
                </a>
            </li>
            <li>
                <a href="/admin/plans">
                    <i class='bx bx-package'></i>
                    Plans
                </a>
            </li>
            <li>
                <a href="/admin/billing">
                    <i class='bx bx-money'></i>
                    Billing
                </a>
            </li>
            <li>
                <a href="/admin/network">
                    <i class='bx bx-network-chart'></i>
                    Network
                </a>
            </li>
            <li>
                <a href="/admin/employees">
                    <i class='bx bxs-user-badge'></i>
                    Employees
                </a>
            </li>
            <li>
                <a href="/admin/settings">
                    <i class='bx bx-cog'></i>
                    Settings
                </a>
            </li>
            <li>
                <a href="/logout">
                    <i class='bx bx-log-out'></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-search">
                <i class='bx bx-search'></i>
                <input type="text" class="form-control" placeholder="Search...">
            </div>
            <div class="user-info">
                <div class="notifications">
                    <i class='bx bx-bell'></i>
                </div>
                <div class="messages">
                    <i class='bx bx-envelope'></i>
                </div>
                <div class="user">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span><?= htmlspecialchars($username) ?></span>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">250</div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bxs-user-detail bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Monthly Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$25,000</div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-money bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Network Uptime</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">99.9%</div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-network-chart bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Active Tickets</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">18</div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-support bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6>Revenue Overview</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6>Customer Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie">
                                <canvas id="customerDistribution"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [20000, 22000, 25000, 24000, 25000, 28000],
                    borderColor: '#4e73df',
                    tension: 0.3,
                    fill: false
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Customer Distribution Chart
        const distributionCtx = document.getElementById('customerDistribution').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Residential', 'Business', 'Enterprise'],
                datasets: [{
                    data: [150, 75, 25],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
