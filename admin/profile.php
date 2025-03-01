<?php
require_once 'config.php';
check_login();

$errors = [];
$success = false;

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check email uniqueness
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            log_activity($_SESSION['user_id'], 'update_profile', "User updated their profile");
            $success = true;
            
            // Update session email
            $_SESSION['email'] = $email;
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM payments p 
     INNER JOIN billing b ON p.billing_id = b.id 
     INNER JOIN customers c ON b.customer_id = c.id 
     WHERE c.user_id = ?) as payments_processed,
    (SELECT COUNT(*) FROM activity_log WHERE user_id = ?) as total_activities,
    (SELECT COUNT(DISTINCT DATE(created_at)) FROM activity_log WHERE user_id = ?) as active_days,
    (SELECT created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 1) as last_activity";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent activities
$activities_query = "SELECT * FROM activity_log 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5";
$stmt = $conn->prepare($activities_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_activities = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP Billing System - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #343a40;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: rgba(255,255,255,.8);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,.1);
        }
        .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .profile-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        .activity-type {
            text-transform: capitalize;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="sidebar" style="width: 250px;">
        <h4 class="mb-4">ISP Billing System</h4>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="customers.php" class="nav-link"><i class='bx bxs-user-detail'></i> Customers</a>
            <a href="plans.php" class="nav-link"><i class='bx bxs-package'></i> Plans</a>
            <a href="billing.php" class="nav-link"><i class='bx bxs-receipt'></i> Billing</a>
            <a href="payments.php" class="nav-link"><i class='bx bxs-bank'></i> Payments</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="users.php" class="nav-link"><i class='bx bxs-user'></i> Users</a>
            <?php endif; ?>
            <a href="profile.php" class="nav-link active"><i class='bx bxs-user-circle'></i> My Profile</a>
            <a href="logout.php" class="nav-link mt-auto"><i class='bx bxs-log-out'></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Profile updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="display-1 mb-0">
                            <i class='bx bxs-user-circle'></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="mb-0">
                            <span class="badge bg-light text-dark"><?php echo ucfirst($user['role']); ?></span>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-auto">
                        <a href="change_password.php" class="btn btn-light">
                            <i class='bx bx-lock-alt'></i> Change Password
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <!-- User Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div>Payments Processed</div>
                                <div class="fw-bold"><?php echo number_format($stats['payments_processed']); ?></div>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <div>Total Activities</div>
                                <div class="fw-bold"><?php echo number_format($stats['total_activities']); ?></div>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <div>Active Days</div>
                                <div class="fw-bold"><?php echo number_format($stats['active_days']); ?></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>Last Activity</div>
                                <div class="fw-bold">
                                    <?php echo $stats['last_activity'] ? date('M d, Y H:i', strtotime($stats['last_activity'])) : 'Never'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" required
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Account Created</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Last Login</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>" readonly>
                                </div>

                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                            <a href="user_activity.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 200px;">Date & Time</th>
                                            <th style="width: 150px;">Activity Type</th>
                                            <th>Description</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                                <td>
                                                    <span class="activity-type">
                                                        <?php echo ucwords(str_replace('_', ' ', $activity['type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if ($recent_activities->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent activities</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
