<?php
require_once 'config.php';
require_once 'auth_check.php';

$errors = [];
$success = false;

// Get user and customer information
$stmt = $conn->prepare("
    SELECT u.*, c.name, c.address, c.balance, c.status as account_status
    FROM users u
    LEFT JOIN customers c ON c.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $address = clean_input($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
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
    
    // Password validation
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update user information
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $email, $hashed_password, $_SESSION['user_id']);
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $email, $_SESSION['user_id']);
            }
            $stmt->execute();
            
            // Update customer information
            $stmt = $conn->prepare("UPDATE customers SET name = ?, address = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $name, $address, $_SESSION['user_id']);
            $stmt->execute();
            
            // Log activity
            log_activity($_SESSION['user_id'], 'profile_update', "User updated their profile");
            
            $conn->commit();
            $success = true;
            
            // Update session email
            $_SESSION['email'] = $email;
            
            // Refresh user data
            $stmt = $conn->prepare("
                SELECT u.*, c.name, c.address, c.balance, c.status as account_status
                FROM users u
                LEFT JOIN customers c ON c.user_id = u.id
                WHERE u.id = ?
            ");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ISP Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #0d6efd, #0099ff);
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
        }
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .content-wrapper {
            margin-left: 250px; /* Match sidebar width */
            padding: 20px;
            min-height: 100vh;
            background: #f8f9fa;
        }
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_navbar.php'; ?>

    <div class="d-flex">
        <div class="flex-grow-1 content-wrapper">
            <div class="container py-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="profile-header text-center">
                                <div class="avatar mx-auto">
                                    <i class='bx bxs-user'></i>
                                </div>
                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                <p class="mb-0">Account Status: 
                                    <span class="badge bg-<?php echo $user['account_status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['account_status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="card-body p-4">
                                <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Profile updated successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>

                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                    </div>

                                    <hr class="my-4">
                                    <h5>Change Password</h5>
                                    <p class="text-muted small">Leave blank if you don't want to change your password</p>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password">
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class='bx bx-save'></i> Save Changes
                                        </button>
                                    </div>
                                </form>
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
