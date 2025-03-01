<?php
require_once '../config.php';
check_auth();

// Get database connection
$conn = get_db_connection();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $conn->beginTransaction();

        // Check if customer exists and is not active
        $stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            throw new Exception("Customer not found.");
        }

        if ($customer['status'] === 'active') {
            throw new Exception("Cannot delete an active customer.");
        }

        // Check for connected ONUs
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_onus WHERE customer_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            throw new Exception("Cannot delete customer with connected ONUs. Please remove ONUs first.");
        }

        // Delete customer
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        $conn->commit();
        $_SESSION['success'] = "Customer deleted successfully.";

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: customers.php");
exit();
?>
