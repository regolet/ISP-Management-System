<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/customer/dashboard">
            <img src="/img/logo.png" alt="ISP Logo" height="30" class="d-inline-block align-text-top me-2">
            ISP Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="customerNavbar">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= isCurrentPage('/customer/dashboard') ? 'active' : '' ?>" 
                       href="/customer/dashboard">
                        <i class="fa fa-dashboard"></i> Dashboard
                    </a>
                </li>

                <!-- Billing -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with(getCurrentPage(), '/customer/billing') ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-file-invoice-dollar"></i> Billing
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/customer/billing">
                                <i class="fa fa-list"></i> View Bills
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/billing/history">
                                <i class="fa fa-history"></i> Billing History
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/billing/statements">
                                <i class="fa fa-file-pdf"></i> Statements
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Payments -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with(getCurrentPage(), '/customer/payments') ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-credit-card"></i> Payments
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/customer/payments/make">
                                <i class="fa fa-plus"></i> Make Payment
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/payments/history">
                                <i class="fa fa-history"></i> Payment History
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/payments/methods">
                                <i class="fa fa-wallet"></i> Payment Methods
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Subscription -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with(getCurrentPage(), '/customer/subscription') ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-wifi"></i> Subscription
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/customer/subscription">
                                <i class="fa fa-info-circle"></i> Current Plan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/subscription/usage">
                                <i class="fa fa-chart-line"></i> Usage Stats
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/subscription/upgrade">
                                <i class="fa fa-arrow-up"></i> Upgrade Plan
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Support -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with(getCurrentPage(), '/customer/support') ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-headset"></i> Support
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/customer/support/tickets">
                                <i class="fa fa-ticket"></i> Support Tickets
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/support/new">
                                <i class="fa fa-plus"></i> New Ticket
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/support/faq">
                                <i class="fa fa-question-circle"></i> FAQ
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- Right Side Navigation -->
            <ul class="navbar-nav">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-bell"></i>
                        <?php if (!empty($notifications)): ?>
                            <span class="badge bg-danger"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <?php if (empty($notifications)): ?>
                            <div class="dropdown-item text-center text-muted">
                                No new notifications
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <a class="dropdown-item" href="<?= $notification['link'] ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="notification-icon bg-<?= $notification['type'] ?> text-white rounded-circle p-2 me-3">
                                            <i class="fa fa-<?= getNotificationIcon($notification['type']) ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">
                                                <?= timeAgo($notification['created_at']) ?>
                                            </div>
                                            <div><?= htmlspecialchars($notification['message']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="/customer/notifications">
                                View All Notifications
                            </a>
                        <?php endif; ?>
                    </div>
                </li>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="<?= getProfileImage() ?>" alt="Profile" 
                             class="rounded-circle me-1" width="32" height="32">
                        <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/customer/profile">
                                <i class="fa fa-user"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/customer/settings">
                                <i class="fa fa-cog"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/logout">
                                <i class="fa fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
function isCurrentPage($path) {
    return $_SERVER['REQUEST_URI'] === $path;
}

function getCurrentPage() {
    return $_SERVER['REQUEST_URI'];
}

function getNotificationIcon($type) {
    return match ($type) {
        'success' => 'check-circle',
        'warning' => 'exclamation-triangle',
        'danger' => 'times-circle',
        'info' => 'info-circle',
        default => 'bell'
    };
}

function getProfileImage() {
    $profileImage = $_SESSION['user_profile_image'] ?? null;
    return $profileImage ? "/uploads/profiles/{$profileImage}" : "/img/default-profile.png";
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>

<style>
.notification-dropdown {
    width: 300px;
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

.notification-dropdown .dropdown-item {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.notification-dropdown .dropdown-item:last-child {
    border-bottom: none;
}

.notification-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.navbar .nav-link {
    padding: 0.5rem 1rem;
}

.navbar-dark .navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.9);
}

.navbar-dark .navbar-nav .nav-link:hover {
    color: #fff;
}

.navbar-dark .navbar-nav .nav-link.active {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.25rem;
}
</style>
