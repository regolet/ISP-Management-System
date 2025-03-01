<?php
/**
 * Clean user input
 * @param string $input Input to clean
 * @return string Cleaned input
 */
function clean_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Log user activity
 * @param int $user_id User ID performing the action
 * @param string $type Activity type
 * @param string $description Activity description
 * @return bool Success status
 */
function log_activity($user_id, $type, $description) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, type, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $user_id,
            $type,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send portal credentials email to customer
 * @param string $email Customer email
 * @param string $username Portal username
 * @param string $password Portal password
 * @return bool Success status
 */
function send_portal_credentials($email, $username, $password) {
    $subject = "Your ISP Portal Access Credentials";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { text-align: center; padding: 20px; color: #6c757d; }
            .button { 
                display: inline-block;
                padding: 10px 20px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to Our ISP Portal</h2>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>Your portal access has been set up. Here are your login credentials:</p>
                <p><strong>Username:</strong> {$username}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p>For security reasons, please change your password after your first login.</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='http://your-domain.com/portal' class='button'>Access Portal</a>
                </p>
                <p><strong>Important Security Tips:</strong></p>
                <ul>
                    <li>Never share your password with anyone</li>
                    <li>Use a strong, unique password</li>
                    <li>Enable two-factor authentication if available</li>
                </ul>
            </div>
            <div class='footer'>
                <p>If you didn't request this account, please contact our support team immediately.</p>
                <p>© " . date('Y') . " Your ISP Name. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ISP Support <support@your-domain.com>',
        'Reply-To: support@your-domain.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate a secure random password
 * @param int $length Password length
 * @return string Generated password
 */
function generate_secure_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $chars_length = strlen($chars);
    
    // Ensure at least one of each character type
    $password .= $chars[random_int(0, 25)]; // lowercase
    $password .= $chars[random_int(26, 51)]; // uppercase
    $password .= $chars[random_int(52, 61)]; // number
    $password .= $chars[random_int(62, strlen($chars) - 1)]; // special char
    
    // Fill the rest randomly
    for ($i = 4; $i < $length; $i++) {
        $password .= $chars[random_int(0, $chars_length - 1)];
    }
    
    // Shuffle the password
    return str_shuffle($password);
}

/**
 * Validate portal username
 * @param string $username Username to validate
 * @param int $exclude_user_id User ID to exclude from unique check
 * @return array [is_valid, error_message]
 */
function validate_portal_username($username, $exclude_user_id = null) {
    try {
        $conn = get_db_connection();
        
        // Check length
        if (strlen($username) < 5) {
            return [false, "Username must be at least 5 characters long"];
        }
        
        // Check format (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return [false, "Username can only contain letters, numbers, and underscores"];
        }
        
        // Check uniqueness
        $query = "SELECT id FROM users WHERE username = ?";
        $params = [$username];
        
        if ($exclude_user_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_user_id;
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            return [false, "Username already exists"];
        }
        
        return [true, ""];
    } catch (Exception $e) {
        error_log("Username validation error: " . $e->getMessage());
        return [false, "System error occurred"];
    }
}

/**
 * Get customer portal status badge HTML
 * @param string $status Portal status
 * @return string HTML for status badge
 */
function get_portal_status_badge($status) {
    $badge_class = match($status) {
        'active' => 'success',
        'inactive' => 'secondary',
        'suspended' => 'warning',
        default => 'danger'
    };
    
    return sprintf(
        '<span class="badge bg-%s"><i class="bx bx-user"></i> %s</span>',
        $badge_class,
        ucfirst($status)
    );
}

/**
 * Format customer balance for display
 * @param float $balance Customer balance
 * @return string Formatted balance
 */
function format_balance($balance) {
    return '₱' . number_format($balance, 2);
}

/**
 * Get customer payment status badge HTML
 * @param string $status Payment status
 * @return string HTML for status badge
 */
function get_payment_status_badge($status) {
    $badge_class = match($status) {
        'paid' => 'success',
        'unpaid' => 'warning',
        'overdue' => 'danger',
        default => 'secondary'
    };
    
    return sprintf(
        '<span class="badge bg-%s">%s</span>',
        $badge_class,
        ucfirst($status)
    );
}
