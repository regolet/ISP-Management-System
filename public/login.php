
<?php
session_start();

// Clear session if requested (from redirect loop prevention)
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    session_unset();
    session_destroy();
    session_start();
}

// Reset redirect counter on login page
$_SESSION['redirect_count'] = 0;

// Rest of your login page code follows...

<?php
session_start();

// Clear any existing session data
session_unset();
session_destroy();
session_start();

// Load initialization file
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $auth = new \App\Controllers\AuthController(); // Use fully qualified name
            if ($auth->login($username, $password)) {
                // Log successful login
                log_activity('login', 'User logged in successfully');
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
                log_activity('login_failed', "Failed login attempt for username: $username");
            }
        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred during login. Please try again.";
        }
    }
}

// Get flash messages
if (isset($_GET['expired'])) {
    $error = "Your session has expired. Please login again.";
}

$flash = get_flash_message();
if ($flash) {
    if ($flash['type'] === 'error') {
        $error = $flash['message'];
    } else {
        $success = $flash['message'];
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .btn-login {
            background-color: #3498db;
            border-color: #3498db;
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-network-wired"></i>
            <h2>ISP Manager</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           placeholder="Username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required
                           autocomplete="username">
                    <div class="invalid-feedback">
                        Please enter your username.
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           placeholder="Password"
                           required
                           autocomplete="current-password">
                    <div class="invalid-feedback">
                        Please enter your password.
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">Default login: admin / password</small>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form validation -->
    <script>
        (function() {
            'use strict';
            
            // Fetch all forms that need validation
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
