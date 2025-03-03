<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - ISP Management System</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
        
        <div class="dashboard-section">
            <h3>Account Information</h3>
            <p>User ID: <?= htmlspecialchars($userId) ?></p>
            <p>Role: <?= htmlspecialchars($_SESSION['role']) ?></p>
        </div>

        <div class="dashboard-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="/customer/profile">My Profile</a></li>
                <li><a href="/customer/billing">Billing Information</a></li>
                <li><a href="/customer/subscription">My Subscription</a></li>
                <li><a href="/customer/payments">Payment History</a></li>
            </ul>
        </div>

        <div class="dashboard-actions">
            <a href="/logout" class="logout-btn">Logout</a>
        </div>
    </div>

    <style>
        .dashboard-section {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dashboard-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .dashboard-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .dashboard-section ul li {
            margin-bottom: 0.5rem;
        }

        .dashboard-section ul li a {
            color: #4a90e2;
            text-decoration: none;
            display: block;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }

        .dashboard-section ul li a:hover {
            background-color: #f8f9fa;
        }

        .dashboard-actions {
            margin-top: 2rem;
            text-align: center;
        }

        .logout-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</body>
</html>
