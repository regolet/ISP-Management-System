<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/staff/dashboard">
            <img src="/img/logo.png" alt="ISP Logo" height="30" class="d-inline-block align-text-top me-2">
            ISP Management
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#staffNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="staffNavbar">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= isCurrentPage('/staff/dashboard') ? 'active' : '' ?>" 
                       href="/staff/dashboard">
                        <i class="fa fa-dashboard"></i> Dashboard
                    </a>
                </li>

                <!-- Attendance -->
                <li class="nav-item">
                    <a class="nav-link <?= isCurrentPage('/staff/attendance') ? 'active' : '' ?>" 
                       href="/staff/attendance">
                        <i class="fa fa-clock"></i> Attendance
                    </a>
                </li>

                <!-- Expenses -->
                <li class="nav-item">
                    <a class="nav-link <?= isCurrentPage('/staff/expenses') ? 'active' : '' ?>" 
                       href="/staff/expenses">
                        <i class="fa fa-receipt"></i> Expenses
                    </a>
                </li>

                <!-- Leave Management -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with(getCurrentPage(), '/staff/leave') ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-calendar"></i> Leave
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/staff/leave/apply">
                                <i class="fa fa-plus"></i> Apply Leave
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/staff/leave/history">
                                <i class="fa fa-history"></i> Leave History
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/staff/leave/balance">
                                <i class="fa fa-calculator"></i> Leave Balance
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Tasks -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with(getCurrentPage(), '/staff/tasks') ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-tasks"></i> Tasks
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/staff/tasks">
                                <i class="fa fa-list"></i> My Tasks
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/staff/tasks/completed">
                                <i class="fa fa-check"></i> Completed Tasks
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Documents -->
                <li class="nav-item">
                    <a class="nav-link <?= isCurrentPage('/staff/documents') ? 'active' : '' ?>" 
                       href="/staff/documents">
                        <i class="fa fa-file"></i> Documents
                    </a>
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
                            <a class="dropdown-item text-center" href="/staff/notifications">
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
                            <a class="dropdown-item" href="/staff/profile">
                                <i class="fa fa-user"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/staff/settings">
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
