<?php
require_once __DIR__ . '/../config.php';
check_auth('customer');

// Get user data
$user_query = "SELECT 
    u.id,
    u.username,
    u.email as user_email,
    c.name,
    c.contact,
    c.contact_number,
    c.email as customer_email,
    c.address,
    c.customer_code,
    c.installation_date,
    c.status,
    c.balance,
    c.outstanding_balance,
    c.credit_balance
FROM users u 
JOIN customers c ON u.id = c.user_id 
WHERE u.id = ?";

$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Set page title
$page_title = 'Profile';
include __DIR__ . '/../header.php';
include __DIR__ . '/navbar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Profile</h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">

        <div class="row">
            <!-- Profile Details -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Profile Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="update_profile.php" method="POST">
                            <div class="mb-3">
                                <label class="small font-weight-bold">Username</label>
                                <p><?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold">Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['customer_email'] ?? $user['user_email'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold">Contact Person</label>
                                <input type="text" class="form-control" name="contact" 
                                       value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold">Contact Number</label>
                                <input type="text" class="form-control" name="contact_number" 
                                       value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold">Address</label>
                                <textarea class="form-control" name="address" rows="3" required><?php 
                                    echo htmlspecialchars($user['address'] ?? ''); 
                                ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="small font-weight-bold">Customer Code</label>
                            <p><?php echo htmlspecialchars($user['customer_code'] ?? 'Not set'); ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="small font-weight-bold">Installation Date</label>
                            <p><?php echo $user['installation_date'] ? date('M d, Y', strtotime($user['installation_date'])) : 'Not set'; ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="small font-weight-bold">Status</label>
                            <p><?php echo ucfirst($user['status'] ?? 'Unknown'); ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="small font-weight-bold">Current Balance</label>
                            <p>₱<?php echo number_format($user['balance'] ?? 0, 2); ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="small font-weight-bold">Outstanding Balance</label>
                            <p>₱<?php echo number_format($user['outstanding_balance'] ?? 0, 2); ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="small font-weight-bold">Credit Balance</label>
                            <p>₱<?php echo number_format($user['credit_balance'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>