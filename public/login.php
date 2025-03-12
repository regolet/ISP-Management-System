<?php
// Start session
session_start();

// Load initialization file
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: clients.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $auth = new \App\Controllers\AuthController();
            if ($auth->login($username, $password)) {
                // Redirect to clients page
                header("Location: clients.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch (\Exception $e) {
            $error = "An error occurred during login. Please try again.";
        }
    }
}

// Get flash messages
if (isset($_GET['expired'])) {
    $error = "Your session has expired. Please login again.";
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISP Management System</title>

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       required>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">Default login: admin / password</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>