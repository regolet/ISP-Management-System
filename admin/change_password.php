<?php
require_once 'config.php';
check_login();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current user's password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    // Validate current password
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    // Validate new password
    if (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long";
    }
    
    // Validate password confirmation
    if ($new_password !== $confirm_password) {
        $errors[] = "New password and confirmation do not match";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            log_activity($_SESSION['user_id'], 'change_password', "User changed their password");
            $success = true;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP Billing System - Change Password</title>
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
        .password-strength {
            height: 5px;
            transition: all 0.3s ease;
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
            <a href="logout.php" class="nav-link mt-auto"><i class='bx bxs-log-out'></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <h2>Change Password</h2>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Password changed successfully!
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

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                    <div class="invalid-feedback">Please enter your current password</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" 
                                           id="new_password" required minlength="6">
                                    <div class="progress mt-2">
                                        <div class="progress-bar password-strength" role="progressbar"></div>
                                    </div>
                                    <div class="invalid-feedback">Password must be at least 6 characters long</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                    <div class="invalid-feedback">Please confirm your new password</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
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

        // Password strength meter
        document.getElementById('new_password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strength = calculatePasswordStrength(password);
            const progressBar = document.querySelector('.password-strength');
            
            progressBar.style.width = strength.score + '%';
            progressBar.className = 'progress-bar password-strength bg-' + strength.class;
        });

        function calculatePasswordStrength(password) {
            let score = 0;
            let strengthClass = 'danger';

            if (password.length >= 8) score += 20;
            if (password.match(/[a-z]/)) score += 20;
            if (password.match(/[A-Z]/)) score += 20;
            if (password.match(/[0-9]/)) score += 20;
            if (password.match(/[^a-zA-Z0-9]/)) score += 20;

            if (score >= 80) strengthClass = 'success';
            else if (score >= 60) strengthClass = 'info';
            else if (score >= 40) strengthClass = 'warning';

            return { score, class: strengthClass };
        }
    </script>
</body>
</html>
