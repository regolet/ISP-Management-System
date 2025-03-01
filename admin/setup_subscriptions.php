<?php
require_once 'config.php';

try {
    $conn->begin_transaction();

    echo "Starting subscription table updates...\n";

    // Add notes column
    $sql1 = "ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS notes TEXT AFTER status";
    if ($conn->query($sql1)) {
        echo "✓ Successfully added notes column\n";
    } else {
        throw new Exception("Failed to add notes column: " . $conn->error);
    }

    // Add auto_renew column
    $sql2 = "ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS auto_renew BOOLEAN DEFAULT TRUE AFTER notes";
    if ($conn->query($sql2)) {
        echo "✓ Successfully added auto_renew column\n";
    } else {
        throw new Exception("Failed to add auto_renew column: " . $conn->error);
    }

    // Update status enum to include all needed values
    $sql3 = "ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'cancelled') DEFAULT 'active'";
    if ($conn->query($sql3)) {
        echo "✓ Successfully updated status column values\n";
    } else {
        throw new Exception("Failed to update status column: " . $conn->error);
    }

    $conn->commit();
    echo "\n✅ All subscription table updates completed successfully!\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>