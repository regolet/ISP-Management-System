<?php
$title = 'Login - ISP Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    
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
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error-circle me-2'></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle me-2'></i>
                        <?= htmlspecialchars($_SESSION['info']) ?>
                    </div>
                    <?php unset($_SESSION['info']); ?>
                <?php endif; ?>
                
                <form method="POST" action="/login">
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class='bx bx-user'></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Enter your username" required 
                                   value="<?= htmlspecialchars($username ?? '') ?>">
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
            &copy; <?= date('Y') ?> ISP Management System. All rights reserved.
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
