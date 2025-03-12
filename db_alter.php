<?php

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Add the plan_id column to the client_subscriptions table
    $query = "ALTER TABLE client_subscriptions ADD COLUMN plan_id INTEGER;";
    $stmt = $db->prepare($query);
    $stmt->execute();

    // Update the plan_id column with the correct plan ID based on the plan name
    $query = "UPDATE client_subscriptions SET plan_id = (SELECT id FROM plans WHERE name = client_subscriptions.plan_name);";
    $stmt = $db->prepare($query);
    $stmt->execute();

    // Drop the plan_name column from the client_subscriptions table
    $query = "ALTER TABLE client_subscriptions DROP COLUMN plan_name;";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo "Successfully added plan_id column to client_subscriptions table and updated data.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>