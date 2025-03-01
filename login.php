<?php
require_once 'init.php';  // This will handle session start

if (basename($_SERVER['PHP_SELF']) == 'login.php' && isset($_SESSION['user_id'])) {
    session_destroy();
    $_SESSION['info'] = "You have been logged out successfully.";
    session_start();
}

// Simplified session check
if (isset($_SESSION['user_id']) && !isset($_POST['username'])) {
    $redirect_path = match($_SESSION['role']) {
        'admin' => 'admin/dashboard.php',
        'staff' => 'staff/dashboard.php',
        'customer' => 'customer/dashboard.php',
        default => 'login.php'
    };
    header("Location: $redirect_path");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        try {
            $conn = get_db_connection();
            
            $stmt = $conn->prepare("
                SELECT u.*, e.id as employee_id 
                FROM users u
                LEFT JOIN employees e ON e.user_id = u.id
                WHERE u.username = ? AND u.status = 'active'
            ");
            
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Clear logout message
                unset($_SESSION['info']);
                
                // Update last login
                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->execute([$user['id']]);
                
                // Log activity
                log_activity($user['id'], 'login', 'User logged in successfully');
                
                // Set success message
                $_SESSION['success'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";
                
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        exit();
                    case 'staff':
                        if ($user['employee_id']) {
                            $_SESSION['employee_id'] = $user['employee_id'];
                            header("Location: staff/dashboard.php");
                            exit();
                        } else {
                            error_log("Staff login failed - No employee_id for user: " . $user['id']);
                            $error = "Staff account not linked to employee record";
                            session_destroy();
                            session_start();
                        }
                        break;
                    case 'customer':
                        header("Location: customer/dashboard.php");
                        exit();
                    default:
                        $error = "Invalid user role";
                        session_destroy();
                        session_start();
                        break;
                }
            } else {
                $error = "Invalid username or password";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "System error occurred. Please try again later.";
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISP Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 400px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        .logo-wrapper {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-wrapper i {
            font-size: 3rem;
            color: var(--primary-color);
        }
        
        .logo-wrapper h2 {
            margin: 1rem 0;
            color: #333;
            font-weight: 600;
        }
        
        .form-label {
            color: var(--secondary-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e3e6f0;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 1px solid #e3e6f0;
            background-color: #f8f9fc;
        }
        
        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #224abe;
            transform: translateY(-1px);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #fff3f3;
            color: var(--danger-color);
        }
        
        .copyright {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card">
            <div class="card-body">
                <div class="logo-wrapper">
                    <i class='bx bx-network-chart'></i>
                    <h2>ISP Management</h2>
                    <p class="text-muted">Sign in to start your session</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error-circle me-2'></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-user'></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Enter your username" required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-lock-alt'></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-log-in-circle me-2'></i>
                            Sign In
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> ISP Management System. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
            button.disabled = true;
        });
    </script>
</body>
</html>
