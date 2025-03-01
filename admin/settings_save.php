<?php
require_once 'config.php';
check_auth('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit();
}

// Validate required fields
if (empty($_POST['company_name'])) {
    $_SESSION['error'] = "Company name is required";
    header('Location: settings.php');
    exit();
}

// Handle file upload if a new logo is provided
$logo_path = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['logo']['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($mime_type, $allowed_types)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
        header('Location: settings.php');
        exit();
    }
    
    // Validate file size
    if ($_FILES['logo']['size'] > $max_size) {
        $_SESSION['error'] = "File size must be less than 2MB";
        header('Location: settings.php');
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/logos';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('logo_') . '.' . $file_ext;
    $upload_path = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
        $logo_path = $upload_path;
    } else {
        $_SESSION['error'] = "Failed to upload logo";
        header('Location: settings.php');
        exit();
    }
}

// Sanitize inputs
$company_name = clean_input($_POST['company_name']);
$company_email = clean_input($_POST['company_email'] ?? '');
$company_phone = clean_input($_POST['company_phone'] ?? '');
$company_website = clean_input($_POST['company_website'] ?? '');
$company_address = clean_input($_POST['company_address'] ?? '');
$tax_rate = floatval($_POST['tax_rate'] ?? 0);
$currency = clean_input($_POST['currency'] ?? 'PHP');

// Validate email if provided
if (!empty($company_email) && !filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header('Location: settings.php');
    exit();
}

// Validate website URL if provided
if (!empty($company_website) && !filter_var($company_website, FILTER_VALIDATE_URL)) {
    $_SESSION['error'] = "Invalid website URL format";
    header('Location: settings.php');
    exit();
}

// Validate tax rate
if ($tax_rate < 0 || $tax_rate > 100) {
    $_SESSION['error'] = "Tax rate must be between 0 and 100";
    header('Location: settings.php');
    exit();
}

try {
    // Check if settings already exist
    $check = $conn->query("SELECT id FROM settings WHERE setting_key = 'company_profile' LIMIT 1");
    
    if ($check->num_rows > 0) {
        // Update existing settings
        $sql = "UPDATE settings SET 
                setting_value = ?,
                company_name = ?,
                company_email = ?,
                company_phone = ?,
                company_website = ?,
                company_address = ?,
                tax_rate = ?,
                currency = ?";
        
        $params = [
            'updated', // setting_value
            $company_name,
            $company_email,
            $company_phone,
            $company_website,
            $company_address,
            $tax_rate,
            $currency
        ];
        
        // Add logo path to update if new logo was uploaded
        if ($logo_path) {
            $sql .= ", logo_path = ?";
            $params[] = $logo_path;
        }
        
        $sql .= " WHERE setting_key = 'company_profile'";
        
    } else {
        // Insert new settings
        $sql = "INSERT INTO settings (
                setting_key,
                setting_value,
                description,
                company_name,
                company_email,
                company_phone,
                company_website,
                company_address,
                tax_rate,
                currency" . ($logo_path ? ", logo_path" : "") . ")
                VALUES (
                'company_profile',
                'initial',
                'Company profile settings',
                ?, ?, ?, ?, ?, ?, ?" . ($logo_path ? ", ?" : "") . ")";
        
        $params = [
            $company_name,
            $company_email,
            $company_phone,
            $company_website,
            $company_address,
            $tax_rate,
            $currency
        ];
        
        if ($logo_path) {
            $params[] = $logo_path;
        }
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Settings updated successfully";
        
        // Log the activity
        log_activity(
            $_SESSION['user_id'],
            'settings_update',
            "Updated company settings"
        );
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Settings save error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to save settings: " . $e->getMessage();
}

header('Location: settings.php');
exit();
