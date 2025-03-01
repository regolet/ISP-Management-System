<?php
require_once 'config.php';
check_login();

$page_title = isset($_GET['id']) ? 'Edit User' : 'Add New User';
$_SESSION['active_menu'] = 'users';

$user = null;
$error = null;
$success = null;

 // Get available roles from database
$roles_query = "SELECT name, description FROM roles WHERE 1 ORDER BY name";
$roles_result = $conn->query($roles_query);

// If roles table doesn't exist or is empty, use default roles
if (!$roles_result || $roles_result->num_rows === 0) {
    $available_roles = [
        ['name' => 'admin', 'description' => 'Full system access and management capabilities'],
        ['name' => 'staff', 'description' => 'Regular staff member with basic system access'],
        ['name' => 'customer', 'description' => 'Customer account with limited access']
    ];
} else {
    $available_roles = [];
    while ($role = $roles_result->fetch_assoc()) {
        // Ensure role name is lowercase for consistent comparison
        $role['name'] = strtolower($role['name']);
        $available_roles[] = $role;
    }
}

// Debug roles and user data
if (isset($_GET['id'])) {
    error_log("Available roles: " . json_encode($available_roles));
    error_log("User role: " . ($user['role'] ?? 'not set'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT) : null;
        $username = clean_input($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $role = clean_input($_POST['role']);
        $status = clean_input($_POST['status']);
        
        // Validate inputs
        if (empty($username) || strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        // Validate role
        $valid_role = false;
        foreach ($available_roles as $available_role) {
            if ($available_role['name'] === $role) {
                $valid_role = true;
                break;
            }
        }
        if (!$valid_role) {
            throw new Exception("Invalid role selected");
        }

        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception("Invalid status selected");
        }

        $conn->begin_transaction();

        if ($id) {
            // Prevent self-role change for admin
            if ($id == $_SESSION['user_id'] && $_SESSION['role'] == 'admin' && $role != 'admin') {
                throw new Exception("Admin users cannot change their own role");
            }

            // Update existing user
            $query = "UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $username, $email, $role, $status, $id);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 8) {
                    throw new Exception("Password must be at least 8 characters long");
                }
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    throw new Exception("Passwords do not match");
                }
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $pwd_stmt->bind_param("si", $password_hash, $id);
                $pwd_stmt->execute();
            }
        } else {
            // Check if username exists
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Username already exists");
            }

            // Check if email exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Email already exists");
            }

            // Validate password for new user
            if (empty($_POST['password']) || strlen($_POST['password']) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            if ($_POST['password'] !== $_POST['confirm_password']) {
                throw new Exception("Passwords do not match");
            }

            // Create new user
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssss", $username, $email, $password_hash, $role, $status);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error saving user: " . $conn->error);
        }

        $conn->commit();
        log_activity($_SESSION['user_id'], $id ? 'update_user' : 'create_user', "User: $username");
        $_SESSION['success'] = "User " . ($id ? "updated" : "created") . " successfully";
        header("Location: users.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

 // Get user data for editing
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Validate ID
    if (!$id) {
        $_SESSION['error'] = "Invalid user ID";
        header("Location: users.php");
        exit;
    }
    
    try {
        // Prepare and execute query
        $stmt = $conn->prepare("
            SELECT id, username, email, LOWER(role) as role, status 
            FROM users 
            WHERE id = ? 
            LIMIT 1
        ");
        
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to fetch user data: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Close statement
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: users.php");
        exit;
    }
}

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2"><?php echo $page_title; ?></h1>
            </div>
            <div class="col text-end">
                <a href="users.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Users
                </a>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <?php if ($user): ?>
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required 
                                   value="<?php echo $user ? htmlspecialchars($user['username']) : ''; ?>"
                                   pattern=".{3,}"
                                   title="Username must be at least 3 characters long">
                            <div class="invalid-feedback">
                                Please enter a valid username (minimum 3 characters)
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required 
                                   value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">
                            <div class="invalid-feedback">
                                Please enter a valid email address
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required 
                                    <?php echo ($user && $user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                <option value="">Select Role</option>
                                <?php foreach ($available_roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['name']); ?>" 
                                            <?php 
                                            if ($user && isset($user['role'])) {
                                                echo strtolower($user['role']) === strtolower($role['name']) ? 'selected' : '';
                                            }
                                            ?>
                                            title="<?php echo htmlspecialchars($role['description']); ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role['name']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a role
                            </div>
                            <?php if ($user && $user['id'] == $_SESSION['user_id']): ?>
                                <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required
                                    <?php echo ($user && $user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                <option value="active" <?php echo ($user && $user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($user && $user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a status
                            </div>
                            <?php if ($user && $user['id'] == $_SESSION['user_id']): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($user['status']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <?php echo $user ? '(Leave blank to keep current)' : ''; ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" 
                                       <?php echo $user ? '' : 'required'; ?>
                                       minlength="8" id="password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class='bx bx-show'></i>
                                </button>
                            </div>
                            <div class="form-text">Minimum 8 characters</div>
                            <div class="invalid-feedback">
                                Password must be at least 8 characters long
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" 
                                       <?php echo $user ? '' : 'required'; ?>
                                       minlength="8" id="confirmPassword">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class='bx bx-show'></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Passwords do not match
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save'></i> Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips for role descriptions
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form validation
    const form = document.querySelector('.needs-validation');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (password.value || confirmPassword.value) {
            if (password.value !== confirmPassword.value) {
                event.preventDefault();
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        form.classList.add('was-validated');
    });

    // Password toggle visibility
    function setupPasswordToggle(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);

        toggle.addEventListener('click', function() {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            toggle.querySelector('i').className = `bx bx-${type === 'password' ? 'show' : 'hide'}`;
        });
    }

    setupPasswordToggle('password', 'togglePassword');
    setupPasswordToggle('confirmPassword', 'toggleConfirmPassword');
});
</script>

<?php include 'footer.php'; ?>