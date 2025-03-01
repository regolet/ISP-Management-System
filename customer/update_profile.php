<?php
require_once __DIR__ . '/../config.php';
check_auth('customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Get customer ID
        $customer_query = "SELECT c.id FROM customers c WHERE c.user_id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if (!$customer) {
            throw new Exception('Customer not found');
        }

        // Validate inputs
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            throw new Exception('Name is required');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email is required');
        }

        if (empty($contact)) {
            throw new Exception('Contact person is required');
        }

        if (empty($contact_number)) {
            throw new Exception('Contact number is required');
        }

        if (empty($address)) {
            throw new Exception('Address is required');
        }

        // Start transaction
        $conn->autocommit(FALSE);

        // Update customer table
        $update_customer = "UPDATE customers SET 
            name = ?,
            email = ?,
            contact = ?,
            contact_number = ?,
            address = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_customer);
        $stmt->bind_param(
            "sssssi",
            $name,
            $email,
            $contact,
            $contact_number,
            $address,
            $customer['id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update customer information');
        }

        // Update user email if it's different
        $update_user = "UPDATE users SET 
            email = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_user);
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update user information');
        }

        // If everything is successful, commit the transaction
        if (!$conn->commit()) {
            throw new Exception('Failed to commit changes');
        }

        // Re-enable autocommit
        $conn->autocommit(TRUE);

        $_SESSION['success_message'] = 'Profile updated successfully';
        header('Location: profile.php');
        exit;

    } catch (Exception $e) {
        // Rollback changes if there was an error
        $conn->rollback();
        // Re-enable autocommit
        $conn->autocommit(TRUE);

        $_SESSION['error_message'] = 'Error updating profile: ' . $e->getMessage();
        header('Location: profile.php');
        exit;
    }
}

// If not POST request, redirect back to profile
header('Location: profile.php');
exit;